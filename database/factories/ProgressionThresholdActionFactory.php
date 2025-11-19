<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThresholdAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressionThresholdAction>
 */
class ProgressionThresholdActionFactory extends Factory
{
    protected $model = ProgressionThresholdAction::class;

    public function definition(): array
    {
        return [
            'threshold_id' => ProgressionThreshold::factory(),
            'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
            'config' => [
                'feature' => $this->faker->slug(),
            ],
            'auto_revert' => true,
        ];
    }

    public function webhook(): self
    {
        return $this->state(fn () => [
            'action' => ProgressionThresholdAction::ACTION_EXTERNAL_WEBHOOK,
            'config' => [
                'url' => $this->faker->url(),
                'method' => 'POST',
            ],
        ]);
    }
}
