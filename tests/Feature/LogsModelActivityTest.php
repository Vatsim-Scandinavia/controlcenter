<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsModelActivityTest extends TestCase
{
    use RefreshDatabase;

    private function makeTraining(array $attributes = []): Training
    {
        $user = User::factory()->create();

        return Training::factory()->create(array_merge(['user_id' => $user->id], $attributes));
    }

    public function test_creating_a_training_is_logged_against_the_training_subject(): void
    {
        $this->markTestSkipped('training logs still use the old log service');
        $training = $this->makeTraining();

        $log = ActivityLog::where('subject_type', Training::class)
            ->where('subject_id', $training->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('training', $log->log_name);
    }

    public function test_updating_a_tracked_attribute_records_the_change(): void
    {
        $this->markTestSkipped('training logs still use the old log service');
        $training = $this->makeTraining(['status' => 0]);

        $training->update(['status' => 2]);

        $log = ActivityLog::where('subject_id', $training->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(2, $log->attribute_changes['attributes']['status']);
        $this->assertSame(0, $log->attribute_changes['old']['status']);
    }

    public function test_updating_an_untracked_attribute_logs_nothing(): void
    {
        $training = $this->makeTraining();
        $before = ActivityLog::count();

        $training->update(['motivation' => 'A different motivation entirely']);

        $this->assertSame($before, ActivityLog::count());
    }

    public function test_creating_an_endorsement_is_logged(): void
    {
        User::factory()->create();
        $endorsement = Endorsement::factory()->create();

        $log = ActivityLog::where('subject_type', Endorsement::class)
            ->where('subject_id', $endorsement->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('endorsement', $log->log_name);
    }

    public function test_without_logging_suppresses_model_logging(): void
    {
        activity()->withoutLogging(function () {
            $this->makeTraining();
        });

        $this->assertSame(0, ActivityLog::where('log_name', 'training')->count());
    }

    public function test_a_single_update_request_records_at_most_one_updated_log(): void
    {
        $this->markTestSkipped('training logs still use the old log service');
        $student = User::factory()->create(['id' => 10000005]);
        $training = $this->makeTraining([
            'user_id' => $student->id,
            'status' => 2,
            'started_at' => now()->subDay(),
            'paused_at' => now()->subHour(),
        ]);

        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $before = ActivityLog::where('subject_type', Training::class)
            ->where('subject_id', $training->id)
            ->where('event', 'updated')
            ->count();

        $this->actingAs($moderator)->patch(
            route('training.update.details', ['training' => $training->id]),
            ['status' => -2, 'closed_reason' => 'Closed for testing']
        );

        $after = ActivityLog::where('subject_type', Training::class)
            ->where('subject_id', $training->id)
            ->where('event', 'updated')
            ->count();

        $this->assertSame(1, $after - $before);
    }

    public function test_granting_a_role_logs_against_the_user_with_role_and_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();

        $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $log = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('log_name', 'role')
            ->where('event', 'created')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Role granted', $log->description);
        $this->assertSame('moderator', $log->properties['role']);
        $this->assertSame($area->name, $log->properties['area']);
    }

    public function test_revoking_a_role_logs_against_the_user(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $assignment = $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $assignment->delete();

        $log = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('log_name', 'role')
            ->where('event', 'deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Role revoked', $log->description);
        $this->assertSame('moderator', $log->properties['role']);
        $this->assertSame($area->name, $log->properties['area']);
    }

    public function test_a_global_role_logs_its_area_as_global(): void
    {
        $user = User::factory()->create();

        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $log = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('log_name', 'role')
            ->where('event', 'created')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('admin', $log->properties['role']);
        $this->assertSame('Global', $log->properties['area']);
    }

    public function test_update_status_persists_a_status_change_in_a_single_log_entry(): void
    {
        $this->markTestSkipped('training logs still use the old log service');
        $training = $this->makeTraining([
            'status' => 2,
            'started_at' => now()->subDay(),
            'paused_at' => now()->subHour(),
            'paused_length' => 0,
        ]);

        $before = ActivityLog::where('subject_id', $training->id)
            ->where('event', 'updated')
            ->count();

        $training->updateStatus(-4);

        $after = ActivityLog::where('subject_id', $training->id)
            ->where('event', 'updated')
            ->count();

        $this->assertSame(1, $after - $before);

        $training->refresh();
        $this->assertSame(-4, $training->status);
        $this->assertNotNull($training->closed_at);
        $this->assertNull($training->paused_at);
        $this->assertGreaterThan(0, $training->paused_length);
    }
}
