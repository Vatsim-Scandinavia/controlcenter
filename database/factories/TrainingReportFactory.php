<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\TrainingReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TrainingReport::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date = $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now');

        return [
            'report_date' => $date->format('Y-M-d'),
            'content' => $this->faker->paragraph(),
            'contentimprove' => $this->faker->paragraph(),
            'position' => Position::inRandomOrder()->first()->callsign,
            'draft' => $this->faker->numberBetween(0, 1),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
