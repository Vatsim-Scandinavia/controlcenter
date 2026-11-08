<?php

namespace Tests\Feature\Api\V1;

use App\Exceptions\VatsimAPIException;
use App\Facades\VatsimBookingApi;
use App\Helpers\VatsimRating;
use App\Http\Controllers\API\BookingController;
use App\Models\ApiKey;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use App\Policies\BookingPolicy;
use Carbon\Carbon;
use Database\Seeders\ExternalBookingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
            'external_revision' => 1,
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
            'cid' => $replacement->id, 'start_at' => '19:00', 'external_revision' => 2,
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
        $this->withToken('rotated-key')->patchJson("/api/v1/bookings/$id", $this->payload(['end_at' => '21:00', 'external_revision' => 2]))->assertOk();
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 3])->assertOk();
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
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertOk();
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertOk();
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
        $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_url' => null, 'external_revision' => 2]))
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

        $this->key('replacement-key', 'staffing-adbj');
        $this->withToken('replacement-key')->postJson('/api/v1/bookings/create', $this->payload())->assertOk();
        $this->assertSame('CC', $booking->fresh()->source);
    }

    public function test_rollback_revokes_integration_keys_without_revoking_legacy_keys(): void
    {
        $booking = Booking::factory()->create(['source' => 'CC', 'user_id' => $this->controller->id]);
        $this->key('rotated-key', 'staffing-adbj');
        $this->key('another-integration-key', 'another-roster');
        $this->key('integration-read-key', 'staffing-adbj', true);
        $this->key('legacy-write-key', null);
        $this->key('legacy-read-key', null, true);
        $migration = require database_path('migrations/2026_11_07_214336_add_external_booking_management.php');

        $migration->down();

        foreach (['roster-key', 'rotated-key', 'another-integration-key', 'integration-read-key'] as $token) {
            $this->assertNull(ApiKey::find($token));
            $this->withToken($token)->postJson('/api/v1/bookings/create', [])->assertUnauthorized();
            $this->patchJson("/api/v1/bookings/{$booking->id}", [])->assertUnauthorized();
            $this->deleteJson("/api/v1/bookings/{$booking->id}")->assertUnauthorized();
            $this->getJson('/api/v1/users')->assertUnauthorized();
        }

        $this->assertNotNull(ApiKey::find('legacy-write-key'));
        foreach (['legacy-read-key'] as $token) {
            $this->assertTrue((bool) ApiKey::findOrFail($token)->read_only);
            $this->withToken($token)->deleteJson("/api/v1/bookings/{$booking->id}")->assertUnauthorized();
        }
        $this->withToken('legacy-write-key')->deleteJson("/api/v1/bookings/{$booking->id}")->assertOk();

        $migration->up();
        $this->assertNull(ApiKey::find('roster-key'));
    }

    public function test_integration_reads_are_scoped_and_user_directory_access_is_denied(): void
    {
        $ownId = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $this->key('foreign-key', 'foreign-roster');
        $foreignId = $this->withToken('foreign-key')->postJson('/api/v1/bookings/create', $this->payload([
            'start_at' => '20:00', 'end_at' => '22:00',
        ]))->assertOk()->json('booking.id');
        $this->key('integration-read-key', 'staffing-adbj', true);

        foreach (['roster-key', 'integration-read-key'] as $token) {
            $this->withToken($token)->getJson('/api/v1/bookings')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownId);
            $this->getJson("/api/v1/bookings/$foreignId")->assertForbidden();
            $this->getJson("/api/v1/bookings/$ownId")->assertOk();
            $this->getJson('/api/v1/users?include[]=allUsers&include[]=email')->assertForbidden();
            $this->getJson('/api/users?include[]=allUsers&include[]=email')->assertForbidden();
            $this->getJson('/api/bookings')->assertOk()->assertJsonCount(1, 'data');
            $this->getJson('/api/v1/positions')->assertOk();
        }

        $this->key('general-key', null);
        $this->withToken('general-key')->getJson('/api/v1/bookings')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/users?include[]=allUsers&include[]=email')->assertOk();
    }

    public function test_assignment_identifiers_are_case_sensitive(): void
    {
        $upperId = $this->postJson('/api/v1/bookings/create', $this->payload(['external_id' => 'Assignment-A']))->assertOk()->json('booking.id');
        $lowerId = $this->postJson('/api/v1/bookings/create', $this->payload([
            'external_id' => 'assignment-a', 'start_at' => '20:00', 'end_at' => '22:00',
        ]))->assertOk()->json('booking.id');
        $this->assertNotSame($upperId, $lowerId);
        $this->postJson('/api/v1/bookings/create', $this->payload([
            'external_id' => 'Assignment-A', 'external_revision' => 2, 'start_at' => '19:00',
        ]))->assertOk()->assertJsonPath('booking.id', $upperId);
        $this->assertSame('assignment-a', Booking::findOrFail($lowerId)->external_id);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_older_publications_and_cancellations_cannot_overwrite_a_newer_revision(): void
    {
        VatsimBookingApi::shouldReceive('createBooking')->once()->andReturn(123);
        VatsimBookingApi::shouldReceive('updateBooking')->once()->andReturn(123);
        VatsimBookingApi::shouldReceive('deleteBooking')->never();
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_revision' => 3, 'start_at' => '19:00']))->assertOk();
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_revision' => 2]))->assertConflict();
        $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_revision' => 2]))->assertConflict();
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertConflict();
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_revision' => 3, 'start_at' => '19:00']))->assertOk()
            ->assertJsonPath('booking.external_revision', 3);
        $this->assertSame('19:00', Carbon::parse(Booking::findOrFail($id)->time_start)->format('H:i'));
        $this->assertFalse((bool) Booking::findOrFail($id)->deleted);
    }

    public function test_external_mutations_require_a_revision(): void
    {
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_revision' => null]))->assertUnprocessable()->assertJsonValidationErrors('external_revision');
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $payload = $this->payload();
        unset($payload['external_revision']);
        $this->patchJson("/api/v1/bookings/$id", $payload)->assertUnprocessable()->assertJsonValidationErrors('external_revision');
        $this->deleteJson("/api/v1/bookings/$id")->assertUnprocessable()->assertJsonValidationErrors('external_revision');
    }

    public function test_position_mutations_wait_for_competing_assignments_and_recheck_overlap(): void
    {
        VatsimBookingApi::shouldReceive('createBooking')->once()->andReturn(123);
        $lock = Cache::lock('booking-position:' . $this->position->id, 120);
        $this->assertTrue($lock->get());
        try {
            $this->postJson('/api/v1/bookings/create', $this->payload())->assertConflict();
            $this->assertDatabaseCount('bookings', 0);
        } finally {
            $lock->release();
        }

        $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk();
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_id' => 'competing-assignment']))->assertBadRequest();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_patch_and_delete_share_the_publication_lock(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $lock = Cache::lock('external-booking:' . hash('sha256', 'staffing-adbj:event-123-assignment-456'), 120);
        $this->assertTrue($lock->get());
        VatsimBookingApi::shouldReceive('updateBooking')->never();
        VatsimBookingApi::shouldReceive('deleteBooking')->once();
        try {
            $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_revision' => 2]))->assertConflict();
            $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertConflict();
        } finally {
            $lock->release();
        }
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertOk();
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertOk();
        $this->postJson('/api/v1/bookings/create', $this->payload(['external_revision' => 3]))->assertConflict();
    }

    public function test_patch_waits_for_the_target_position_before_checking_and_saving(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        VatsimBookingApi::shouldReceive('updateBooking')->once()->andReturn(123);
        $lock = Cache::lock('booking-position:' . $this->position->id, 120);
        $this->assertTrue($lock->get());
        try {
            $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_revision' => 2]))->assertConflict();
            $this->assertSame(1, Booking::findOrFail($id)->external_revision);
        } finally {
            $lock->release();
        }
        $this->patchJson("/api/v1/bookings/$id", $this->payload(['external_revision' => 2]))->assertOk();
        $this->assertSame(2, Booking::findOrFail($id)->external_revision);
    }

    public function test_native_booking_creation_uses_the_same_position_lock_as_the_api(): void
    {
        $this->controller->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        VatsimBookingApi::shouldReceive('createBooking')->once()->andReturn(123);
        $lock = Cache::lock('booking-position:' . $this->position->id, 120);
        $this->assertTrue($lock->get());
        try {
            $this->actingAs($this->controller)->from('/booking')->post(route('booking.store'), $this->payload())
                ->assertRedirect('/booking')->assertSessionHasErrors();
            $this->assertDatabaseCount('bookings', 0);
        } finally {
            $lock->release();
        }
        $this->post(route('booking.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['position_id' => $this->position->id, 'source' => 'CC']);
    }

    public function test_mutations_reload_booking_state_after_acquiring_the_lock(): void
    {
        $id = $this->postJson('/api/v1/bookings/create', $this->payload())->assertOk()->json('booking.id');
        $stale = Booking::findOrFail($id);
        $this->deleteJson("/api/v1/bookings/$id", ['external_revision' => 2])->assertOk();
        VatsimBookingApi::shouldReceive('deleteBooking')->never();
        $request = Request::create("/api/v1/bookings/$id", 'DELETE', ['external_revision' => 2]);
        $response = app(BookingController::class)->destroy($request, $stale);
        $this->assertSame('Booking already deleted', $response->getData(true)['message']);
    }

    public function test_documented_demo_seeding_preserves_metadata_and_can_be_republished(): void
    {
        Storage::fake('local');
        app()->detectEnvironment(fn () => 'local');
        try {
            $this->artisan('db:seed', ['--class' => ExternalBookingDemoSeeder::class, '--no-interaction' => true])->assertSuccessful();
            $this->assertDatabaseCount('bookings', 4);
            $this->assertSame(2, Booking::where('source', 'EXTERNAL')->whereNotNull('integration_id')->where('external_revision', 1)->count());
            $sample = Storage::disk('local')->json('external-booking-demo.json');
            $this->withToken($sample['token'])->postJson('/api/v1/bookings/create', $sample['payload'])->assertOk();
            $this->assertDatabaseCount('bookings', 4);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }
}
