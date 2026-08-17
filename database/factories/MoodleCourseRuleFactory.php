<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\MoodleCourse;
use App\Models\MoodleCourseRule;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoodleCourseRule>
 */
class MoodleCourseRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'moodle_course_id' => MoodleCourse::factory(),
            'area_id' => Area::factory(),
            'rating_id' => Rating::factory(),
        ];
    }
}
