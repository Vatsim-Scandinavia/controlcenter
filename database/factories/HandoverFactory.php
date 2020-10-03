<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Handover;
use Faker\Generator as Faker;

$factory->define(Handover::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->email,
        'rating' => $faker->numberBetween(1, 12),
        'rating_short' => 'ADM',
        'rating_long' => 'ADM',
        'pilot_rating' => $faker->randomElement([
            0, 1, 3, 7, 15,
        ]),
        'country' => 'NO',
        'region' => 'EMEA',
        'subdivision' => 'SCA',
        'accepted_privacy' => false,
    ];
});
