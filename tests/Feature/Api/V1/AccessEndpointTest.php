<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function editKey(): string
    {
        ApiKey::create(['id' => 'access-edit', 'name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'access-edit';
    }

    private function readKey(): string
    {
        ApiKey::create(['id' => 'access-ro', 'name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'access-ro';
    }

    public function test_role_grant_rejects_admin(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();

        $this->withToken($this->editKey())
            ->postJson('/api/v1/users/'.$user->id.'/roles', [
                'role' => 'admin',
                'actor_cid' => $actor->id,
            ])
            ->assertStatus(422);
    }

    public function test_role_grant_and_revoke(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();

        $this->withToken($this->editKey())
            ->postJson('/api/v1/users/'.$user->id.'/roles', [
                'role' => 'staff',
                'actor_cid' => $actor->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'staff');

        $this->assertTrue($user->fresh()->hasRole('staff'));

        $this->withToken('access-edit')
            ->deleteJson('/api/v1/users/'.$user->id.'/roles', [
                'role' => 'staff',
                'actor_cid' => $actor->id,
            ])
            ->assertOk();

        $this->assertFalse($user->fresh()->hasRole('staff'));
    }

    public function test_role_grant_requires_edit_key(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();

        $this->withToken($this->readKey())
            ->postJson('/api/v1/users/'.$user->id.'/roles', [
                'role' => 'staff',
                'actor_cid' => $actor->id,
            ])
            ->assertUnauthorized();
    }

    public function test_visiting_endorsement_create_and_revoke(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $rating = Rating::factory()->create();

        $create = $this->withToken($this->editKey())
            ->postJson('/api/v1/endorsements', [
                'user_id' => $user->id,
                'type' => 'VISITING',
                'rating_id' => $rating->id,
                'area_ids' => [$area->id],
                'actor_cid' => $actor->id,
            ])
            ->assertCreated();

        $id = $create->json('data.id');

        $this->withToken('access-edit')
            ->deleteJson('/api/v1/endorsements/'.$id, [
                'actor_cid' => $actor->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertTrue((bool) Endorsement::find($id)->revoked);
    }
}
