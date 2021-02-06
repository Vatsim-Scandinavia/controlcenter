<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Training;
use Faker\Generator as Faker;

$factory->define(Training::class, function (Faker $faker) {

    $status = $faker->numberBetween(0, 3);
    $started_at = null;
    $closed_at = null;

    if($status > 1) {
        $started_at = $faker->dateTimeBetween($startDate = '-1 years', $endDate = '-1 months');
    }

    return [
        'user_id' => App\Models\User::inRandomOrder()->first()->id,
        'status' => $status,
        'country_id' => 1,
        'motivation' => $faker->paragraph(15, false),
        'notes' => $faker->paragraph(1, false),
        'english_only_training' => false,
        'created_at' => $faker->dateTimeBetween($startDate = '-2 years', $endDate = '-1 years'),
        'updated_at' => \Carbon\Carbon::now(),
        'type' => $faker->numberBetween(1, 5),
        'experience' => $this->faker->numberBetween(1, 5),
        'started_at' => $started_at,
        'closed_at' => $closed_at
    ];
});
