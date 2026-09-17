<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A solo endorsement is issued for a position, so the activity entry recording
 * it must land on the training covering that position's area.
 */
class SoloEndorsementTrainingLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
    }

    private function issueSolo(Position $position): void
    {
        $this->actingAs($this->admin)->post(route('endorsements.store'), [
            'endorsementType' => 'SOLO',
            'user' => $this->student->id,
            'position' => $position->callsign,
            'expires' => Carbon::today()->addDays(20)->format('d/m/Y'),
        ])->assertSessionHasNoErrors();
    }

    private function trainingIn(Area $area, TrainingStatus $status): Training
    {
        return Training::factory()->create([
            'user_id' => $this->student->id,
            'area_id' => $area->id,
            'status' => $status,
        ]);
    }

    #[Test]
    public function the_endorsement_is_recorded_against_the_training_for_the_positions_area()
    {
        $norway = Area::factory()->create(['name' => 'Norway']);
        $sweden = Area::factory()->create(['name' => 'Sweden']);

        // Created first, so the old "first open training" rule would pick this one.
        $norwegianTraining = $this->trainingIn($norway, TrainingStatus::ACTIVE_TRAINING);
        $swedishTraining = $this->trainingIn($sweden, TrainingStatus::AWAITING_EXAM);

        $this->issueSolo(Position::factory()->create(['area_id' => $sweden->id]));

        $activity = TrainingActivity::where('type', 'ENDORSEMENT')->first();

        $this->assertNotNull($activity);
        $this->assertSame($swedishTraining->id, $activity->training_id);
        $this->assertNotSame($norwegianTraining->id, $activity->training_id);
    }

    #[Test]
    public function a_closed_training_in_the_positions_area_is_not_used()
    {
        $area = Area::factory()->create();

        $closed = $this->trainingIn($area, TrainingStatus::COMPLETED);
        $open = $this->trainingIn($area, TrainingStatus::ACTIVE_TRAINING);

        $this->issueSolo(Position::factory()->create(['area_id' => $area->id]));

        $activity = TrainingActivity::where('type', 'ENDORSEMENT')->first();

        $this->assertSame($open->id, $activity->training_id);
        $this->assertNotSame($closed->id, $activity->training_id);
    }

    #[Test]
    public function the_training_nearest_its_exam_wins_when_several_are_open_in_the_area()
    {
        $area = Area::factory()->create();

        $earlier = $this->trainingIn($area, TrainingStatus::PRE_TRAINING);
        $nearerExam = $this->trainingIn($area, TrainingStatus::AWAITING_EXAM);

        $this->issueSolo(Position::factory()->create(['area_id' => $area->id]));

        $activity = TrainingActivity::where('type', 'ENDORSEMENT')->first();

        $this->assertSame($nearerExam->id, $activity->training_id);
        $this->assertNotSame($earlier->id, $activity->training_id);
    }

    #[Test]
    public function it_falls_back_to_an_open_training_when_none_covers_the_positions_area()
    {
        $trainingArea = Area::factory()->create();
        $otherArea = Area::factory()->create();

        $training = $this->trainingIn($trainingArea, TrainingStatus::ACTIVE_TRAINING);

        $this->issueSolo(Position::factory()->create(['area_id' => $otherArea->id]));

        $activity = TrainingActivity::where('type', 'ENDORSEMENT')->first();

        $this->assertNotNull($activity, 'The endorsement should still be recorded somewhere.');
        $this->assertSame($training->id, $activity->training_id);
    }

    #[Test]
    public function the_endorsement_is_still_created_when_it_cannot_be_linked()
    {
        $area = Area::factory()->create();
        $this->trainingIn($area, TrainingStatus::ACTIVE_TRAINING);

        $this->issueSolo(Position::factory()->create(['area_id' => $area->id]));

        $this->assertSame(1, Endorsement::where('type', 'SOLO')->count());
    }
}
