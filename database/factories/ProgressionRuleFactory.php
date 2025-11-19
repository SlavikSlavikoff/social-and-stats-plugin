<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressionRule>
 */
class ProgressionRuleFactory extends Factory
{
    protected $model = ProgressionRule::class;

    public function definition(): array
    {
        return [
            'rating_id' => ProgressionRating::factory(),
            'name' => $this->faker->words(3, true),
            'trigger_key' => 'activity.changed',
            'source_type' => ProgressionRule::SOURCE_INTERNAL,
            'conditions' => [
                [
                    'field' => 'delta',
                    'operator' => '>=',
                    'value' => 10,
                ],
            ],
            'options' => [
                'cooldown_key' => null,
            ],
            'delta' => 5,
            'is_active' => true,
            'cooldown_seconds' => null,
        ];
    }

    public function negative(): self
    {
        return $this->state(fn () => ['delta' => -5]);
    }
}
