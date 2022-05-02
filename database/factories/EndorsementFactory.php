<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EndorsementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Endorsement::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'type' => "MASC",
            'valid_from' => Carbon::now()
        ];
    }
}
