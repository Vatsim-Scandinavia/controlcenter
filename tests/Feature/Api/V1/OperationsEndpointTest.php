<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Area;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function editKey(): string
    {
        ApiKey::create(['id' => 'ops-edit', 'name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'ops-edit';
    }

    private function readKey(): string
    {
        ApiKey::create(['id' => 'ops-ro', 'name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'ops-ro';
    }

    public function test_roster_and_activity_endpoints(): void
    {
        $area = Area::factory()->create();

        $this->withToken($this->readKey())
            ->getJson('/api/v1/areas/'.$area->id.'/roster')
            ->assertOk()
            ->assertJsonStructure(['data' => ['active', 'visiting']]);

        $this->withToken('ops-ro')
            ->getJson('/api/v1/areas/'.$area->id.'/activity')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_sweatbook_create_and_list(): void
    {
        $user = User::factory()->create();
        $position = Position::factory()->create();
        $date = Carbon::tomorrow()->format('d/m/Y');

        $this->withToken($this->editKey())
            ->postJson('/api/v1/sweatbooks', [
                'cid' => $user->id,
                'date' => $date,
                'start_at' => '12:00',
                'end_at' => '13:00',
                'position' => $position->callsign,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->withToken('ops-edit')
            ->getJson('/api/v1/sweatbooks')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $user->id);
    }

    public function test_feedback_update_references(): void
    {
        $submitter = User::factory()->create();
        $controller = User::factory()->create();
        $area = Area::factory()->create();
        $feedback = Feedback::factory()->create([
            'submitter_user_id' => $submitter->id,
            'reference_user_id' => null,
            'reference_position_id' => null,
            'area_id' => null,
        ]);

        $this->withToken($this->editKey())
            ->patchJson('/api/v1/feedback/'.$feedback->id, [
                'controller' => $controller->id,
                'area' => $area->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.reference_user_id', $controller->id)
            ->assertJsonPath('data.area_id', $area->id);
    }

    public function test_settings_get_and_patch(): void
    {
        $this->withToken($this->readKey())
            ->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonStructure(['data' => ['trainingEnabled', 'linkHome']]);

        $this->withToken($this->editKey())
            ->patchJson('/api/v1/settings', [
                'trainingEnabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.trainingEnabled', false);
    }

    public function test_votes_create_and_list(): void
    {
        $this->withToken($this->editKey())
            ->postJson('/api/v1/votes', [
                'question' => 'Preferred training day?',
                'end_at' => Carbon::tomorrow()->toIso8601String(),
                'options' => ['Monday', 'Tuesday'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.question', 'Preferred training day?');

        $this->withToken('ops-edit')
            ->getJson('/api/v1/votes')
            ->assertOk()
            ->assertJsonFragment(['question' => 'Preferred training day?']);
    }

    public function test_activity_log_index(): void
    {
        $this->withToken($this->readKey())
            ->getJson('/api/v1/activity-log')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);
    }
}
