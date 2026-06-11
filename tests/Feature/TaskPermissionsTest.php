<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_create_update_and_receive_tasks(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $this->assertTrue($director->can('create', Task::class));
        $this->assertTrue($director->can('update', Task::class));
        $this->assertTrue($director->can('receive', Task::class));
    }

    public function test_regular_user_cannot_create_tasks(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('create', Task::class));
    }

    public function test_mentor_can_create_update_and_receive_tasks(): void
    {
        $area = Area::factory()->create();
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        $this->assertTrue($mentor->can('create', Task::class));
        $this->assertTrue($mentor->can('update', Task::class));
        $this->assertTrue($mentor->can('receive', Task::class));
    }

    public function test_examiner_without_staff_role_can_receive_tasks(): void
    {
        $examiner = User::factory()->create();
        Endorsement::factory()->create([
            'user_id' => $examiner->id,
            'type' => 'EXAMINER',
            'valid_from' => Carbon::now(),
            'expired' => false,
            'revoked' => false,
        ]);

        $this->assertTrue($examiner->can('receive', Task::class));
    }

    public function test_mentor_is_not_a_suggested_task_recipient(): void
    {
        $area = Area::factory()->create();
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->assertFalse($mentor->hasPermission('suggested-task-recipient'));
        $this->assertTrue($moderator->hasPermission('suggested-task-recipient'));
    }
}
