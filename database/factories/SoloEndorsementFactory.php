<?php

namespace Database\Factories;

use App\Models\SoloEndorsement;
use App\Models\User;
use App\Models\Training;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoloEndorsementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SoloEndorsement::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'training_id' => Training::inRandomOrder()->first()->id,
            'position' => Position::inRandomOrder()->first()->callsign,
        ];
    }
}
