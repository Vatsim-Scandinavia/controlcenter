<?php

namespace Tests\Feature\Api\V1;

use App\Exceptions\VatsimAPIException;
use App\Facades\VatsimBookingApi;
use App\Helpers\VatsimRating;
use App\Models\ApiKey;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use App\Policies\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalBookingsTest extends TestCase
{
    use RefreshDatabase;

    private User $controller;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = User::factory()->create(['rating' => VatsimRating::C1->value]);
        $this->position = Position::factory()->create(['rating' => VatsimRating::S2->value]);
        $this->key('roster-key', 'staffing-adbj');
        $this->withToken('roster-key');
    }

    private function key(string $token, ?string $integration, bool $readOnly = false): void
    {
        ApiKey::create([
            'id' => $token,
            'name' => $token,
            'read_only' => $readOnly,
            'integration_id' => $integration,
            'created_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'cid' => $this->controller->id,
            'date' => now()->addDay()->format('d/m/Y'),
            'start_at' => '18:00',
            'end_at' => '20:00',
            'position' => $this->position->callsign,
            'source' => 'EXTERNAL',
            'external_id' => 'event-123-assignment-456',
            'external_url' => 'https://staffing.adbj.no/rosters/123',
            'tag' => 3,
        ], $overrides);
    }

    public function test_external_booking_is_created_with_integration_metadata(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload())
            ->assertOk()
            ->assertJsonPath('booking.source', 'EXTERNAL')
            ->assertJsonPath('booking.integration_id', 'staffing-adbj')
            ->assertJsonPath('booking.external_id', 'event-123-assignment-456')
            ->assertJsonPath('booking.external_url', 'https://staffing.adbj.no/rosters/123');
    }

    public function test_republication_updates_the_same_booking_and_controller(): void
    {
        VatsimBookingApi::shouldReceive('createBooking')->once()->andReturn(123);
        VatsimBookingApi::shouldReceive('updateBooking')->once()->andReturn(123);
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $replacement = User::factory()->create(['rating' => VatsimRating::C1->value]);

        $this->postJson('/api/v1/bookings/create', $this->payload([
            'cid' => $replacement->id, 'start_at' => '19:00',
        ]))->assertOk()->assertJsonPath('booking.id', $id)
            ->assertJsonPath('booking.user_id', $replacement->id)
            ->assertJsonPath('booking.name', $replacement->name);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_another_integration_cannot_update_or_delete_the_booking(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $this->key('other-key', 'another-roster');
        $this->withToken('other-key')->patchJson("/api/v1/bookings/$id", $this->payload())->assertForbidden();
        $this->deleteJson("/api/v1/bookings/$id")->assertForbidden();

        $this->key('legacy-key', null);
        $this->withToken('legacy-key')->deleteJson("/api/v1/bookings/$id")->assertForbidden();
        $this->assertFalse((bool) Booking::findOrFail($id)->deleted);
    }

    public function test_rotated_credentials_for_the_same_integration_keep_access(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $this->key('rotated-key', 'staffing-adbj');
        $this->withToken('rotated-key')->patchJson("/api/v1/bookings/$id", $this->payload(['end_at' => '21:00']))->assertOk();
        $this->deleteJson("/api/v1/bookings/$id")->assertOk();
    }

    public function test_integration_credentials_cannot_create_or_modify_local_bookings(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload(['source' => 'CC']))->assertForbidden();
        $booking = Booking::factory()->create(['user_id' => $this->controller->id]);
        $this->patchJson("/api/v1/bookings/{$booking->id}", $this->payload())->assertForbidden();
        $this->deleteJson("/api/v1/bookings/{$booking->id}")->assertForbidden();
    }

    public function test_external_source_requires_integration_credentials_and_assignment_id(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_id' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('external_id');
        $this->key('legacy-key', null);
        $this->withToken('legacy-key')->postJson('/api/v1/bookings/create', $this->payload())->assertForbidden();
        $this->key('read-key', 'staffing-adbj', true);
        $this->withToken('read-key')->postJson('/api/v1/bookings/create', $this->payload())->assertUnauthorized();
    }

    public function test_source_and_management_url_are_validated(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_url' => 'javascript:alert(1)']))
            ->assertUnprocessable()->assertJsonValidationErrors('external_url');
        $this->key('legacy-key', null);
        $this->withToken('legacy-key')->postJson('/api/v1/bookings/create', $this->payload(['source' => 'UNKNOWN']))
            ->assertUnprocessable()->assertJsonValidationErrors('source');
    }

    public function test_cancelled_assignments_are_not_recreated_by_retries(): void
    {
        VatsimBookingApi::shouldReceive('createBooking')->once()->andReturn(123);
        VatsimBookingApi::shouldReceive('deleteBooking')->once();
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $this->deleteJson("/api/v1/bookings/$id")->assertOk();
        $this->deleteJson("/api/v1/bookings/$id")->assertOk();
        $this->postJson('/api/v1/bookings/create', $this->payload())->assertConflict();
        $this->patchJson("/api/v1/bookings/$id", $this->payload())->assertConflict();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_each_integration_has_its_own_assignment_namespace(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk();
        $this->key('other-key', 'another-roster');
        $this->withToken('other-key')->postJson('/api/v1/bookings/create', $this->payload([
            'start_at' => '20:00', 'end_at' => '22:00',
        ]))->assertOk();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_overlaps_that_contain_an_existing_booking_are_rejected(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk();
        $this->postJson('/api/v1/bookings/create', $this->payload([
            'external_id' => 'another-assignment', 'start_at' => '17:00', 'end_at' => '21:00',
        ]))->assertBadRequest();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_failed_publication_can_be_retried_without_a_local_duplicate(): void
    {
        VatsimBookingApi::shouldReceive('createBooking')->once()->andThrow(new VatsimAPIException('Unavailable'));
        $this->postJson('/api/v1/bookings/create', $this->payload())->assertBadRequest();
        $this->assertDatabaseCount('bookings', 0);
        VatsimBookingApi::shouldReceive('createBooking')->once()->andReturn(123);
        $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_local_owner_and_staff_cannot_edit_or_delete_external_bookings(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $booking = Booking::findOrFail($id);
        $staff = User::factory()->create();
        $staff->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $policy = new BookingPolicy;
        $this->assertTrue($policy->update($this->controller, $booking)->denied());
        $this->assertTrue($policy->update($staff, $booking)->denied());

        $this->actingAs($this->controller)->get(route('booking'))
            ->assertOk()->assertSee('Externally managed booking. Open the originating system to make changes.')
            ->assertSee('fa-arrow-up-right-from-square')
            ->assertDontSeeText('Externally managed')->assertDontSeeText('Manage booking')
            ->assertSee('https://staffing.adbj.no/rosters/123');
        $this->get("/booking/$id")->assertForbidden();
        $this->get(route('booking.delete', $id))->assertForbidden();
        $this->actingAs($staff)->get(route('booking.delete', $id))->assertForbidden();
    }

    public function test_external_bookings_work_without_a_management_url(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload(['external_url' => null]))
            ->assertOk()->json('booking.id');
        $this->actingAs($this->controller)->get(route('booking'))->assertOk()
            ->assertSee('Externally managed booking. Make changes in the system where it was booked.')
            ->assertSee('tabindex="0"', false)
            ->assertDontSeeText('Externally managed')
            ->assertDontSeeText('Manage booking');
        $this->assertNull(Booking::findOrFail($id)->external_url);
    }

    public function test_management_url_is_preserved_when_omitted_and_can_be_cleared(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $payload = $this->payload();
        unset($payload['external_url']);
        $this->postJson('/api/v1/bookings/create', $payload)->assertOk()
            ->assertJsonPath('booking.external_url', 'https://staffing.adbj.no/rosters/123');
        $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_url' => null]))
            ->assertOk()->assertJsonPath('booking.external_url', null);
    }

    public function test_assignment_identity_and_source_cannot_be_changed_by_patch(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_id' => 'replacement-id']))
            ->assertUnprocessable()->assertJsonValidationErrors('external_id');
        $this->patchJson("/api/v1/bookings/$id", $this->payload(['source' => 'CC']))
            ->assertUnprocessable()->assertJsonValidationErrors('source');
        $this->assertSame('EXTERNAL', Booking::findOrFail($id)->source);
    }

    public function test_migration_can_be_rolled_back_and_reapplied_without_losing_legacy_bookings(): void
    {
        $booking = Booking::factory()->create(['source' => 'CC', 'user_id' => $this->controller->id]);
        $migration = require database_path('migrations/2026_11_07_214336_add_external_booking_management.php');

        $migration->down();
        $this->assertSame('CC', $booking->fresh()->source);
        $migration->up();

        ApiKey::where('id', 'roster-key')->update(['integration_id' => 'staffing-adbj']);
        $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk();
        $this->assertSame('CC', $booking->fresh()->source);
    }
}
