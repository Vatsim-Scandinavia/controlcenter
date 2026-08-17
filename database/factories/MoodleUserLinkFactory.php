<?php

namespace Database\Factories;

use App\Models\MoodleUserLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoodleUserLink>
 */
class MoodleUserLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'moodle_user_id' => fake()->unique()->numberBetween(1, 100000),
            'moodle_username' => (string) fake()->numberBetween(800000, 1999999),
            'moodle_full_name' => fake()->name(),
            'match_type' => 'automatic',
            'linked_by' => null,
            'verified_at' => now(),
        ];
    }
}
