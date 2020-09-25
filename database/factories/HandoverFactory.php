<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Handover;
use Faker\Generator as Faker;

$factory->define(Handover::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName
    ];
});
