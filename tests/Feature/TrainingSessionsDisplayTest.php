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
            ->assertSee('Next Training Session')
            ->assertSee(Carbon::parse($nextBooking->time_start)->toEuropeanDate(true))
            ->assertSee(Carbon::parse($nextBooking->time_start)->toEuropeanTime())
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
            ->assertDontSee('UNTAGGED')
            ->assertDontSee('DELETED')
            ->assertDontSee('OTHER_TWR');
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
