<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\Booking;
use App\Models\Position;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingSessionsDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function dashboard_shows_the_next_tagged_training_booking_for_each_student(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');

        $area = Area::factory()->create();
        $mentor = User::factory()->create();
        $student = User::factory()->create();
        $position = Position::factory()->for($area)->create([
            'callsign' => 'EKCH_TWR',
            'name' => 'Copenhagen Tower',
        ]);
        $laterPosition = Position::factory()->for($area)->create([
            'callsign' => 'EKDK_CTR',
            'name' => 'Copenhagen Control',
        ]);
        $training = Training::factory()->for($student)->for($area)->create([
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);

        $this->createBooking($student, $position, now()->subDay(), true);
        $this->createBooking($student, $laterPosition, now()->addDays(3), true);
        $nextBooking = $this->createBooking($student, $position, now()->addDay(), true);

        $response = $this->actingAs($mentor)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Next Session')
            ->assertSee($nextBooking->time_start->toEuropeanDate(true))
            ->assertSee($nextBooking->time_start->toEuropeanTime())
            ->assertSee('EKCH_TWR')
            ->assertDontSee('EKDK_CTR');
    }

    #[Test]
    public function training_page_lists_only_non_deleted_bookings_with_the_training_tag(): void
    {
        $area = Area::factory()->create();
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $training = Training::factory()->for($student)->for($area)->create([
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);
        $firstPosition = Position::factory()->for($area)->create([
            'callsign' => 'FIRST_TWR',
            'name' => 'First Tower',
        ]);
        $secondPosition = Position::factory()->for($area)->create([
            'callsign' => 'SECOND_APP',
            'name' => 'Second Approach',
        ]);
        $untaggedPosition = Position::factory()->for($area)->create(['callsign' => 'UNTAGGED']);
        $deletedPosition = Position::factory()->for($area)->create(['callsign' => 'DELETED']);
        $otherPosition = Position::factory()->for($area)->create(['callsign' => 'OTHER_TWR']);

        $this->createBooking($student, $secondPosition, now()->addDays(2), true);
        $this->createBooking($student, $firstPosition, now()->subDay(), true);
        $this->createBooking($student, $untaggedPosition, now()->addDay(), false);
        $this->createBooking($student, $deletedPosition, now()->addDay(), true, true);
        $this->createBooking($otherStudent, $otherPosition, now()->addDay(), true);

        $response = $this->actingAs($student)->get(route('training.show', $training));

        $response->assertOk()
            ->assertSee('Training Sessions')
            ->assertSeeInOrder(['FIRST_TWR', 'SECOND_APP'])
            ->assertDontSee('First Tower')
            ->assertDontSee('Second Approach')
            ->assertDontSee('UNTAGGED')
            ->assertDontSee('DELETED')
            ->assertDontSee('OTHER_TWR');
    }

    #[Test]
    public function dashboard_keeps_showing_a_session_until_it_has_ended(): void
    {
        Carbon::setTestNow('2026-08-14 12:30:00');

        $area = Area::factory()->create();
        $student = User::factory()->create();
        $mentor = $this->createMentorFor($this->createTraining($student, $area));
        $runningPosition = Position::factory()->for($area)->create(['callsign' => 'RUNNING_TWR']);
        $laterPosition = Position::factory()->for($area)->create(['callsign' => 'LATER_TWR']);

        $this->createBooking($student, $runningPosition, now()->subMinutes(30), true);
        $this->createBooking($student, $laterPosition, now()->addDay(), true);

        $this->actingAs($mentor)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('RUNNING_TWR')
            ->assertDontSee('LATER_TWR')
            ->assertDontSee('Not booked');
    }

    #[Test]
    public function dashboard_does_not_show_a_session_that_has_ended(): void
    {
        Carbon::setTestNow('2026-08-14 12:30:00');

        $area = Area::factory()->create();
        $student = User::factory()->create();
        $mentor = $this->createMentorFor($this->createTraining($student, $area));
        $position = Position::factory()->for($area)->create(['callsign' => 'ENDED_TWR']);

        $this->createBooking($student, $position, now()->subHours(3), true);

        $this->actingAs($mentor)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Not booked')
            ->assertDontSee('ENDED_TWR');
    }

    #[Test]
    public function dashboard_does_not_show_a_session_from_another_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $student = User::factory()->create();
        $mentor = $this->createMentorFor($this->createTraining($student, $area));
        $otherAreaPosition = Position::factory()->for($otherArea)->create(['callsign' => 'OTHER_AREA']);

        $this->createBooking($student, $otherAreaPosition, now()->addDay(), true);

        $this->actingAs($mentor)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Not booked')
            ->assertDontSee('OTHER_AREA');
    }

    #[Test]
    public function training_page_does_not_list_sessions_from_another_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $student = User::factory()->create();
        $training = $this->createTraining($student, $area);
        $areaPosition = Position::factory()->for($area)->create(['callsign' => 'AREA_TWR']);
        $otherAreaPosition = Position::factory()->for($otherArea)->create(['callsign' => 'OTHER_AREA']);

        $this->createBooking($student, $areaPosition, now()->addDay(), true);
        $this->createBooking($student, $otherAreaPosition, now()->addDays(2), true);

        $this->actingAs($student)->get(route('training.show', $training))
            ->assertOk()
            ->assertSee('AREA_TWR')
            ->assertDontSee('OTHER_AREA');
    }

    #[Test]
    public function closed_training_page_does_not_list_sessions_of_a_newer_training(): void
    {
        $area = Area::factory()->create();
        $student = User::factory()->create();
        $completedTraining = $this->createTraining($student, $area, TrainingStatus::COMPLETED);
        $newerTraining = $this->createTraining($student, $area);
        $position = Position::factory()->for($area)->create(['callsign' => 'NEWER_TWR']);

        $this->createBooking($student, $position, now()->addDay(), true);

        $this->actingAs($student)->get(route('training.show', $completedTraining))
            ->assertOk()
            ->assertSee('No training sessions booked')
            ->assertDontSee('NEWER_TWR');

        $this->actingAs($student)->get(route('training.show', $newerTraining))
            ->assertOk()
            ->assertSee('NEWER_TWR');
    }

    #[Test]
    public function queued_training_has_no_booked_sessions(): void
    {
        $area = Area::factory()->create();
        $student = User::factory()->create();
        $training = $this->createTraining($student, $area, TrainingStatus::IN_QUEUE);
        $position = Position::factory()->for($area)->create();

        $this->createBooking($student, $position, now()->addDay(), true);

        $this->assertTrue($training->bookedSessions()->isEmpty());
    }

    #[Test]
    public function booking_exposes_its_times_as_carbon_instances_without_casting_the_columns(): void
    {
        $area = Area::factory()->create();
        Position::factory()->for($area)->create();
        $booking = Booking::factory()->create([
            'time_start' => '2026-08-15 14:00:00',
            'time_end' => '2026-08-15 16:00:00',
        ])->fresh();

        $this->assertInstanceOf(Carbon::class, $booking->starts_at);
        $this->assertInstanceOf(Carbon::class, $booking->ends_at);
        $this->assertSame('2026-08-15 14:00:00', $booking->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-15 16:00:00', $booking->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-15 14:00:00', $booking->time_start);
        $this->assertSame('2026-08-15 16:00:00', $booking->time_end);
    }

    private function createTraining(User $student, Area $area, TrainingStatus $status = TrainingStatus::ACTIVE_TRAINING): Training
    {
        return Training::factory()->for($student)->for($area)->create(['status' => $status]);
    }

    private function createMentorFor(Training $training): User
    {
        $mentor = User::factory()->create();

        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area_id]);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);

        return $mentor;
    }

    private function createBooking(
        User $user,
        Position $position,
        Carbon $start,
        bool $training,
        bool $deleted = false,
    ): Booking {
        return Booking::factory()->create([
            'callsign' => $position->callsign,
            'position_id' => $position->id,
            'name' => $user->name,
            'user_id' => $user->id,
            'time_start' => $start,
            'time_end' => $start->copy()->addHours(2),
            'training' => $training,
            'deleted' => $deleted,
        ]);
    }
}
