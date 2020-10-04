<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\TrainingExamination;
use Faker\Generator as Faker;

$factory->define(TrainingExamination::class, function (Faker $faker) {
    return [
        'examination_date' => now()->addYears(5),
        'position_id' => \App\Position::inRandomOrder()->first()->id,
        'result' => $faker->randomElement([
            'PASSED', 'FAILED', 'INCOMPLETE', 'POSTPONED',
        ]),
    ];
});
