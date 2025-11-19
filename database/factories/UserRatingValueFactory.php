<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\UserRatingValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRatingValue>
 */
class UserRatingValueFactory extends Factory
{
    protected $model = UserRatingValue::class;

    public function definition(): array
    {
        return [
            'rating_id' => ProgressionRating::factory(),
            'user_id' => User::factory(),
            'value' => $this->faker->numberBetween(-200, 200),
            'meta' => [
                'last_source' => $this->faker->word(),
            ],
        ];
    }
}
