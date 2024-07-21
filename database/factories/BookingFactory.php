<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $user = User::factory()->create();
        $position = Position::whereNotNull('name')->inRandomOrder()->first();

        return [
            'callsign' => $position->name,
            'position_id' => $position->id,
            'name' => $user->name,
            'time_start' => Carbon::now()->addHours(1),
            'time_end' => Carbon::now()->addHours(2),
            'user_id' => $user->id,
        ];
    }
}
