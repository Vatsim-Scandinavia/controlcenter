<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\TrainingReport;
use App\Models\User;
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
        $isDraft = (bool) $this->faker->numberBetween(0, 1);

        return [
            'written_by_id' => User::factory(),
            'report_date' => $date->format('Y-M-d'),
            'content' => $this->faker->paragraph(),
            'contentimprove' => $this->faker->paragraph(),
            'position' => Position::inRandomOrder()->first()->callsign,
            'draft' => $isDraft,
            'created_at' => $date,
            'updated_at' => $date,
            'published_at' => $isDraft ? null : $date,
        ];
    }
}
