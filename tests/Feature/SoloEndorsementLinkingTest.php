<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Training;
use App\Models\User;
use App\Services\SoloEndorsementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreatesRoleAssignedUsers;
use Tests\TestCase;

/**
 * End-to-end cover for the counter: issuing a solo through the endorsement form
 * must make the counter appear on the training it was issued for, and only that
 * one, for a student with several open trainings.
 */
class SoloEndorsementLinkingTest extends TestCase
{
    use CreatesRoleAssignedUsers;
    use RefreshDatabase;

    #[Test]
    public function the_counter_appears_on_the_training_the_solo_was_issued_for()
    {
        // Issued after noon, so a 20 day solo expiring at 12:00 counts 20 days.
        $this->travelTo(Carbon::parse('2026-01-05 14:00'));

        $student = User::factory()->create();

        $norway = Area::factory()->create(['name' => 'Norway']);
        $sweden = Area::factory()->create(['name' => 'Sweden']);

        // Created first, so it is the training an area-blind lookup would pick.
        $norwegianTraining = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => $norway->id,
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $swedishTraining = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => $sweden->id,
            'status' => TrainingStatus::AWAITING_EXAM,
        ]);

        $this->issueSolo($student, Position::factory()->create(['area_id' => $sweden->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Endorsement::where('type', 'SOLO')->count());

        $service = app(SoloEndorsementService::class);

        $summary = $service->summaryFor($swedishTraining->refresh());
        $this->assertNotNull($summary, 'The counter should show on the training the solo was issued for.');
        $this->assertSame(20, $summary['used']);

        $this->assertNull(
            $service->summaryFor($norwegianTraining->refresh()),
            'The counter must not show on an unrelated training in another area.'
        );
    }

    #[Test]
    public function a_solo_is_refused_when_the_student_has_no_training_in_the_positions_area()
    {
        $student = User::factory()->create();

        Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => Area::factory()->create(['name' => 'Norway'])->id,
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $sweden = Area::factory()->create(['name' => 'Sweden']);

        $this->issueSolo($student, Position::factory()->create(['area_id' => $sweden->id]))
            ->assertRedirect();

        $this->assertStringContainsString('no active training in Sweden', session('errors')->first());

        $this->assertSame(0, Endorsement::where('type', 'SOLO')->count());
    }

    #[Test]
    public function a_solo_is_refused_while_the_training_is_in_pre_training()
    {
        $student = User::factory()->create();
        $area = Area::factory()->create();

        Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => $area->id,
            'status' => TrainingStatus::PRE_TRAINING,
        ]);

        $this->issueSolo($student, Position::factory()->create(['area_id' => $area->id]))
            ->assertRedirect();

        $this->assertStringContainsString('no active training', session('errors')->first());
        $this->assertSame(0, Endorsement::where('type', 'SOLO')->count());
    }

    #[Test]
    public function a_position_without_an_area_falls_back_to_the_students_active_training()
    {
        $student = User::factory()->create();

        $training = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => Area::factory()->create()->id,
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $this->issueSolo($student, Position::factory()->create(['area_id' => null]))
            ->assertSessionHasNoErrors();

        $this->assertNotNull(
            app(SoloEndorsementService::class)->summaryFor($training->refresh()),
            'A position with no area should still land the solo on the single active training.'
        );
    }

    #[Test]
    public function the_counter_renders_on_that_trainings_page()
    {
        $student = User::factory()->create();
        $area = Area::factory()->create();

        $training = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => $area->id,
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $this->issueSolo($student, Position::factory()->create(['area_id' => $area->id]))
            ->assertSessionHasNoErrors();

        $this->get(route('training.show', $training))
            ->assertOk()
            ->assertSee('Solo Endorsements')
            ->assertSee('Solo days remaining:');
    }

    /**
     * Issue a solo endorsement for a position through the endorsement form, as
     * an admin would.
     */
    private function issueSolo(User $student, Position $position): TestResponse
    {
        return $this->actingAs($this->admin())->post(route('endorsements.store'), [
            'endorsementType' => 'SOLO',
            'user' => $student->id,
            'position' => $position->callsign,
            'expires' => Carbon::today()->addDays(20)->format('d/m/Y'),
        ]);
    }
}
