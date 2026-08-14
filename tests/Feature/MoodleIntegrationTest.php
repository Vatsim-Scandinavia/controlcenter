<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Jobs\SyncMoodleTrainingEnrolments;
use App\Models\Area;
use App\Models\MoodleCourse;
use App\Models\MoodleCourseRule;
use App\Models\MoodleEnrolment;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use App\Services\MoodleClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class MoodleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => str_repeat('a', 32),
            'services.moodle.enabled' => true,
            'services.moodle.url' => 'https://moodle.test',
            'services.moodle.token' => 'secret-token',
            'services.moodle.student_role_id' => 5,
        ]);
    }

    #[Test]
    public function entering_pre_training_queues_moodle_enrolment(): void
    {
        Queue::fake();
        $training = $this->training(TrainingStatus::IN_QUEUE);

        $training->update(['status' => TrainingStatus::PRE_TRAINING]);

        Queue::assertPushed(SyncMoodleTrainingEnrolments::class, fn ($job): bool => $job->training->is($training));
    }

    #[Test]
    public function job_matches_cid_username_and_enrols_configured_courses(): void
    {
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $course = MoodleCourse::factory()->create(['moodle_id' => 42]);
        MoodleCourseRule::factory()->create([
            'moodle_course_id' => $course->id,
            'area_id' => $training->area_id,
            'rating_id' => $training->ratings->first()->id,
        ]);

        Http::fake(function (Request $request) use ($training): mixed {
            return match ($request['wsfunction']) {
                'core_user_get_users_by_field' => Http::response([[
                    'id' => 77,
                    'username' => (string) $training->user_id,
                    'fullname' => 'Moodle Student',
                    'deleted' => false,
                    'suspended' => false,
                ]]),
                'enrol_manual_enrol_users' => Http::response(null),
            };
        });

        (new SyncMoodleTrainingEnrolments($training))->handle(app(MoodleClient::class));

        $this->assertDatabaseHas('moodle_user_links', [
            'user_id' => $training->user_id,
            'moodle_user_id' => 77,
            'match_type' => 'automatic',
        ]);
        $this->assertDatabaseHas('moodle_enrolments', [
            'training_id' => $training->id,
            'moodle_course_id' => $course->id,
            'status' => 'enrolled',
            'attempts' => 1,
        ]);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'enrol_manual_enrol_users'
            && (int) $request['enrolments'][0]['userid'] === 77
            && (int) $request['enrolments'][0]['courseid'] === 42);
    }

    #[Test]
    public function missing_cid_user_records_a_visible_failure_without_throwing(): void
    {
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $course = MoodleCourse::factory()->create();
        MoodleCourseRule::factory()->create([
            'moodle_course_id' => $course->id,
            'area_id' => $training->area_id,
            'rating_id' => $training->ratings->first()->id,
        ]);
        Http::fake(['*' => Http::response([])]);

        (new SyncMoodleTrainingEnrolments($training))->handle(app(MoodleClient::class));

        $this->assertDatabaseHas('moodle_enrolments', [
            'training_id' => $training->id,
            'status' => 'failed',
            'last_error' => "No Moodle user has username {$training->user_id}.",
        ]);
        Http::assertSentCount(1);
    }

    #[Test]
    public function training_staff_can_manually_assign_courses_to_a_training(): void
    {
        Queue::fake();
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $course = MoodleCourse::factory()->create();
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $training->area_id]);

        $this->actingAs($staff)
            ->post(route('training.moodle.courses.assign', $training), [
                'course_ids' => [$course->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('moodle_enrolments', [
            'training_id' => $training->id,
            'moodle_course_id' => $course->id,
            'status' => 'pending',
        ]);
        Queue::assertPushed(SyncMoodleTrainingEnrolments::class);
    }

    #[Test]
    public function job_enrols_manually_assigned_courses_without_an_automatic_rule(): void
    {
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $course = MoodleCourse::factory()->create(['moodle_id' => 42]);
        MoodleEnrolment::factory()->create([
            'training_id' => $training->id,
            'moodle_course_id' => $course->id,
        ]);

        Http::fake(function (Request $request) use ($training): mixed {
            return match ($request['wsfunction']) {
                'core_user_get_users_by_field' => Http::response([[
                    'id' => 77,
                    'username' => (string) $training->user_id,
                    'fullname' => 'Moodle Student',
                    'deleted' => false,
                    'suspended' => false,
                ]]),
                'enrol_manual_enrol_users' => Http::response(null),
            };
        });

        (new SyncMoodleTrainingEnrolments($training))->handle(app(MoodleClient::class));

        $this->assertDatabaseHas('moodle_enrolments', [
            'training_id' => $training->id,
            'moodle_course_id' => $course->id,
            'status' => 'enrolled',
        ]);
    }

    #[Test]
    public function bundled_rating_training_receives_courses_for_each_target_rating(): void
    {
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $secondRating = Rating::factory()->create();
        $training->ratings()->attach($secondRating);
        $courses = MoodleCourse::factory()->count(2)->create();

        MoodleCourseRule::factory()->create([
            'moodle_course_id' => $courses[0]->id,
            'area_id' => $training->area_id,
            'rating_id' => $training->ratings->first()->id,
        ]);
        MoodleCourseRule::factory()->create([
            'moodle_course_id' => $courses[1]->id,
            'area_id' => $training->area_id,
            'rating_id' => $secondRating->id,
        ]);

        Http::fake(function (Request $request) use ($training): mixed {
            if ($request['wsfunction'] === 'core_user_get_users_by_field') {
                return Http::response([[
                    'id' => 77,
                    'username' => (string) $training->user_id,
                    'fullname' => 'Moodle Student',
                    'deleted' => false,
                    'suspended' => false,
                ]]);
            }

            return Http::response(null);
        });

        (new SyncMoodleTrainingEnrolments($training))->handle(app(MoodleClient::class));

        $this->assertDatabaseCount('moodle_enrolments', 2);
        $this->assertDatabaseMissing('moodle_enrolments', ['status' => 'failed']);
        Http::assertSentCount(3);
    }

    #[Test]
    public function course_catalogue_uses_the_endpoint_that_skips_inaccessible_courses(): void
    {
        Http::fake(['*' => Http::response([
            'courses' => [
                ['id' => 1, 'shortname' => 'SITE', 'fullname' => 'Site'],
                ['id' => 3, 'shortname' => 'BASIC', 'fullname' => 'Basic ATC'],
            ],
            'warnings' => [],
        ])]);

        $courses = app(MoodleClient::class)->courses();

        $this->assertCount(1, $courses);
        $this->assertSame(3, $courses[0]['id']);
        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_course_get_courses_by_field'
            && $request['field'] === ''
            && $request['value'] === '');
    }

    #[Test]
    public function moodle_api_errors_are_recorded_before_the_job_retries(): void
    {
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $course = MoodleCourse::factory()->create();
        MoodleCourseRule::factory()->create([
            'moodle_course_id' => $course->id,
            'area_id' => $training->area_id,
            'rating_id' => $training->ratings->first()->id,
        ]);
        Http::fakeSequence()
            ->push([[
                'id' => 77,
                'username' => (string) $training->user_id,
                'fullname' => 'Moodle Student',
                'deleted' => false,
                'suspended' => false,
            ]])
            ->push(['exception' => 'moodle_exception', 'message' => 'Manual enrolment is disabled.']);

        try {
            (new SyncMoodleTrainingEnrolments($training))->handle(app(MoodleClient::class));
            $this->fail('The Moodle API error should be rethrown for the queue to retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Manual enrolment is disabled.', $exception->getMessage());
        }

        $this->assertDatabaseHas('moodle_enrolments', [
            'training_id' => $training->id,
            'status' => 'failed',
            'attempts' => 1,
            'last_error' => 'Manual enrolment is disabled.',
        ]);
    }

    #[Test]
    public function training_staff_can_search_moodle_and_link_a_fallback_user(): void
    {
        Queue::fake();
        $training = $this->training(TrainingStatus::PRE_TRAINING);
        $existingEnrolment = MoodleEnrolment::factory()->create([
            'training_id' => $training->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $training->area_id]);

        Http::fake(function (Request $request): mixed {
            if ($request['wsfunction'] === 'core_user_search_identity') {
                return Http::response([
                    'list' => [[
                        'id' => 91,
                        'fullname' => 'Fallback Student',
                        'extrafields' => [
                            ['name' => 'username', 'value' => 'different-username'],
                            ['name' => 'email', 'value' => 'student@example.test'],
                        ],
                    ]],
                    'maxusersperpage' => 100,
                    'overflow' => false,
                ]);
            }

            return Http::response([[
                'id' => 91,
                'username' => 'different-username',
                'fullname' => 'Fallback Student',
                'deleted' => false,
            ]]);
        });

        $this->actingAs($staff)
            ->getJson(route('training.moodle.users', [$training, 'query' => 'Fallback']))
            ->assertOk()
            ->assertJsonPath('users.0.id', 91)
            ->assertJsonPath('users.0.username', 'different-username');

        $this->actingAs($staff)
            ->post(route('training.moodle.link', $training), ['moodle_user_id' => 91])
            ->assertRedirect();

        $this->assertDatabaseHas('moodle_user_links', [
            'user_id' => $training->user_id,
            'moodle_user_id' => 91,
            'match_type' => 'manual',
            'linked_by' => $staff->id,
        ]);
        $this->assertDatabaseHas('moodle_enrolments', [
            'id' => $existingEnrolment->id,
            'status' => 'pending',
            'enrolled_at' => null,
        ]);
        Queue::assertPushed(SyncMoodleTrainingEnrolments::class);
    }

    #[Test]
    public function fallback_user_search_is_only_available_during_pre_training(): void
    {
        $training = $this->training(TrainingStatus::IN_QUEUE);
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $training->area_id]);
        Http::fake();

        $this->actingAs($staff)
            ->getJson(route('training.moodle.users', [$training, 'query' => 'student']))
            ->assertUnprocessable();

        Http::assertNothingSent();
    }

    #[Test]
    public function course_rules_are_restricted_to_the_training_staffs_areas(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $course = MoodleCourse::factory()->create();
        $rating = Rating::factory()->create();
        $area->ratings()->attach($rating);
        $otherArea->ratings()->attach($rating);
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $area->id]);
        $ordinaryUser = User::factory()->create();

        $this->actingAs($ordinaryUser)
            ->put(route('admin.moodle.rules.update'), [
                'rules' => [$area->id => [$rating->id => [$course->id]]],
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('admin.moodle.rules.update'), [
                'rules' => [$area->id => [$rating->id => [$course->id]]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('moodle_course_rules', [
            'area_id' => $area->id,
            'rating_id' => $rating->id,
            'moodle_course_id' => $course->id,
        ]);

        $this->actingAs($staff)
            ->put(route('admin.moodle.rules.update'), [
                'rules' => [$otherArea->id => [$rating->id => [$course->id]]],
            ])
            ->assertSessionHasErrors('rules');
    }

    #[Test]
    public function automatic_rule_screen_uses_each_areas_configured_training_ratings(): void
    {
        $area = Area::factory()->create();
        $configuredRating = Rating::factory()->create(['name' => 'Configured Endorsement', 'vatsim_rating' => null]);
        $unconfiguredRating = Rating::factory()->create(['name' => 'Other Endorsement', 'vatsim_rating' => null]);
        $area->ratings()->attach($configuredRating);
        MoodleCourse::factory()->create();
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $area->id]);

        $this->actingAs($staff)
            ->get(route('admin.moodle'))
            ->assertOk()
            ->assertSee('Add automatic assignment')
            ->assertSee('Select an area and training first')
            ->assertSee('Configured Endorsement training')
            ->assertDontSee('Other Endorsement training');
    }

    #[Test]
    public function automatic_rule_screen_hides_course_pickers_until_a_training_is_selected(): void
    {
        $area = Area::factory()->create(['name' => 'Copenhagen']);
        $configuredRating = Rating::factory()->create(['name' => 'S2']);
        $availableRating = Rating::factory()->create(['name' => 'S3']);
        $area->ratings()->attach([$configuredRating->id, $availableRating->id]);
        $course = MoodleCourse::factory()->create();
        MoodleCourseRule::factory()->create([
            'area_id' => $area->id,
            'rating_id' => $configuredRating->id,
            'moodle_course_id' => $course->id,
        ]);
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $area->id]);

        $this->actingAs($staff)
            ->get(route('admin.moodle'))
            ->assertOk()
            ->assertSee('id="moodle-training-picker"', false)
            ->assertSee('data-moodle-rule-panel="'.$area->id.'.'.$configuredRating->id.'"', false)
            ->assertSee('data-moodle-rule-configured="true"', false)
            ->assertSee('S2 training (configured)')
            ->assertSee('data-moodle-rule-panel="'.$area->id.'.'.$availableRating->id.'"', false)
            ->assertSee('data-moodle-rule-configured="false"', false)
            ->assertSee('class="border rounded p-3 mb-3 d-none"', false);
    }

    protected function training(TrainingStatus $status): Training
    {
        $area = Area::factory()->create();
        $student = User::factory()->create();

        $training = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => $area->id,
            'type' => 1,
            'status' => $status,
        ]);

        $training->ratings()->attach(Rating::factory()->create());

        return $training->load('ratings');
    }
}
