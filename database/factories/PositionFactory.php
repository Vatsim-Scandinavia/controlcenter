<?php

namespace Database\Factories;

use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Position::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'callsign' => strtoupper($this->faker->unique()->text(7)),
            'name' => $this->faker->word(),
            'frequency' => sprintf('%.3f', $this->faker->randomFloat(3, 118, 136)),
            'fir' => $this->faker->lexify('????'),
            'rating' => $this->faker->randomElement(VatsimRating::getControllerRatings()),
            'area_id' => Area::factory(),
        ];
    }
}
