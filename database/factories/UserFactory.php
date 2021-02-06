<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\Models\User;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'last_login' => \Carbon\Carbon::now(),
        'group' => null,
        'setting_notify_newreport' => false,
        'setting_notify_newreq' => false,
        'setting_notify_closedreq' => false,
        'setting_notify_newexamreport' => false,
    ];
});
