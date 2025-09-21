<?php

namespace Database\Factories;

use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition()
    {
        $ratings = collect(VatsimRating::cases())->filter(function ($rating) {
            return !in_array($rating, VatsimRating::NOT_POSITION_RATINGS);
        });

        return [
            'callsign' => $this->faker->unique()->lexify('????_???'),
            'name' => $this->faker->word(),
            'frequency' => round($this->faker->randomFloat(3, 118, 136), 3),
            'fir' => strtoupper($this->faker->lexify('????')),
            'rating' => $ratings->random()->value,
            'area_id' => Area::factory(),
        ];
    }
}
