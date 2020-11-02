<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\TrainingExamination;
use Faker\Generator as Faker;

$factory->define(TrainingExamination::class, function (Faker $faker) {

    $date = $faker->dateTimeBetween($startDate = 'now', $endDate = '+ 1 years');

    return [
        'examination_date' => $date,
        'position_id' => \App\Position::query()->inRandomOrder()->first()->id,
        'result' => $faker->randomElement([
            'PASSED', 'FAILED', 'INCOMPLETE', 'POSTPONED',
        ]),
        'created_at' => $date,
        'updated_at' => $date,
    ];
});
