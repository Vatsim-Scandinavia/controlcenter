<?php

namespace Database\Factories;

use App\Helpers\FactoryHelper;
use App\Models\Handover;
use Illuminate\Database\Eloquent\Factories\Factory;

class HandoverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Handover::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // get random region
        $region = FactoryHelper::region();

        // get random division depending on region
        $division = FactoryHelper::division($region);

        // division id
        $divisionId = $division[0];

        // are subdivision allowed?
        $subdivisionAllowed = $division[1];

        // if allowed get random subdivision depending on division else null
        $subdivision = ($subdivisionAllowed) ? FactoryHelper::subdivision($divisionId) : null;

        // users rating id

        $rating = $this->faker->numberBetween(1, 12);

        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'rating' => $rating,
            'rating_short' => FactoryHelper::shortRating($rating),
            'rating_long' => FactoryHelper::longRating($rating),
            'pilot_rating' => $this->faker->randomElement([
                0, 1, 3, 7, 15,
            ]),
            'country' => 'NO',
            'region' => $region,
            'division' => $divisionId,
            'subdivision' => $subdivision,
            'accepted_privacy' => false,
        ];
    }
}
