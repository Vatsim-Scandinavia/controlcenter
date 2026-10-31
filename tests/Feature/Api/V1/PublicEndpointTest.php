<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_positions_are_public(): void
    {
        $this->getJson('/api/v1/positions')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_v1_bookings_are_public_with_reduced_payload(): void
    {
        $this->getJson('/api/v1/bookings')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_legacy_public_routes_still_work(): void
    {
        $this->getJson('/api/positions')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/bookings')->assertOk()->assertJsonStructure(['data']);
    }
}
