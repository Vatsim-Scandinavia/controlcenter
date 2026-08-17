<?php

namespace Database\Factories;

use App\Models\MoodleCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoodleCourse>
 */
class MoodleCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'moodle_id' => fake()->unique()->numberBetween(2, 100000),
            'short_name' => fake()->unique()->bothify('COURSE-###'),
            'full_name' => fake()->sentence(3),
            'enabled' => true,
            'synced_at' => now(),
        ];
    }
}
