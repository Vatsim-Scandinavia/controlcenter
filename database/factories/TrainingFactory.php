<?php

namespace Database\Factories;

use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Training::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = $this->faker->numberBetween(0, 3);
        $started_at = null;
        $closed_at = null;

        if ($status > 1) {
            $started_at = $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '-1 months');
        }

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'status' => $status,
            'area_id' => 1,
            'motivation' => $this->faker->paragraph(15, false),
            'english_only_training' => false,
            'created_at' => $this->faker->dateTimeBetween($startDate = '-2 years', $endDate = '-1 years'),
            'updated_at' => \Carbon\Carbon::now(),
            'type' => $this->faker->numberBetween(1, 5),
            'experience' => $this->faker->numberBetween(1, 5),
            'started_at' => $started_at,
            'closed_at' => $closed_at,
        ];
    }
}
