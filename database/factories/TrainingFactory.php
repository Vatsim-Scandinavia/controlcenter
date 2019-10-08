<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Training;
use Faker\Generator as Faker;

$factory->define(Training::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'status' => 0,
        'country_id' => 1,
        'motivation' => 'I love loss of separation!',
        'english_only_training' => false,
        'created_at' => \Carbon\Carbon::now(),
        'updated_at' => \Carbon\Carbon::now(),
    ];
});
