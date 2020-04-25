<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\TrainingExamination;
use Faker\Generator as Faker;

$factory->define(TrainingExamination::class, function (Faker $faker) {

    $training = factory(\App\Training::class)->create();
    $examiner = factory(\App\User::class)->create(['group' => 2]);

    return [
        'examination_date' => now(),
        'examiner_id' => $examiner->id,
        'training_id' => $training->id,
        'position_id' => '2'
    ];
});
