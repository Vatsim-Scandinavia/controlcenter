<?php

namespace Database\Factories;

use App\Models\MoodleCourse;
use App\Models\MoodleEnrolment;
use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoodleEnrolment>
 */
class MoodleEnrolmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'moodle_course_id' => MoodleCourse::factory(),
            'moodle_user_link_id' => null,
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'enrolled_at' => null,
        ];
    }
}
