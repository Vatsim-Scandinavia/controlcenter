<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateQueueCalculationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_records_wait_time_for_in_queue_trainings(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create an area and a rating with vatsim_rating
        $area = Area::factory()->create();
        $rating = Rating::factory()->create(['vatsim_rating' => 2]); // S1

        // Link area and rating together
        $area->ratings()->attach($rating->id);

        // Create 4 IN_QUEUE trainings in the same area, all with the same rating
        for ($i = 0; $i < 4; $i++) {
            $training = Training::factory()->create([
                'user_id' => $user->id,
                'status' => TrainingStatus::IN_QUEUE->value,
                'area_id' => $area->id,
                'created_at' => now()->subDays(10),
                'paused_at' => null,
                'paused_length' => 0,
            ]);
            // Attach rating to training
            $training->ratings()->attach($rating->id);
        }

        // Verify before running the command: queue_length_low/high should be null (or not set)
        $pivotBefore = $area->ratings()->where('rating_id', $rating->id)->first()->pivot;
        $this->assertNull($pivotBefore->queue_length_low);
        $this->assertNull($pivotBefore->queue_length_high);

        // Run the command
        $this->artisan('update:queuecalculation')->assertExitCode(0);

        // Verify that queue_length_low and queue_length_high are now recorded (non-null and > 0)
        $pivotAfter = $area->ratings()->where('rating_id', $rating->id)->first()->pivot;
        $this->assertNotNull($pivotAfter->queue_length_low, 'queue_length_low should be recorded for 4 IN_QUEUE trainings');
        $this->assertNotNull($pivotAfter->queue_length_high, 'queue_length_high should be recorded for 4 IN_QUEUE trainings');
        $this->assertGreaterThan(0, $pivotAfter->queue_length_low, 'queue_length_low should be > 0');
        $this->assertGreaterThan(0, $pivotAfter->queue_length_high, 'queue_length_high should be > 0');
    }
}
