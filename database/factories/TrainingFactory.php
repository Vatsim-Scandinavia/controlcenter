<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Training;
use Faker\Generator as Faker;

$factory->define(Training::class, function (Faker $faker) {
    return [
        'user_id' => App\User::inRandomOrder()->first()->id,
        'status' => $faker->numberBetween(0, 3),
        'country_id' => 1,
        'motivation' => $faker->paragraph(15, false),
        'english_only_training' => false,
        'created_at' => \Carbon\Carbon::now(),
        'updated_at' => \Carbon\Carbon::now(),
        'type' => $faker->numberBetween(1, 5),
    ];
});
