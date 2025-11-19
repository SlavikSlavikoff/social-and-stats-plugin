<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\AutomationLog;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationLog>
 */
class AutomationLogFactory extends Factory
{
    protected $model = AutomationLog::class;

    public function definition(): array
    {
        return [
            'rule_id' => AutomationRule::factory(),
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'status' => 'success',
            'payload' => [
                'user_id' => 1,
                'username' => 'Player',
                'old_role_id' => 2,
                'new_role_id' => 3,
            ],
            'actions' => [
                [
                    'type' => 'internal_reward',
                    'result' => 'ok',
                ],
            ],
        ];
    }
}
