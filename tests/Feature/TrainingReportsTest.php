<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingReport;
use App\Models\User;
use App\Notifications\TrainingReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingReportsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Create a training with a student.
     */
    private function makeTraining(): Training
    {
        return Training::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
    }

    /**
     * Create a mentor with a role assignment in the training's area, attached to
     * the training by default.
     */
    private function makeMentor(Training $training, bool $attach = true): User
    {
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area->id]);

        if ($attach) {
            $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);
        }

        return $mentor;
    }

    /**
     * Create a published training report (a session) for the training.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeReport(Training $training, array $attributes = []): TrainingReport
    {
        return TrainingReport::factory()->create(array_merge([
            'training_id' => $training->id,
            'draft' => false,
        ], $attributes));
    }

    #[Test]
    public function only_authorized_users_can_view_published_reports()
    {
        $training = $this->makeTraining();
        $mentor = $this->makeMentor($training);
        $report = $this->makeReport($training, ['written_by_id' => $mentor->id]);

        $buddy = User::factory()->create();
        $buddy->roleAssignments()->create(['role' => 'buddy', 'area_id' => $training->area->id]);

        $this->assertTrue($mentor->can('view', $report));
        $this->assertTrue($training->user->can('view', $report));
        $this->assertFalse(User::factory()->create()->can('view', $report));
        $this->assertFalse($buddy->can('view', $report));
    }

    #[Test]
    public function only_attached_mentors_can_view_draft_reports()
    {
        $training = $this->makeTraining();
        $attachedMentor = $this->makeMentor($training);
        $unattachedMentor = $this->makeMentor($training, attach: false);
        $report = $this->makeReport($training, ['draft' => true, 'written_by_id' => $attachedMentor->id]);

        $this->assertTrue($attachedMentor->can('view', $report));
        $this->assertFalse($training->user->can('view', $report));
        $this->assertFalse($unattachedMentor->can('view', $report));
    }

    #[Test]
    public function a_regular_user_cant_create_a_training_report()
    {
        $training = $this->makeTraining();
        $report = TrainingReport::factory()->make(['training_id' => $training->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('training.report.store', ['training' => $training->id]), $report->getAttributes())
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_reports', ['training_id' => $training->id]);
    }

    #[Test]
    public function a_buddy_can_create_a_report_via_a_one_time_link()
    {
        $training = $this->makeTraining();
        $buddy = User::factory()->create();
        $buddy->roleAssignments()->create(['role' => 'buddy', 'area_id' => $training->area->id]);

        $oneTimeLink = OneTimeLink::create([
            'training_id' => $training->id,
            'training_object_type' => OneTimeLink::TRAINING_REPORT_TYPE,
            'key' => sha1($training->id . now()),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($buddy)
            ->get(route('training.onetimelink.redirect', ['key' => $oneTimeLink->key]))
            ->assertRedirect(route('training.report.create', ['training' => $training]));
        $this->assertEquals($oneTimeLink->key, session()->get('onetimekey'));

        $this->actingAs($buddy)
            ->post(route('training.report.store', ['training' => $training]), [
                'report_date' => now()->format('d/m/Y'),
                'content' => 'Report via one-time link.',
                'contentimprove' => 'Improvement notes.',
                'position' => 'EKCH_A_TWR',
                'draft' => false,
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('training_reports', [
            'training_id' => $training->id,
            'written_by_id' => $buddy->id,
            'content' => 'Report via one-time link.',
        ]);
    }

    #[Test]
    public function a_mentor_can_update_a_training_report()
    {
        $training = $this->makeTraining();
        $mentor = $this->makeMentor($training);
        $report = $this->makeReport($training);
        $content = $this->faker->paragraph();

        $this->actingAs($mentor)
            ->patch(route('training.report.update', ['report' => $report->id]), [
                'report_date' => today()->format('d/m/Y'),
                'content' => $content,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('training_reports', ['id' => $report->id, 'content' => $content]);
    }

    #[Test]
    public function a_regular_user_cant_update_a_training_report()
    {
        $training = $this->makeTraining();
        $report = $this->makeReport($training, ['draft' => true]);
        $content = $this->faker->paragraph();

        $this->actingAs($training->user)
            ->patch(route('training.report.update', ['report' => $report->id]), ['content' => $content])
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_reports', ['content' => $content]);
    }

    #[Test]
    public function publishing_a_draft_report_stamps_published_at_and_edits_preserve_it()
    {
        $training = $this->makeTraining();
        $mentor = $this->makeMentor($training);
        $report = $this->makeReport($training, ['draft' => true, 'published_at' => null]);

        $this->assertNull($report->published_at);

        // Publishing (omitting 'draft') stamps the publish date.
        $this->actingAs($mentor)
            ->patch(route('training.report.update', ['report' => $report->id]), [
                'report_date' => today()->format('d/m/Y'),
                'content' => $report->content,
            ])
            ->assertRedirect();

        $publishedAt = $report->fresh()->published_at;
        $this->assertNotNull($publishedAt);

        // A later edit must not move the publish date.
        $this->actingAs($mentor)
            ->patch(route('training.report.update', ['report' => $report->id]), [
                'report_date' => today()->format('d/m/Y'),
                'content' => $this->faker->paragraph(),
            ])
            ->assertRedirect();

        $this->assertTrue($report->fresh()->published_at->equalTo($publishedAt));
    }

    #[Test]
    public function publishing_a_draft_report_notifies_the_student_once()
    {
        Notification::fake();

        $student = User::factory()->create(['setting_notify_newreport' => true]);
        $training = Training::factory()->create(['user_id' => $student->id]);
        $mentor = $this->makeMentor($training);
        $report = $this->makeReport($training, ['draft' => true, 'published_at' => null]);

        // First publish notifies the student.
        $this->actingAs($mentor)
            ->patch(route('training.report.update', ['report' => $report->id]), [
                'report_date' => today()->format('d/m/Y'),
                'content' => $report->content,
            ])
            ->assertRedirect();

        // A later edit of the already-published report must not re-notify.
        $this->actingAs($mentor)
            ->patch(route('training.report.update', ['report' => $report->id]), [
                'report_date' => today()->format('d/m/Y'),
                'content' => $this->faker->paragraph(),
            ])
            ->assertRedirect();

        Notification::assertSentToTimes($student, TrainingReportNotification::class, 1);
    }

    #[Test]
    public function published_at_is_set_for_published_reports_but_not_drafts()
    {
        $training = $this->makeTraining();

        $published = $this->makeReport($training, ['published_at' => null]);
        $draft = $this->makeReport($training, ['draft' => true, 'published_at' => null]);

        $this->assertNotNull($published->published_at);
        $this->assertNull($draft->published_at);
    }

    #[Test]
    public function activity_date_uses_published_at_when_present()
    {
        $training = $this->makeTraining();
        $report = $this->makeReport($training, [
            'created_at' => now()->subYear(),
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue($report->activity_date->equalTo($report->published_at));
    }

    #[Test]
    public function privileged_users_can_delete_reports()
    {
        $training = $this->makeTraining();
        $mentor = $this->makeMentor($training);

        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        foreach ([$mentor, $admin] as $user) {
            $report = $this->makeReport($training, ['written_by_id' => $mentor->id]);

            $this->actingAs($user)->get(route('training.report.delete', ['report' => $report->id]));

            $this->assertDatabaseMissing('training_reports', ['id' => $report->id]);
        }
    }

    #[Test]
    public function unauthorized_users_cannot_delete_reports()
    {
        $training = $this->makeTraining();
        $author = $this->makeMentor($training);
        $report = $this->makeReport($training, ['written_by_id' => $author->id]);

        $unattachedMentor = $this->makeMentor($training, attach: false);

        $buddy = User::factory()->create();
        $buddy->roleAssignments()->create(['role' => 'buddy', 'area_id' => $training->area->id]);
        // A report the buddy wrote themselves: they still can't delete it.
        $ownReport = $this->makeReport($training, ['written_by_id' => $buddy->id]);

        $attempts = [
            [$unattachedMentor, $report],
            [User::factory()->create(), $report],
            [$buddy, $report],
            [$buddy, $ownReport],
        ];

        foreach ($attempts as [$user, $target]) {
            $this->actingAs($user)
                ->get(route('training.report.delete', ['report' => $target->id]))
                ->assertStatus(403);
        }

        $this->assertDatabaseHas('training_reports', ['id' => $report->id]);
        $this->assertDatabaseHas('training_reports', ['id' => $ownReport->id]);
    }

    #[Test]
    public function create_report_returns_error_for_queued_training(): void
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => TrainingStatus::IN_QUEUE->value,
        ]);
        $mentor = $this->makeMentor($training);

        $this->actingAs($mentor)
            ->get(route('training.report.create', $training))
            ->assertSessionHasErrors();
    }

    #[Test]
    public function director_permissions_are_scoped_to_their_area()
    {
        $training = $this->makeTraining();
        $mentor = $this->makeMentor($training);
        $report = $this->makeReport($training, ['written_by_id' => $mentor->id]);

        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $training->area->id]);

        $this->assertTrue($director->can('view', $report));
        $this->assertTrue($director->can('update', $report));
        $this->assertTrue($director->can('delete', $report));
        $this->assertTrue($director->can('create', [TrainingReport::class, $training]));

        $otherDirector = User::factory()->create();
        $otherDirector->roleAssignments()->create(['role' => 'director', 'area_id' => Area::factory()->create()->id]);

        $this->assertFalse($otherDirector->can('update', $report));
    }
}
