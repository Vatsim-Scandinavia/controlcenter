<?php

namespace Tests\Unit;

use App\Contracts\VatsimBookingApiContract;
use App\Exceptions\VatsimAPIException;
use App\Facades\VatsimBookingApi;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use App\Services\VatsimBooking\Api;
use App\Services\VatsimBooking\NoOpApi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VatsimBookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'vatsim.booking_api_url' => 'https://booking-api.test',
            'vatsim.booking_api_token' => 'test-token',
        ]);
    }

    private function makeBooking(): Booking
    {
        $position = Position::factory()->create();
        $user = User::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);

        $booking = new Booking();
        $booking->callsign = $position->callsign;
        $booking->position_id = $position->id;
        $booking->name = $user->name;
        $booking->user_id = $user->id;
        $booking->time_start = $start;
        $booking->time_end = $start->copy()->addHours(2);

        return $booking;
    }

    #[Test]
    public function noop_service_is_bound_outside_production(): void
    {
        $this->assertInstanceOf(NoOpApi::class, app(VatsimBookingApiContract::class));
    }

    #[Test]
    public function noop_service_sends_no_requests_and_returns_null(): void
    {
        Http::fake();

        $this->assertNull(VatsimBookingApi::createBooking($this->makeBooking(), 'booking'));

        Http::assertNothingSent();
    }

    #[Test]
    public function create_booking_posts_to_the_vatsim_api_and_returns_the_booking_id(): void
    {
        Http::fake([
            'booking-api.test/booking' => Http::response(['id' => 1337]),
        ]);

        $booking = $this->makeBooking();
        $vatsimBookingId = (new Api())->createBooking($booking, 'training');

        $this->assertSame(1337, $vatsimBookingId);
        Http::assertSent(function ($request) use ($booking) {
            return $request->url() === 'https://booking-api.test/booking'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['callsign'] === $booking->callsign
                && $request['cid'] == $booking->user_id
                && $request['type'] === 'training'
                && $request['start'] === $booking->time_start->format('Y-m-d H:i:s')
                && $request['end'] === $booking->time_end->format('Y-m-d H:i:s');
        });
    }

    #[Test]
    public function update_booking_puts_to_the_vatsim_api_and_returns_the_booking_id(): void
    {
        Http::fake([
            'booking-api.test/booking/42' => Http::response(['id' => 42]),
        ]);

        $booking = $this->makeBooking();
        $booking->vatsim_booking = 42;
        $vatsimBookingId = (new Api())->updateBooking($booking, 'booking');

        $this->assertSame(42, $vatsimBookingId);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://booking-api.test/booking/42'
                && $request->method() === 'PUT';
        });
    }

    #[Test]
    public function delete_booking_sends_a_delete_request(): void
    {
        Http::fake([
            'booking-api.test/booking/42' => Http::response(),
        ]);

        $booking = $this->makeBooking();
        $booking->vatsim_booking = 42;
        (new Api())->deleteBooking($booking);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://booking-api.test/booking/42'
                && $request->method() === 'DELETE';
        });
    }

    #[Test]
    public function failed_request_throws_a_vatsim_api_exception(): void
    {
        Http::fake([
            'booking-api.test/*' => Http::response(['error' => 'nope'], 422),
        ]);

        $this->expectException(VatsimAPIException::class);

        (new Api())->createBooking($this->makeBooking(), 'booking');
    }
}
