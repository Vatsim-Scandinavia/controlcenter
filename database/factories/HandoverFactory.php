<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Handover;
use Faker\Generator as Faker;
use App\Helpers\FactoryHelper;

$factory->define(Handover::class, function (Faker $faker) {

    // get random region
    $region = FactoryHelper::region();

    // get random division depending on region
    $division = FactoryHelper::division($region);

    // division id
    $divisionId = $division[0];

    // are subdivision allowed?
    $subdivisionAllowed = $division[1];

    // if allowed get random subdivision depending on division else null
    $subdivision = ($subdivisionAllowed) ? FactoryHelper::subdivision($divisionId) : null;

    // users rating id

    $rating = $faker->numberBetween(1, 12);

    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->email,
        'rating' => $rating,
        'rating_short' => FactoryHelper::shortRating($rating),
        'rating_long' => FactoryHelper::longRating($rating),
        'pilot_rating' => $faker->randomElement([
            0, 1, 3, 7, 15,
        ]),
        'country' => 'NO',
        'region' => $region,
        'division' => $divisionId,
        'subdivision' => $subdivision,
        'accepted_privacy' => false,
    ];
});