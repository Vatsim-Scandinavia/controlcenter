<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\TrainingExamination;
use Faker\Generator as Faker;

$factory->define(TrainingExamination::class, function (Faker $faker) {

    $training = App\Training::inRandomOrder()->first();
    $examiner = App\User::where('id', '!=', $training->user_id)->inRandomOrder()->first();

    return [
        'examination_date' => now()->format('Y-m-d'),
        'examiner_id' => $examiner->id,
        'training_id' => $training->id,
        'position_id' => \App\Position::inRandomOrder()->first()->id,
        'result' => 'PASSED'
    ];
});
