<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingCreatedNotification;
use App\Notifications\TrainingMentorNotification;
use App\Services\TrainingService;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Training $training;

    protected function setUp(): void
    {
        parent::setUp();
        $this->training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
    }

    /**
     * Create a user with mentor rights in the training's area.
     */
    private function makeMentor(): User
    {
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $this->training->area->id]);

        return $mentor;
    }

    /**
     * Create a user with moderator rights in the default training's area.
     */
    private function makeModerator(): User
    {
        return $this->moderatorFor($this->training);
    }

    /**
     * Create a user with moderator rights in the given training's area.
     */
    private function moderatorFor(Training $training): User
    {
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $training->area->id]);

        return $moderator;
    }

    /**
     * An open training (ACTIVE_TRAINING) in area 1, type 1, whose student is inside the division.
     */
    private function openTrainingInDivision(): Training
    {
        Setting::set('trainingSubDivisions', 'SCA');

        $student = User::factory()->create([
            'division' => config('app.owner_code'),
            'subdivision' => 'SCA',
        ]);

        return Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => 1,
            'type' => 1,
            'status' => TrainingStatus::ACTIVE_TRAINING->value,
        ]);
    }

    /**
     * Attach a fresh facility rating (no VATSIM rating) to the training.
     */
    private function attachFacilityRating(Training $training): Rating
    {
        $rating = Rating::factory()->create(['vatsim_rating' => null]);
        $training->ratings()->attach($rating->id);

        return $rating;
    }

    /**
     * An existing active FACILITY endorsement for $rating, which completion should revoke.
     */
    private function priorFacilityEndorsement(Training $training, Rating $rating): Endorsement
    {
        $endorsement = Endorsement::factory()->create([
            'user_id' => $training->user->id,
            'type' => 'FACILITY',
        ]);
        $endorsement->ratings()->attach($rating->id);

        return $endorsement;
    }

    #[Test]
    public function student_can_close_their_in_queue_training(): void
    {
        Notification::fake();
        $this->training->update(['status' => TrainingStatus::IN_QUEUE->value]);

        $response = $this->actingAs($this->training->user)
            ->get(route('training.action.close', $this->training));

        $response->assertRedirect();
        $this->training->refresh();
        $this->assertEquals(TrainingStatus::CLOSED_BY_STUDENT, $this->training->status);
    }

    #[Test]
    public function student_cannot_close_active_training(): void
    {
        $this->training->update(['status' => TrainingStatus::ACTIVE_TRAINING->value]);

        $response = $this->actingAs($this->training->user)
            ->get(route('training.action.close', $this->training));

        $response->assertForbidden();
    }

    #[Test]
    public function a_user_can_apply_for_training(): void
    {
        Notification::fake();
        Http::fake(['api.vatsim.net/*' => Http::response([], 404)]);

        $area = Area::factory()->create();
        $ratings = [
            Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]),
            Rating::factory()->create(['vatsim_rating' => null]),
        ];

        foreach ($ratings as $rating) {
            $area->ratings()->attach($rating->id, [
                'required_vatsim_rating' => VatsimRating::OBS->value,
                'allow_bundling' => true,
                'hour_requirement' => 0,
                'queue_length_low' => 0,
                'queue_length_high' => 0,
            ]);
        }

        $applicant = User::factory()->create(['rating' => VatsimRating::OBS->value]);

        // Applying for several ratings at once bundles them with a plus sign
        $response = $this->actingAs($applicant)->post(route('training.store'), [
            'training_level' => implode('+', collect($ratings)->pluck('id')->all()),
            'training_area' => $area->id,
            'motivation' => 'I would like to learn tower control.',
            'experience' => 3,
            'englishOnly' => 1,
            'comment' => 'Available on weekends',
        ]);

        $training = Training::where('user_id', $applicant->id)->sole();
        $response->assertRedirect($training->path());

        $this->assertEquals($area->id, $training->area_id);
        $this->assertEquals($applicant->id, $training->created_by);
        $this->assertEquals('I would like to learn tower control.', $training->motivation);
        $this->assertTrue((bool) $training->english_only_training);
        $this->assertEqualsCanonicalizing(collect($ratings)->pluck('id')->all(), $training->ratings->pluck('id')->all());

        $this->assertDatabaseHas('training_activity', [
            'training_id' => $training->id,
            'type' => 'COMMENT',
            'comment' => 'Comment from application: Available on weekends',
        ]);

        Notification::assertSentTo($applicant, TrainingCreatedNotification::class);
    }

    #[Test]
    public function a_user_cant_apply_while_they_have_an_active_training(): void
    {
        Notification::fake();
        $this->training->update(['status' => TrainingStatus::IN_QUEUE->value]);

        $this->actingAs($this->training->user)
            ->post(route('training.store'), [
                'training_level' => Rating::first()->id,
                'training_area' => $this->training->area_id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertEquals(1, Training::where('user_id', $this->training->user_id)->count());
        Notification::assertNothingSent();
    }

    #[Test]
    public function a_moderator_can_create_a_training_for_another_user(): void
    {
        Notification::fake();

        $moderator = $this->makeModerator();
        $student = User::factory()->create();
        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        $attributes = [
            'user_id' => $student->id,
            'training_area' => $this->training->area_id,
            'ratings' => [$rating->id],
            'type' => 1,
        ];

        $this->actingAs($moderator)
            ->post(route('training.store'), $attributes)
            ->assertRedirect();

        $training = Training::where('user_id', $student->id)->sole();
        $this->assertEquals($moderator->id, $training->created_by);
        $this->assertTrue($training->ratings->contains($rating));
        Notification::assertSentTo($student, TrainingCreatedNotification::class);

        // A CID unknown to the application can't be given a training
        $this->actingAs($moderator)
            ->postJson(route('training.store'), array_merge($attributes, ['user_id' => User::max('id') + 1]))
            ->assertStatus(400)
            ->assertJson(['message' => 'The given CID cannot be found in the application database. Please check the user has logged in before.']);
    }

    #[Test]
    public function training_page_only_offers_rating_tasks_for_vatsim_rating_trainings()
    {
        $moderator = User::factory()->create();

        $facilityTraining = Training::factory()
            ->has(Rating::factory(['vatsim_rating' => null]))
            ->create(['user_id' => User::factory()->create()->id]);
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $facilityTraining->area->id]);

        $this->actingAs($moderator)->get($facilityTraining->path())
            ->assertSeeText('Custom Request')
            ->assertDontSeeText('Rating Upgrade')
            ->assertDontSeeText('Theoretical Exam Access');

        $combinedTraining = Training::factory()
            ->has(Rating::factory(['vatsim_rating' => VatsimRating::S2, 'name' => 'TST-S2']))
            ->create(['user_id' => User::factory()->create()->id, 'area_id' => $facilityTraining->area_id]);
        $combinedTraining->ratings()->save(Rating::factory()->create(['vatsim_rating' => null, 'name' => 'TST-MAE']));

        $this->actingAs($moderator)->get($combinedTraining->path())
            ->assertSeeText('Rating Upgrade')
            ->assertSeeText('Theoretical Exam Access')
            ->assertSee('for <b>TST-S2</b> rating', false)
            ->assertSeeText('TST-S2 + TST-MAE');
    }

    #[Test]
    public function get_highest_vatsim_rating_returns_rating_with_highest_vatsim_rating(): void
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        // Attach two ratings with different VATSIM ratings to the training
        $ratingS1 = Rating::factory()->create(['vatsim_rating' => VatsimRating::S1->value]);
        $ratingS2 = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        $training->ratings()->attach([$ratingS1->id, $ratingS2->id]);
        $training->load('ratings'); // Reload relation

        $highest = $training->getHighestVatsimRating();

        $this->assertNotNull($highest);
        $this->assertEquals(VatsimRating::S2, $highest->vatsim_rating);
    }

    #[Test]
    public function guest_cant_create_training_request()
    {
        $attributes = [
            'experience' => $this->faker->numberBetween(1, 5),
            'englishOnly' => (int) $this->faker->boolean,
            'motivation' => $this->faker->realText(1500, 2),
            'comment' => '',
            'training_level' => Rating::find($this->faker->numberBetween(1, 7))->id,
            'training_area' => Area::find($this->faker->numberBetween(1, 5))->id,
        ];

        $response = $this->post('/training/store', $attributes);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function test_director_is_eligible_as_training_mentor_in_their_area(): void
    {
        $area = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $this->assertTrue($director->hasPermission('training.mentor', $area));
        $this->assertTrue($director->hasPermission('training.mentor-dashboard.view'));
    }

    #[Test]
    public function moderator_can_update_training_request()
    {
        $moderator = User::factory()->create();

        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $training->area->id]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $this->actingAs($moderator)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertRedirect($training->path())
            ->assertSessionHas('success', 'Training successfully updated');

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => $attributes['status']]);
    }

    #[Test]
    public function a_regular_user_cant_update_a_training()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $user = $training->user;
        $user->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area->id]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $this->actingAs($user)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertStatus(403);
    }

    // #[Test]
    public function moderator_can_update_the_trainings_status()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => 0]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 1]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => 1, 'started_at' => $training->fresh()->started_at->format('Y-m-d H:i:s')]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 3]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => 3,
            'started_at' => $training->fresh()->started_at->format('Y-m-d H:i:s'),
            'closed_at' => $training->fresh()->closed_at->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => 0,
            'started_at' => null,
            'closed_at' => null,
        ]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => -1]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => -1,
            'started_at' => null,
            'closed_at' => null,
        ]);
    }

    #[Test]
    public function a_moderator_can_add_mentors_to_a_training(): void
    {
        Notification::fake();
        $this->freezeTime();

        $mentors = [$this->makeMentor(), $this->makeMentor()];

        $this->actingAs($this->makeModerator())
            ->patch(route('training.update.details', $this->training), ['mentors' => collect($mentors)->pluck('id')->all()])
            ->assertRedirect($this->training->path());

        foreach ($mentors as $mentor) {
            $this->assertTrue($this->training->fresh()->mentors->contains($mentor));
            $this->assertDatabaseHas('training_mentor', [
                'training_id' => $this->training->id,
                'user_id' => $mentor->id,
                'expire_at' => now()->addMonths(12)->toDateTimeString(),
            ]);
            $this->assertDatabaseHas('training_activity', [
                'training_id' => $this->training->id,
                'type' => 'MENTOR',
                'new_data' => $mentor->id,
            ]);
        }

        Notification::assertSentToTimes($this->training->user, TrainingMentorNotification::class, 1);
    }

    #[Test]
    public function a_moderator_can_remove_mentors_from_a_training(): void
    {
        Notification::fake();

        $keptMentor = $this->makeMentor();
        $removedMentor = $this->makeMentor();
        $this->training->mentors()->attach([$keptMentor->id, $removedMentor->id], ['expire_at' => now()->addYear()]);

        $moderator = $this->makeModerator();

        $this->actingAs($moderator)
            ->patch(route('training.update.details', $this->training), ['mentors' => [$keptMentor->id]])
            ->assertRedirect($this->training->path());

        $this->assertTrue($this->training->fresh()->mentors->contains($keptMentor));
        $this->assertFalse($this->training->fresh()->mentors->contains($removedMentor));
        $this->assertDatabaseHas('training_activity', [
            'training_id' => $this->training->id,
            'type' => 'MENTOR',
            'old_data' => $removedMentor->id,
        ]);

        // Omitting the key entirely means an empty list, which detaches everyone
        $this->actingAs($moderator)
            ->patch(route('training.update.details', $this->training), ['status' => TrainingStatus::IN_QUEUE->value])
            ->assertRedirect($this->training->path());

        $this->assertTrue($this->training->fresh()->mentors->isEmpty());
        Notification::assertNotSentTo($this->training->user, TrainingMentorNotification::class);
    }

    #[Test]
    public function test_director_can_create_training_requests_for_others(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $this->assertTrue($director->can('create', Training::class));
        $this->assertTrue($director->hasPermission('training.activities.view', Area::factory()->create()));
    }

    #[Test]
    public function a_mentor_cant_be_added_if_they_are_not_a_mentor_in_the_right_area()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
            'area_id' => 1,
        ]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $training->area->id]);
        $mentor = User::factory()->create();

        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => 2]);

        $this->actingAs($moderator)
            ->patchJson(route('training.update.details', ['training' => $training]), ['mentors' => [$mentor->id]])
            ->assertStatus(302);

        $this->assertNotTrue($training->mentors->contains($mentor));
    }

    #[Test]
    public function obs_user_gets_zero_vatsim_hours_on_api_404(): void
    {
        Http::fake([
            'api.vatsim.net/*' => Http::response([], 404),
        ]);

        Setting::set('trainingEnabled', true);
        Setting::set('trainingSubDivisions', 'SCA');
        Setting::set('atcActivityBasedOnTotalHours', false);

        $obsUser = User::factory()->create([
            'rating' => VatsimRating::OBS->value,
            'division' => config('app.owner_code'),
            'subdivision' => 'SCA',
        ]);

        $response = $this->actingAs($obsUser)->get(route('training.apply'));

        // With the fix, the page loads without an error redirect
        $response->assertSuccessful();
    }

    #[Test]
    public function apply_page_shows_available_ratings_for_s1_user(): void
    {
        Http::fake([
            'api.vatsim.net/*' => Http::response(['s1' => 10], 200),
        ]);

        Setting::set('trainingEnabled', true);
        Setting::set('trainingSubDivisions', 'SCA');
        Setting::set('atcActivityBasedOnTotalHours', false);

        $area = Area::factory()->create();
        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        // Attach to area with required_vatsim_rating = S1 (value 2) so an S1 user qualifies
        $area->ratings()->attach($rating->id, [
            'required_vatsim_rating' => VatsimRating::S1->value,
            'allow_bundling' => false,
            'hour_requirement' => 0,
            'queue_length_low' => 0,
            'queue_length_high' => 0,
        ]);

        $s1User = User::factory()->create([
            'rating' => VatsimRating::S1->value,
            'division' => config('app.owner_code'),
            'subdivision' => 'SCA',
        ]);

        // S1 user needs to be ATC active to pass TrainingPolicy (S1 > OBS triggers the check)
        AtcActivity::create([
            'user_id' => $s1User->id,
            'area_id' => $area->id,
            'atc_active' => true,
            'hours' => 0,
            'hours_in_period' => 0,
        ]);

        $response = $this->actingAs($s1User)->get(route('training.apply'));

        $response->assertSuccessful();
        // The S2 rating should appear as available for the S1 user
        $response->assertSee($rating->name);
    }

    #[Test]
    public function a_mentor_can_edit_their_own_timeline_comment(): void
    {
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $this->training->area->id]);
        $this->training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);

        $activity = $this->training->activities()->create([
            'triggered_by_id' => $mentor->id,
            'type' => 'COMMENT',
            'comment' => 'Discussed holding patterns',
        ]);

        $this->actingAs($mentor)
            ->post(route('training.activity.comment'), [
                'training_id' => $this->training->id,
                'update_id' => $activity->id,
                'comment' => 'Discussed holding patterns and missed approaches',
            ])
            ->assertRedirect();

        $this->assertEquals('Discussed holding patterns and missed approaches', $activity->fresh()->comment);
    }

    /*
    |--------------------------------------------------------------------------
    | Completion / closure side-effects (TrainingController::updateDetails,
    | delegated to App\Services\TrainingService)
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function completing_a_training_grants_a_facility_endorsement_and_activates_atc(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);

        $training = $this->openTrainingInDivision();
        $rating = $this->attachFacilityRating($training);
        $oldEndorsement = $this->priorFacilityEndorsement($training, $rating);

        DivisionApi::shouldReceive('assignTierEndorsement')->once()->andReturn(false);

        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($training->path());

        $this->assertTrue((bool) $oldEndorsement->fresh()->revoked);

        $new = Endorsement::where('user_id', $training->user->id)
            ->where('revoked', false)
            ->where('id', '!=', $oldEndorsement->id)
            ->get();
        $this->assertCount(1, $new);
        $this->assertTrue($new->first()->ratings->contains($rating->id));

        $this->assertTrue(
            AtcActivity::where('user_id', $training->user->id)
                ->where('area_id', $training->area->id)
                ->where('atc_active', true)
                ->exists()
        );

        Notification::assertSentToTimes($training->user, TrainingClosedNotification::class, 1);
    }

    #[Test]
    public function completing_a_vatsim_only_training_activates_atc_without_an_endorsement(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);

        $training = $this->openTrainingInDivision();
        $training->ratings()->attach(Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value])->id);

        // VATSIM ratings are Core-side upgrades, not facility endorsements: no API call.
        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($training->path());

        $this->assertSame(0, Endorsement::where('user_id', $training->user->id)->count());
        $this->assertTrue(
            AtcActivity::where('user_id', $training->user->id)
                ->where('area_id', $training->area->id)
                ->where('atc_active', true)
                ->exists()
        );
        Notification::assertSentToTimes($training->user, TrainingClosedNotification::class, 1);
    }

    #[Test]
    public function atc_activation_respects_the_total_hours_gate_for_familiarisation(): void
    {
        Notification::fake();
        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        // When activity is hours-based, familiarisations (type > 4) are excluded from
        // automatic ATC activation; with the setting off, every type activates.
        Setting::set('atcActivityBasedOnTotalHours', true);
        $gated = $this->openTrainingInDivision();
        $gated->update(['type' => 5]);
        $gated->ratings()->attach(Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value])->id);

        $this->actingAs($this->moderatorFor($gated))
            ->patch(route('training.update.details', $gated), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($gated->path());

        $this->assertFalse(
            AtcActivity::where('user_id', $gated->user->id)
                ->where('area_id', $gated->area->id)
                ->where('atc_active', true)
                ->exists()
        );

        Setting::set('atcActivityBasedOnTotalHours', false);
        $ungated = $this->openTrainingInDivision();
        $ungated->update(['type' => 5]);
        $ungated->ratings()->attach(Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value])->id);

        $this->actingAs($this->moderatorFor($ungated))
            ->patch(route('training.update.details', $ungated), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($ungated->path());

        $this->assertTrue(
            AtcActivity::where('user_id', $ungated->user->id)
                ->where('area_id', $ungated->area->id)
                ->where('atc_active', true)
                ->exists()
        );
    }

    #[Test]
    public function completing_a_training_for_a_user_outside_the_division_skips_atc_activation(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);
        Setting::set('trainingSubDivisions', 'SCA');

        // Both fields mismatched so the user is out-of-division regardless of app.mode
        // (subdivision mode checks subdivision; division mode checks division vs owner_code).
        $student = User::factory()->create(['division' => 'XXX', 'subdivision' => 'XXX']);
        $training = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => 1,
            'type' => 1,
            'status' => TrainingStatus::ACTIVE_TRAINING->value,
        ]);
        $training->ratings()->attach(Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value])->id);

        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($training->path());

        $this->assertSame(0, AtcActivity::where('user_id', $student->id)->count());
    }

    #[Test]
    public function a_division_api_failure_when_completing_redirects_back_without_notifying(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);

        $training = $this->openTrainingInDivision();
        $rating = $this->attachFacilityRating($training);
        $oldEndorsement = $this->priorFacilityEndorsement($training, $rating);

        $failed = new ClientResponse(new GuzzleResponse(422, [], json_encode(['message' => 'nope'])));
        DivisionApi::shouldReceive('assignTierEndorsement')->once()->andReturn($failed);
        DivisionApi::shouldReceive('getName')->andReturn('VATEUD');

        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect() // back(), not the training path
            ->assertSessionHasErrors();

        // The grant runs after the API call, so a failure leaves no new endorsement...
        $this->assertSame(1, Endorsement::where('user_id', $training->user->id)->count());
        // ...but the old one was already revoked before the call. That partial state is
        // one the controller has always produced, pinned here so a refactor can't silently change it.
        $this->assertTrue((bool) $oldEndorsement->fresh()->revoked);
        Notification::assertNotSentTo($training->user, TrainingClosedNotification::class);
    }

    #[Test]
    public function closing_a_training_without_completion_detaches_mentors_and_notifies(): void
    {
        Notification::fake();

        $training = $this->openTrainingInDivision();
        $this->attachFacilityRating($training);

        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area->id]);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);

        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        // Send the mentor back so mentor-sync keeps them: the close path itself must be
        // what detaches them, not the "no mentors key = detach all" fallback.
        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), [
                'status' => TrainingStatus::CLOSED_BY_STAFF->value,
                'closed_reason' => 'Test reason',
                'mentors' => [$mentor->id],
            ])
            ->assertRedirect($training->path());

        $this->assertTrue($training->fresh()->mentors->isEmpty());
        $this->assertSame(0, Endorsement::where('user_id', $training->user->id)->count());
        $this->assertFalse(AtcActivity::where('user_id', $training->user->id)->where('atc_active', true)->exists());
        Notification::assertSentToTimes($training->user, TrainingClosedNotification::class, 1);
    }

    #[Test]
    public function re_saving_an_already_completed_training_unchanged_fires_no_side_effects(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);

        $training = $this->openTrainingInDivision();
        $training->update(['status' => TrainingStatus::COMPLETED->value]);
        $rating = $this->attachFacilityRating($training);
        $oldEndorsement = $this->priorFacilityEndorsement($training, $rating);

        // Re-saving with the status unchanged must hit the "status changed" guard and
        // short-circuit. Otherwise a closed training would re-grant endorsements and
        // re-notify every time its detail form is submitted.
        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($training->path());

        $this->assertSame(1, Endorsement::where('user_id', $training->user->id)->count());
        $this->assertFalse((bool) $oldEndorsement->fresh()->revoked);
        Notification::assertNotSentTo($training->user, TrainingClosedNotification::class);
    }

    #[Test]
    public function close_training_on_an_open_status_notifies_and_detaches_without_completion_work(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);

        $training = $this->openTrainingInDivision(); // status = ACTIVE_TRAINING
        $this->attachFacilityRating($training);

        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        // Called directly (no HTTP path leaves a training open at this point): completion
        // work is gated on COMPLETED, but the detach + notification run for any status.
        $error = app(TrainingService::class)->closeTraining($training);

        $this->assertNull($error);
        $this->assertSame(0, Endorsement::where('user_id', $training->user->id)->count());
        $this->assertFalse(AtcActivity::where('user_id', $training->user->id)->where('atc_active', true)->exists());
        Notification::assertSentToTimes($training->user, TrainingClosedNotification::class, 1);
    }

    #[Test]
    public function completing_another_training_restarts_the_activity_grace_period(): void
    {
        Notification::fake();
        Setting::set('atcActivityBasedOnTotalHours', false);

        $training = $this->openTrainingInDivision();
        $training->ratings()->attach(Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value])->id);

        // The student is already half way through a grace period.
        $activity = AtcActivity::create([
            'user_id' => $training->user->id,
            'area_id' => $training->area->id,
            'hours' => 0,
            'atc_active' => true,
            'start_of_grace_period' => now()->subMonths(6),
        ]);

        DivisionApi::shouldReceive('assignTierEndorsement')->never();

        $this->actingAs($this->moderatorFor($training))
            ->patch(route('training.update.details', $training), ['status' => TrainingStatus::COMPLETED->value])
            ->assertRedirect($training->path());

        // Deliberate: finishing another training earns a fresh window rather than
        // running out the one already in progress.
        $this->assertTrue($activity->fresh()->start_of_grace_period->greaterThan(now()->subMinute()));
    }
}
