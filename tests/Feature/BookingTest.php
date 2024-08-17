<?php

namespace Tests\Feature;

use App\Helpers\VatsimRating;
use App\Models\Booking;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function assertCreateBookingAvailable(User $controller)
    {
        $checkBrowser = $this->actingAs($controller)->followingRedirects()
            ->get(route('booking'));

        $randomPosition = Position::whereRating($controller->rating)->whereNotNull('name')->inRandomOrder()->first();
        $checkBrowser->assertSee($randomPosition->name);
        $checkBrowser->assertSeeText('Create Booking');
    }

    private function createBooking(User $controller)
    {
        $lastBooking = Booking::all()->last();

        $startDate = new Carbon(fake()->dateTimeBetween('tomorrow', '+2 months'));
        $endDate = $startDate->copy()->addHours(2)->addMinutes(30);
        $bookingRequest = [
            'date' => $startDate->format('d/m/Y'),
            'start_at' => $startDate->format('H:i'),
            'end_at' => $endDate->format('H:i'),
            'position' => Position::whereRating($controller->rating)->whereNotNull('name')->inRandomOrder()->first()->callsign,
        ];
        $response = $this->actingAs($controller)->followingRedirects()->post(
            '/booking/store',
            $bookingRequest
        );

        $this->assertNotSame($lastBooking, Booking::all()->last());

        return $response;
    }

    /**
     * Validate that a controller can create a booking.
     * The data provider underneath is used by PHPUnit fill the arguments of the test.
     */
    #[DataProvider('controllerProvider')]
    public function test_active_ratings_can_create_bookings(VatsimRating $rating, callable $setup): void
    {
        $controller = User::factory()->create([
            'id' => fake()->numberBetween(100),
            'rating' => $rating->value,
        ]);

        $controller->atcActivity()->create([
            'user_id' => $controller->id,
            'area_id' => 1,
            'hours' => 100,
            'atc_active' => true,
        ]);

        $setup($controller);
        $this->assertCreateBookingAvailable($controller);
        $this->createBooking($controller)->assertValid();
    }

    #[Test]
    public function inactive_cant_book_positions(): void
    {

        $user = User::factory()->create(['id' => 10000001]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->followingRedirects()->postJson(route('booking.store', ['id' => $booking->id]), $booking->getAttributes())
            ->assertStatus(403);
    }

    #[Test]
    public function test_discord_bookings_can_not_be_deleted(): void
    {

        $user = User::factory()->create(['id' => 10000001]);
        $booking = Booking::factory()->create(['user_id' => $user->id, 'source' => 'DISCORD']);

        $this->actingAs($user)->followingRedirects()->get(route('booking.delete', ['id' => $booking->id]))
            ->assertStatus(403);
    }

    /**
     * Provides a list of controllers to feed to the booking test.
     */
    public static function controllerProvider(): array
    {
        return [
            'S1 Rating with endorsement' => [
                VatsimRating::S1,
                function ($user) {
                    Endorsement::factory()->create(
                        ['user_id' => $user->id, 'type' => 'S1', 'valid_to' => null]
                    );
                },
            ],
            'S2 Rating' => [
                VatsimRating::S2,
                function () {},
            ],
            'S3 Rating' => [
                VatsimRating::S3,
                function () {},
            ],
            'C1 Rating' => [
                VatsimRating::C1,
                function () {},
            ],
        ];
    }
}
