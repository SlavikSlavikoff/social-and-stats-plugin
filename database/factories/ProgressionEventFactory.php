<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionEvent;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressionEvent>
 */
class ProgressionEventFactory extends Factory
{
    protected $model = ProgressionEvent::class;

    public function definition(): array
    {
        return [
            'rating_id' => ProgressionRating::factory(),
            'user_id' => User::factory(),
            'rule_id' => null,
            'source' => 'factory',
            'amount' => $this->faker->numberBetween(-25, 25),
            'value_before' => $this->faker->numberBetween(-50, 50),
            'value_after' => $this->faker->numberBetween(-50, 50),
            'payload' => [
                'id' => $this->faker->uuid(),
            ],
            'triggered_at' => now(),
        ];
    }
}
