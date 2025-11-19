<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionUserThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressionUserThreshold>
 */
class ProgressionUserThresholdFactory extends Factory
{
    protected $model = ProgressionUserThreshold::class;

    public function definition(): array
    {
        return [
            'threshold_id' => ProgressionThreshold::factory(),
            'user_id' => User::factory(),
            'direction' => 'ascend',
            'action_state' => 'applied',
            'reached_at' => now(),
            'reverted_at' => null,
            'context' => [],
        ];
    }
}
