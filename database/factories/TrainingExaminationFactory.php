<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\TrainingExamination;
use Faker\Generator as Faker;

$factory->define(TrainingExamination::class, function (Faker $faker) {

    $training = factory(\App\Training::class)->create();
    $examiner = factory(\App\User::class)->create(['group' => 2]);

    return [
        'examination_date' => now()->format('Y-m-d'),
        'examiner_id' => $examiner->id,
        'training_id' => $training->id,
        'position_id' => \App\Position::all()->firstWhere('callsign', '=', 'EKCH_TWR')->id,
        'result' => 'PASSED'
    ];
});
