<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_user_endpoint_rejects_missing_auth(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_v1_booking_create_rejects_missing_bearer_token(): void
    {
        $this->postJson('/api/v1/bookings/create', [])->assertUnauthorized();
    }

    public function test_v1_booking_create_rejects_read_only_key(): void
    {
        ApiKey::create(['id' => 'ro-key', 'name' => 't', 'read_only' => true, 'created_at' => now()]);

        $this->withToken('ro-key')->postJson('/api/v1/bookings/create', [])->assertUnauthorized();
    }
}
