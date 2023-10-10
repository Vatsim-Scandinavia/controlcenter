<?php

namespace Database\Factories;

use App\Helpers\FactoryHelper;
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
     */
    public function definition(): array
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),

            'rating' => $rating,
            'rating_short' => FactoryHelper::shortRating($rating),
            'rating_long' => FactoryHelper::longRating($rating),

            'region' => $region,
            'division' => $divisionId,
            'subdivision' => $subdivision,

            'last_login' => \Carbon\Carbon::now(),
            'setting_notify_newreport' => false,
            'setting_notify_newreq' => false,
            'setting_notify_closedreq' => false,
            'setting_notify_newexamreport' => false,
        ];
    }
}
