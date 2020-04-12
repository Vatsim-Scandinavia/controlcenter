<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Training;
use Faker\Generator as Faker;

$factory->define(Training::class, function (Faker $faker) {
    return [
        'user_id' => factory(\App\User::class)->create(['group' => null])->id,
        'status' => 0,
        'country_id' => 1,
        'motivation' => $faker->paragraph(15, false),
        'english_only_training' => false,
        'created_at' => \Carbon\Carbon::now(),
        'updated_at' => \Carbon\Carbon::now(),
    ];
});
