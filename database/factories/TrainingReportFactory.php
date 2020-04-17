<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\TrainingReport;
use Faker\Generator as Faker;

$factory->define(TrainingReport::class, function (Faker $faker) {

    $training = factory(\App\Training::class)->create();
    $training->mentors()->attach($mentor = factory(\App\User::class)->create(['group' => 3]), ['expire_at' => now()->addCentury()]);

    return [
        'training_id' => $training->id,
        'written_by_id' => $mentor->id,
        'content' => $faker->paragraph()
    ];
});
