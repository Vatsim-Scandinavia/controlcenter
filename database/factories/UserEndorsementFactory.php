<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\SoloEndorsement;
use Faker\Generator as Faker;

$factory->define(SoloEndorsement::class, function (Faker $faker) {
    return [
        'position' => App\Position::inRandomOrder()->first()->callsign,
        'expires_at' => now()->addYears(5),
    ];
});
