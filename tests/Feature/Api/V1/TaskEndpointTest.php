<?php

namespace Tests\Feature\Api\V1;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\TaskStatus;
use App\Helpers\TrainingStatus;
use App\Models\ApiKey;
use App\Models\Area;
use App\Models\Rating;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Tasks\Types\Custom;
use App\Tasks\Types\RatingUpgrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function editKey(): string
    {
        ApiKey::firstOrCreate(['id' => 'task-edit-key'], ['name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'task-edit-key';
    }

    private function readOnlyKey(): string
    {
        ApiKey::firstOrCreate(['id' => 'task-ro-key'], ['name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'task-ro-key';
    }

    private function makeTraining(): Training
    {
        return Training::factory()->create([
            'user_id' => User::factory()->create()->id,
            'area_id' => Area::factory()->create()->id,
            'status' => TrainingStatus::ACTIVE_TRAINING->value,
        ]);
    }

    private function makeStaff(Training $training): User
    {
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area_id]);

        return $staff;
    }

    public function test_task_endpoints_reject_missing_and_read_only_keys_for_writes(): void
    {
        $this->getJson('/api/v1/tasks')->assertUnauthorized();
        $this->postJson('/api/v1/tasks', [])->assertUnauthorized();
        $this->withToken($this->readOnlyKey())->postJson('/api/v1/tasks', [])->assertUnauthorized();
        $this->withToken($this->readOnlyKey())->getJson('/api/v1/tasks')->assertOk();
    }

    public function test_create_list_and_complete_a_custom_task(): void
    {
        $training = $this->makeTraining();
        $assignee = $this->makeStaff($training);
        $creator = User::factory()->create();
        $token = $this->editKey();

        $created = $this->withToken($token)->postJson('/api/v1/tasks', [
            'type' => 'Custom',
            'message' => 'Please review this student',
            'subject_training_id' => $training->id,
            'assignee_user_id' => $assignee->id,
            'actor_cid' => $creator->id,
        ])->assertCreated()
            ->assertJsonPath('data.type', 'Custom')
            ->assertJsonPath('data.status', TaskStatus::PENDING->value)
            ->assertJsonPath('data.status_label', 'pending')
            ->assertJsonPath('data.text', 'Please review this student')
            ->assertJsonPath('data.subject_user_id', $training->user_id)
            ->assertJsonPath('data.creator_user_id', $creator->id);

        $taskId = $created->json('data.id');
        $this->assertSame(Custom::class, Task::find($taskId)->type);

        $this->withToken($token)->getJson('/api/v1/tasks?status=0&assignee_user_id=' . $assignee->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $taskId);
        $this->withToken($token)->getJson('/api/v1/tasks?status=1')->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($token)->getJson('/api/v1/tasks?type=Custom&training_id=' . $training->id)->assertOk()->assertJsonCount(1, 'data');

        $this->withToken($token)->postJson('/api/v1/tasks/' . $taskId . '/complete')
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::COMPLETED->value)
            ->assertJsonPath('data.status_label', 'completed');

        $task = Task::find($taskId);
        $this->assertTrue((bool) $task->assignee_notified);
        $this->assertNotNull($task->closed_at);

        $this->withToken($token)->postJson('/api/v1/tasks/' . $taskId . '/complete')->assertStatus(409);
        $this->withToken($token)->postJson('/api/v1/tasks/' . $taskId . '/decline')->assertStatus(409);
    }

    public function test_decline_a_task(): void
    {
        $training = $this->makeTraining();
        $assignee = $this->makeStaff($training);
        $task = Task::create([
            'type' => Custom::class,
            'message' => 'Check something',
            'subject_user_id' => $training->user_id,
            'subject_training_id' => $training->id,
            'assignee_user_id' => $assignee->id,
            'created_at' => now(),
        ]);

        $this->withToken($this->editKey())->postJson('/api/v1/tasks/' . $task->id . '/decline')
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::DECLINED->value)
            ->assertJsonPath('data.status_label', 'declined');
    }

    public function test_create_refuses_an_assignee_who_cannot_receive_tasks(): void
    {
        $training = $this->makeTraining();
        $regularUser = User::factory()->create();

        $this->withToken($this->editKey())->postJson('/api/v1/tasks', [
            'type' => 'Custom',
            'message' => 'Hello there',
            'subject_training_id' => $training->id,
            'assignee_user_id' => $regularUser->id,
        ])->assertStatus(409);

        $this->assertSame(0, Task::count());
    }

    public function test_create_validates_type_and_message(): void
    {
        $training = $this->makeTraining();
        $assignee = $this->makeStaff($training);
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/tasks', [
            'type' => 'Nope',
            'subject_training_id' => $training->id,
            'assignee_user_id' => $assignee->id,
        ])->assertStatus(422)->assertJsonValidationErrors('type');

        $this->withToken($token)->postJson('/api/v1/tasks', [
            'type' => 'Custom',
            'subject_training_id' => $training->id,
            'assignee_user_id' => $assignee->id,
        ])->assertStatus(422)->assertJsonValidationErrors('message');
    }

    public function test_rating_upgrade_completion_requires_actor_and_reports_division_failures(): void
    {
        $training = $this->makeTraining();
        $rating = Rating::factory()->create(['vatsim_rating' => 3]);
        $training->ratings()->attach($rating->id);
        $assignee = $this->makeStaff($training);
        $task = Task::create([
            'type' => RatingUpgrade::class,
            'subject_user_id' => $training->user_id,
            'subject_training_id' => $training->id,
            'assignee_user_id' => $assignee->id,
            'created_at' => now(),
        ]);
        $token = $this->editKey();

        Setting::set('divisionApiEnabled', true);

        $this->withToken($token)->postJson('/api/v1/tasks/' . $task->id . '/complete')
            ->assertStatus(422)
            ->assertJsonValidationErrors('actor_cid');

        DivisionApi::shouldReceive('requestRatingUpgrade')
            ->once()
            ->withArgs(fn ($user, $r, $requester) => $requester === $assignee->id)
            ->andReturn(false);

        $this->withToken($token)->postJson('/api/v1/tasks/' . $task->id . '/complete', ['actor_cid' => $assignee->id])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::COMPLETED->value);

        $this->assertDatabaseHas('training_activity', ['training_id' => $training->id, 'comment' => 'Rating upgrade requested.']);
    }
}
