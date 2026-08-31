<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end cover for the counter: issuing a solo through the endorsement form
 * must make the counter appear on the student's open training.
 */
class SoloEndorsementLinkingTest extends TestCase
{
    use RefreshDatabase;

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
