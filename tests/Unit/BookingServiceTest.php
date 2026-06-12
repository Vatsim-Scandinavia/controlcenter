<?php

namespace Tests\Unit;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\Booking;
use App\Models\Position;
use App\Models\Rating;
use App\Models\Sweatbook;
use App\Models\Training;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingService();
    }

    private function makeBooking(Position $position, Carbon $startAt, Carbon $endAt): Booking
    {
        $user = User::factory()->create();

        $booking = new Booking();
        $booking->callsign = $position->callsign;
        $booking->position_id = $position->id;
        $booking->name = $user->name;
        $booking->user_id = $user->id;
        $booking->time_start = $startAt;
        $booking->time_end = $endAt;

        return $booking;
    }

    private function makeSweatbook(Position $position, Carbon $startAt, Carbon $endAt): Sweatbook
    {
        $booking = new Sweatbook();
        $booking->user_id = User::factory()->create()->id;
        $booking->position_id = $position->id;
        $booking->time_start = $startAt;
        $booking->time_end = $endAt;

        return $booking;
    }

    #[Test]
    public function parse_period_returns_start_and_end_on_the_given_date(): void
    {
        [$startAt, $endAt] = $this->service->parsePeriod('25/12/2026', '10:00', '12:30');

        $this->assertSame('2026-12-25 10:00', $startAt->format('Y-m-d H:i'));
        $this->assertSame('2026-12-25 12:30', $endAt->format('Y-m-d H:i'));
    }

    #[Test]
    public function adjust_for_overnight_adds_a_day_when_end_is_before_start(): void
    {
        [$startAt, $endAt] = $this->service->parsePeriod('25/12/2026', '23:00', '01:00');

        $this->service->adjustForOvernight($startAt, $endAt);

        $this->assertSame('2026-12-26 01:00', $endAt->format('Y-m-d H:i'));
    }

    #[Test]
    public function adjust_for_overnight_keeps_end_when_after_start(): void
    {
        [$startAt, $endAt] = $this->service->parsePeriod('25/12/2026', '10:00', '12:00');

        $this->service->adjustForOvernight($startAt, $endAt);

        $this->assertSame('2026-12-25 12:00', $endAt->format('Y-m-d H:i'));
    }

    #[Test]
    public function start_in_the_past_is_detected(): void
    {
        $this->assertTrue($this->service->isStartInPast(Carbon::now()->subHour()));
        $this->assertFalse($this->service->isStartInPast(Carbon::now()->addHour()));
    }

    #[Test]
    public function booking_conflict_is_detected_for_overlapping_times(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $this->makeBooking($position, $start, $start->copy()->addHours(2))->save();

        $overlapping = $this->makeBooking($position, $start->copy()->addHour(), $start->copy()->addHours(3));

        $this->assertTrue($this->service->bookingConflictExists($overlapping));
    }

    #[Test]
    public function booking_conflict_ignores_adjacent_bookings(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $this->makeBooking($position, $start, $start->copy()->addHours(2))->save();

        $adjacent = $this->makeBooking($position, $start->copy()->addHours(2), $start->copy()->addHours(4));

        $this->assertFalse($this->service->bookingConflictExists($adjacent));
    }

    #[Test]
    public function booking_conflict_ignores_other_positions(): void
    {
        $position = Position::factory()->create();
        $otherPosition = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $this->makeBooking($position, $start, $start->copy()->addHours(2))->save();

        $otherPositionBooking = $this->makeBooking($otherPosition, $start->copy()->addHour(), $start->copy()->addHours(3));

        $this->assertFalse($this->service->bookingConflictExists($otherPositionBooking));
    }

    #[Test]
    public function booking_conflict_ignores_deleted_bookings(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $deleted = $this->makeBooking($position, $start, $start->copy()->addHours(2));
        $deleted->deleted = true;
        $deleted->save();

        $overlapping = $this->makeBooking($position, $start->copy()->addHour(), $start->copy()->addHours(3));

        $this->assertFalse($this->service->bookingConflictExists($overlapping));
    }

    #[Test]
    public function booking_conflict_ignores_the_booking_itself_on_update(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $booking = $this->makeBooking($position, $start, $start->copy()->addHours(2));
        $booking->save();

        $booking->time_start = $start->copy()->addHour();
        $booking->time_end = $start->copy()->addHours(3);

        $this->assertFalse($this->service->bookingConflictExists($booking));
    }

    #[Test]
    public function sweatbook_conflict_is_detected_for_overlapping_times(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $this->makeSweatbook($position, $start, $start->copy()->addHours(2))->save();

        $overlapping = $this->makeSweatbook($position, $start->copy()->addHour(), $start->copy()->addHours(3));

        $this->assertTrue($this->service->sweatbookConflictExists($overlapping));
    }

    #[Test]
    public function sweatbook_conflict_ignores_other_positions(): void
    {
        $position = Position::factory()->create();
        $otherPosition = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $this->makeSweatbook($position, $start, $start->copy()->addHours(2))->save();

        $otherPositionBooking = $this->makeSweatbook($otherPosition, $start->copy()->addHour(), $start->copy()->addHours(3));

        $this->assertFalse($this->service->sweatbookConflictExists($otherPositionBooking));
    }

    #[Test]
    public function sweatbook_conflict_ignores_the_booking_itself_on_update(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $booking = $this->makeSweatbook($position, $start, $start->copy()->addHours(2));
        $booking->save();

        $booking->time_start = $start->copy()->addHour();
        $booking->time_end = $start->copy()->addHours(3);

        $this->assertFalse($this->service->sweatbookConflictExists($booking));
    }

    #[Test]
    public function applying_training_tag_sets_flags_and_returns_type(): void
    {
        $booking = new Booking();

        $this->assertSame('training', $this->service->applyTagToBooking($booking, 1));
        $this->assertEquals(1, $booking->training);
        $this->assertEquals(0, $booking->exam);
        $this->assertEquals(0, $booking->event);
    }

    #[Test]
    public function applying_exam_tag_sets_flags_and_returns_type(): void
    {
        $booking = new Booking();

        $this->assertSame('exam', $this->service->applyTagToBooking($booking, 2));
        $this->assertEquals(0, $booking->training);
        $this->assertEquals(1, $booking->exam);
        $this->assertEquals(0, $booking->event);
    }

    #[Test]
    public function applying_event_tag_sets_flags_and_returns_type(): void
    {
        $booking = new Booking();

        $this->assertSame('event', $this->service->applyTagToBooking($booking, 3));
        $this->assertEquals(0, $booking->training);
        $this->assertEquals(0, $booking->exam);
        $this->assertEquals(1, $booking->event);
    }

    #[Test]
    public function applying_no_tag_keeps_training_flag_untouched(): void
    {
        $booking = new Booking();
        $booking->training = 1;

        $this->assertSame('booking', $this->service->applyTagToBooking($booking, null));
        $this->assertEquals(1, $booking->training);
        $this->assertEquals(0, $booking->exam);
        $this->assertEquals(0, $booking->event);
    }

    #[Test]
    public function training_tag_is_forced_when_position_is_above_user_rating(): void
    {
        $position = Position::factory()->create(['rating' => VatsimRating::C1->value]);
        $user = User::factory()->create(['rating' => VatsimRating::S2->value]);
        $booking = $this->makeBooking($position, Carbon::tomorrow()->setHour(10), Carbon::tomorrow()->setHour(12));

        $this->assertTrue($this->service->shouldForceTrainingTag($booking, $position, $user));
    }

    #[Test]
    public function training_tag_is_not_forced_when_user_rating_is_sufficient(): void
    {
        $position = Position::factory()->create(['rating' => VatsimRating::S2->value]);
        $user = User::factory()->create(['rating' => VatsimRating::C1->value]);
        $booking = $this->makeBooking($position, Carbon::tomorrow()->setHour(10), Carbon::tomorrow()->setHour(12));

        $this->assertFalse($this->service->shouldForceTrainingTag($booking, $position, $user));
    }

    #[Test]
    public function active_bookings_exclude_deleted_and_are_sorted_by_start_time(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);

        $later = $this->makeBooking($position, $start->copy()->addHours(4), $start->copy()->addHours(6));
        $later->save();
        $earlier = $this->makeBooking($position, $start, $start->copy()->addHours(2));
        $earlier->save();
        $deleted = $this->makeBooking($position, $start->copy()->addHours(8), $start->copy()->addHours(10));
        $deleted->deleted = true;
        $deleted->save();

        $bookings = $this->service->getActiveBookings();

        $this->assertEquals([$earlier->id, $later->id], $bookings->pluck('id')->values()->all());
        $this->assertTrue($bookings->first()->relationLoaded('position'));
        $this->assertTrue($bookings->first()->relationLoaded('user'));
    }

    #[Test]
    public function sweatbook_bookings_are_sorted_by_start_time_across_dates(): void
    {
        $position = Position::factory()->create();

        $nextDayMorning = $this->makeSweatbook($position, Carbon::tomorrow()->addDay()->setHour(9), Carbon::tomorrow()->addDay()->setHour(11));
        $nextDayMorning->save();

        $evening = $this->makeSweatbook($position, Carbon::tomorrow()->setHour(18), Carbon::tomorrow()->setHour(20));
        $evening->save();

        $morning = $this->makeSweatbook($position, Carbon::tomorrow()->setHour(8), Carbon::tomorrow()->setHour(10));
        $morning->save();

        $bookings = $this->service->getActiveBookings(Sweatbook::class);

        $this->assertEquals(
            [$morning->id, $evening->id, $nextDayMorning->id],
            $bookings->pluck('id')->values()->all()
        );
        $this->assertTrue($bookings->first()->relationLoaded('position'));
        $this->assertTrue($bookings->first()->relationLoaded('user'));
    }

    #[Test]
    public function user_with_bypass_permission_can_book_all_positions(): void
    {
        Position::factory()->count(2)->create();
        $mentor = User::factory()->create(['rating' => VatsimRating::S1->value]);
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => Area::factory()->create()->id]);

        $this->assertEquals(Position::count(), $this->service->getBookablePositions($mentor)->count());
    }

    #[Test]
    public function rated_user_can_book_positions_in_active_areas_up_to_their_rating(): void
    {
        Setting::set('atcActivityBasedOnTotalHours', false);

        $area = Area::factory()->create();
        $tower = Position::factory()->create(['area_id' => $area->id, 'rating' => VatsimRating::S2->value]);
        $center = Position::factory()->create(['area_id' => $area->id, 'rating' => VatsimRating::C1->value]);
        $otherAreaTower = Position::factory()->create(['rating' => VatsimRating::S2->value]);

        $user = User::factory()->create(['rating' => VatsimRating::S2->value]);
        $user->atcActivity()->create(['user_id' => $user->id, 'area_id' => $area->id, 'hours' => 100, 'atc_active' => true]);

        $positions = $this->service->getBookablePositions($user);

        $this->assertTrue($positions->contains('id', $tower->id));
        $this->assertFalse($positions->contains('id', $center->id));
        $this->assertFalse($positions->contains('id', $otherAreaTower->id));
    }

    #[Test]
    public function rated_user_can_book_positions_in_all_areas_when_activity_is_based_on_total_hours(): void
    {
        Setting::set('atcActivityBasedOnTotalHours', true);

        $tower = Position::factory()->create(['rating' => VatsimRating::S2->value]);
        $user = User::factory()->create(['rating' => VatsimRating::S2->value]);

        $positions = $this->service->getBookablePositions($user);

        $this->assertTrue($positions->contains('id', $tower->id));
    }

    #[Test]
    public function user_below_s1_without_training_cannot_book_any_positions(): void
    {
        Position::factory()->create();
        $user = User::factory()->create(['rating' => VatsimRating::OBS->value]);

        $this->assertTrue($this->service->getBookablePositions($user)->isEmpty());
    }

    #[Test]
    public function student_in_training_can_book_training_area_positions_up_to_the_training_rating(): void
    {
        $area = Area::factory()->create();
        $tower = Position::factory()->create(['area_id' => $area->id, 'rating' => VatsimRating::S2->value]);
        $center = Position::factory()->create(['area_id' => $area->id, 'rating' => VatsimRating::C1->value]);

        $student = User::factory()->create(['rating' => VatsimRating::S1->value]);
        Training::factory()
            ->has(Rating::factory(['vatsim_rating' => VatsimRating::S2]))
            ->create(['user_id' => $student->id, 'type' => 1, 'status' => TrainingStatus::ACTIVE_TRAINING->value, 'area_id' => $area->id]);

        $positions = $this->service->getBookablePositions($student);

        $this->assertTrue($positions->contains('id', $tower->id));
        $this->assertFalse($positions->contains('id', $center->id));
    }

    #[Test]
    public function booking_activity_is_logged(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $booking = $this->makeBooking($position, $start, $start->copy()->addHours(2));
        $booking->save();

        $this->service->logBookingCreated($booking);

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'INFO',
            'category' => 'BOOKING',
            'message' => 'Created booking booking ' . $booking->id .
                ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
                ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
                ' ― Position: ' . $position->callsign,
        ]);
    }

    #[Test]
    public function sweatbook_activity_is_logged(): void
    {
        $position = Position::factory()->create();
        $start = Carbon::tomorrow()->setHour(10);
        $booking = $this->makeSweatbook($position, $start, $start->copy()->addHours(2));
        $booking->save();

        $this->service->logBookingDeleted($booking);

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'WARNING',
            'category' => 'BOOKING',
            'message' => 'Deleted sweatbox booking ' . $booking->id .
                ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
                ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
                ' ― Position: ' . $position->callsign,
        ]);
    }
}
