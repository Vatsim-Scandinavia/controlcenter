<?php

namespace Tests\Feature;

use App\Helpers\TrainingStatus;
use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seeded_students_have_at_most_one_open_training(): void
    {
        $this->seed();

        $openTrainings = Training::where('status', '>=', TrainingStatus::IN_QUEUE->value)->get();

        $this->assertNotEmpty($openTrainings);
        $this->assertTrue($openTrainings->groupBy('user_id')->every(fn ($trainings) => $trainings->count() === 1));
    }
}
