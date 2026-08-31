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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end cover for the counter: issuing a solo through the endorsement form
 * must make the counter appear on the training it was issued for, and only that
 * one, for a student with several open trainings.
 */
class SoloEndorsementLinkingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_counter_appears_on_the_training_the_solo_was_issued_for()
    {
        $student = User::factory()->create();

        $norway = Area::factory()->create(['name' => 'Norway']);
        $sweden = Area::factory()->create(['name' => 'Sweden']);

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

        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->actingAs($admin)->post(route('endorsements.store'), [
            'endorsementType' => 'SOLO',
            'user' => $student->id,
            'position' => Position::factory()->create(['area_id' => $sweden->id])->callsign,
            'expires' => Carbon::today()->addDays(20)->format('d/m/Y'),
        ])->assertSessionHasNoErrors();

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
    public function the_counter_renders_on_that_trainings_page()
    {
        $student = User::factory()->create();
        $area = Area::factory()->create();

        $training = Training::factory()->create([
            'user_id' => $student->id,
            'area_id' => $area->id,
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->actingAs($admin)->post(route('endorsements.store'), [
            'endorsementType' => 'SOLO',
            'user' => $student->id,
            'position' => Position::factory()->create(['area_id' => $area->id])->callsign,
            'expires' => Carbon::today()->addDays(20)->format('d/m/Y'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('training.show', $training))
            ->assertOk()
            ->assertSee('Solo Endorsements')
            ->assertSee('Solo days remaining:');
    }
}
