<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Position;
use App\Models\Sweatbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SweatbookTest extends TestCase
{
    use RefreshDatabase;

    private function createMentor(): User
    {
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => Area::factory()->create()->id]);

        return $mentor;
    }

    #[Test]
    public function mentor_can_create_sweatbook_booking(): void
    {
        $mentor = $this->createMentor();
        $position = Position::factory()->create();
        $startDate = Carbon::tomorrow()->setHour(10);

        $response = $this->actingAs($mentor)->post('/sweatbook/store', [
            'date' => $startDate->format('d/m/Y'),
            'start_at' => $startDate->format('H:i'),
            'end_at' => $startDate->copy()->addHours(2)->format('H:i'),
            'position' => $position->callsign,
        ]);

        $response->assertRedirect('/sweatbook');
        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Sweatbook::count());
    }

    #[Test]
    public function sweatbook_booking_requires_a_valid_duration(): void
    {
        $mentor = $this->createMentor();
        $position = Position::factory()->create();
        $startDate = Carbon::tomorrow()->setHour(10);

        $response = $this->actingAs($mentor)->from('/sweatbook')->post('/sweatbook/store', [
            'date' => $startDate->format('d/m/Y'),
            'start_at' => $startDate->format('H:i'),
            'end_at' => $startDate->format('H:i'),
            'position' => $position->callsign,
        ]);

        $response->assertInvalid();
        $this->assertEquals(0, Sweatbook::count());
    }

    private function createSweatbook(Position $position, Carbon $startAt, Carbon $endAt): Sweatbook
    {
        $booking = new Sweatbook();
        $booking->user_id = User::factory()->create()->id;
        $booking->position_id = $position->id;
        $booking->time_start = $startAt;
        $booking->time_end = $endAt;
        $booking->save();

        return $booking;
    }

    #[Test]
    public function index_and_show_display_the_booking_date_and_times(): void
    {
        $mentor = $this->createMentor();
        $position = Position::factory()->create();
        $booking = $this->createSweatbook($position, Carbon::tomorrow()->setHour(9)->setMinute(30), Carbon::tomorrow()->setHour(11));
        $booking->user_id = $mentor->id;
        $booking->save();

        $this->actingAs($mentor)->get('/sweatbook')
            ->assertSuccessful()
            ->assertSee(Carbon::tomorrow()->format('D. d/m/Y'))
            ->assertSee('09:30z')
            ->assertSee('11:00z')
            ->assertSee($position->callsign);

        $this->actingAs($mentor)->get('/sweatbook/' . $booking->id)
            ->assertSuccessful()
            ->assertSee('value="09:30"', false)
            ->assertSee('value="11:00"', false)
            ->assertSee(Carbon::tomorrow()->format('d/m/Y'));
    }

    #[Test]
    public function clean_command_removes_only_past_sweatbook_bookings(): void
    {
        $position = Position::factory()->create();

        $this->createSweatbook($position, Carbon::yesterday()->setHour(10), Carbon::yesterday()->setHour(12));
        $upcoming = $this->createSweatbook($position, Carbon::tomorrow()->setHour(10), Carbon::tomorrow()->setHour(12));

        $this->artisan('clean:sweatbooks')->assertSuccessful();

        $this->assertEquals([$upcoming->id], Sweatbook::pluck('id')->all());
    }

    #[Test]
    public function overlapping_sweatbook_booking_is_rejected(): void
    {
        $mentor = $this->createMentor();
        $position = Position::factory()->create();
        $startDate = Carbon::tomorrow()->setHour(10);

        $bookingRequest = [
            'date' => $startDate->format('d/m/Y'),
            'start_at' => $startDate->format('H:i'),
            'end_at' => $startDate->copy()->addHours(2)->format('H:i'),
            'position' => $position->callsign,
        ];

        $this->actingAs($mentor)->post('/sweatbook/store', $bookingRequest)->assertSessionHasNoErrors();
        $response = $this->actingAs($mentor)->from('/sweatbook')->post('/sweatbook/store', $bookingRequest);

        $response->assertInvalid();
        $this->assertEquals(1, Sweatbook::count());
    }
}
