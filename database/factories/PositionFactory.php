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
     */
    protected $model = Position::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'callsign' => strtoupper($this->faker->unique()->text(7)),
            'name' => $this->faker->word(),
            'frequency' => sprintf(
                '%03d.%03d',
                $this->faker->numberBetween(118, 136),
                $this->faker->randomElement([0, 5, 10, 15, 25, 30, 35, 40, 50, 55, 60, 65, 75, 80, 85, 90])
            ),
            'fir' => strtoupper($this->faker->lexify('????')),
            'rating' => $this->faker->randomElement(VatsimRating::getControllerRatings()),
            'area_id' => Area::factory(),
        ];
    }
}
