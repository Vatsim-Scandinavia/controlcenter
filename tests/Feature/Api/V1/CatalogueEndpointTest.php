<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function readKey(): string
    {
        ApiKey::create(['id' => 'catalogue-ro', 'name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'catalogue-ro';
    }

    private function editKey(): string
    {
        ApiKey::create(['id' => 'catalogue-edit', 'name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'catalogue-edit';
    }

    public function test_areas_require_api_key(): void
    {
        $this->getJson('/api/v1/areas')->assertUnauthorized();
    }

    public function test_areas_index_and_show(): void
    {
        $area = Area::factory()->create(['name' => 'Portugal']);

        $this->withToken($this->readKey())
            ->getJson('/api/v1/areas')
            ->assertOk()
            ->assertJsonFragment(['id' => $area->id, 'name' => 'Portugal']);

        $this->withToken('catalogue-ro')
            ->getJson('/api/v1/areas/'.$area->id)
            ->assertOk()
            ->assertJsonPath('data.id', $area->id);
    }

    public function test_ratings_list_includes_area_pivots(): void
    {
        $this->withToken($this->readKey())
            ->getJson('/api/v1/ratings')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'vatsim_rating', 'areas']]]);
    }

    public function test_user_show_supports_includes(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->readKey())
            ->getJson('/api/v1/users/'.$user->id.'?include[]=name&include[]=email')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.first_name', $user->first_name)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_positions_full_adds_management_fields(): void
    {
        $token = $this->readKey();

        $default = $this->withToken($token)->getJson('/api/v1/positions')->assertOk()->json('data.0');
        $defaultFields = array_keys($default);
        sort($defaultFields);
        $this->assertSame(['callsign', 'frequency', 'name'], $defaultFields);

        $full = $this->withToken($token)->getJson('/api/v1/positions?full=1')->assertOk()->json('data.0');
        foreach (['id', 'callsign', 'name', 'frequency', 'fir', 'rating', 'area_id', 'required_facility_rating_id'] as $field) {
            $this->assertArrayHasKey($field, $full);
        }
    }

    public function test_position_show_returns_management_fields(): void
    {
        $position = Position::factory()->create();

        $this->withToken($this->readKey())
            ->getJson('/api/v1/positions/'.$position->id)
            ->assertOk()
            ->assertJsonPath('data.id', $position->id)
            ->assertJsonPath('data.callsign', $position->callsign);
    }

    public function test_endorsements_index_filters_by_type(): void
    {
        $user = User::factory()->create();
        $endorsement = Endorsement::factory()->create([
            'user_id' => $user->id,
            'type' => 'VISITING',
            'expired' => false,
            'revoked' => false,
        ]);

        $this->withToken($this->readKey())
            ->getJson('/api/v1/endorsements?type=VISITING')
            ->assertOk()
            ->assertJsonFragment(['id' => $endorsement->id]);
    }

    public function test_position_store_requires_edit_key(): void
    {
        $area = Area::factory()->create();
        $payload = [
            'callsign' => 'LPPT_TWR',
            'name' => 'Lisbon Tower',
            'frequency' => '118.100',
            'fir' => 'LPPC',
            'rating' => 3,
            'area_id' => $area->id,
        ];

        ApiKey::create(['id' => 'pos-ro', 'name' => 't', 'read_only' => true, 'created_at' => now()]);
        ApiKey::create(['id' => 'pos-edit', 'name' => 't', 'read_only' => false, 'created_at' => now()]);

        $this->withToken('pos-ro')->postJson('/api/v1/positions', $payload)->assertUnauthorized();

        $this->withToken('pos-edit')
            ->postJson('/api/v1/positions', $payload)
            ->assertCreated()
            ->assertJsonPath('data.callsign', 'LPPT_TWR');
    }
}
