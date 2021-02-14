<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'last_login' => \Carbon\Carbon::now(),
            'group' => null,
            'setting_notify_newreport' => false,
            'setting_notify_newreq' => false,
            'setting_notify_closedreq' => false,
            'setting_notify_newexamreport' => false,
        ];
    }
}
