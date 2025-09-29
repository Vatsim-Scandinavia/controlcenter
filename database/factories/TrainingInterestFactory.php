<?php

namespace Database\Factories;

use App\Models\Training;
use App\Models\TrainingInterest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TrainingInterestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TrainingInterest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'key' => Str::random(16),
            'deadline' => now()->addDays(7),
        ];
    }
}
