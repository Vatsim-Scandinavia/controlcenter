<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteMirrorTest extends TestCase
{
    use RefreshDatabase;

    private function editKey(): string
    {
        ApiKey::create(['id' => 'v1-edit-key', 'name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'v1-edit-key';
    }

    public function test_v1_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('api.v1.booking.index'));
        $this->assertTrue(app('router')->has('api.v1.positions.index'));
        $this->assertTrue(app('router')->has('api.v1.users.index'));
        $this->assertSame('/api/v1/bookings', route('api.v1.booking.index', absolute: false));
    }

    public function test_v1_users_endpoint_matches_legacy(): void
    {
        User::factory()->create();

        $token = $this->editKey();
        $legacy = $this->withToken($token)->getJson('/api/users');
        $v1 = $this->withToken($token)->getJson('/api/v1/users');

        $legacy->assertOk();
        $v1->assertOk();
        $this->assertSame($legacy->json(), $v1->json());
    }

    public function test_legacy_route_names_still_exist(): void
    {
        $this->assertTrue(app('router')->has('api.booking.index'));
        $this->assertTrue(app('router')->has('api.users.index'));
    }
}
