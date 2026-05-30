<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition(): array
    {
        return [
            'submitter_user_id' => User::factory(),
            'reference_user_id' => User::factory(),
            'reference_position_id' => Position::factory(),
            'feedback' => $this->faker->paragraph(),
            'forwarded' => false,
        ];
    }

    public function uncorrelated(): static
    {
        return $this->state(['reference_position_id' => null]);
    }
}
