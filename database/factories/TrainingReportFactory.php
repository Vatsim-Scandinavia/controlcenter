<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\TrainingReport;
use Faker\Generator as Faker;

$factory->define(TrainingReport::class, function (Faker $faker) {

    $date = $faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now');

    return [
        'report_date' => $date,
        'content' => $faker->paragraph(),
        'contentimprove' => $faker->paragraph(),
        'position' => App\Models\Position::inRandomOrder()->first()->callsign,
        'draft' => $faker->numberBetween(0, 1),
        'created_at' => $date,
        'updated_at' => $date,
    ];
});
