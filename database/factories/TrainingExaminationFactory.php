<?php

namespace Database\Factories;

use App\Models\TrainingExamination;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingExaminationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TrainingExamination::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date = $this->faker->dateTimeBetween($startDate = 'now', $endDate = '+ 1 years');

        return [
            'examination_date' => $date,
            'position_id' => \App\Models\Position::query()->inRandomOrder()->first()->id,
            'result' => $this->faker->randomElement([
                'PASSED', 'FAILED', 'INCOMPLETE', 'POSTPONED',
            ]),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
