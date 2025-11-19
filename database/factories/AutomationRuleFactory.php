<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRule>
 */
class AutomationRuleFactory extends Factory
{
    protected $model = AutomationRule::class;

    public function definition(): array
    {
        return [
            'name' => 'Автоблокировка',
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'enabled' => true,
            'priority' => 10,
            'conditions' => [
                'from_roles' => ['trusted'],
                'to_roles' => ['banned'],
            ],
            'actions' => [
                [
                    'type' => 'internal_reward',
                    'config' => [
                        'social_score' => -50,
                        'coins' => -100,
                        'activity' => -25,
                        'direction' => 'decrease',
                    ],
                ],
            ],
        ];
    }

    public function withIntegration(?AutomationIntegration $integration = null): self
    {
        return $this->state(function () use ($integration) {
            $integration ??= AutomationIntegration::factory()->create();

            return [
                'actions' => [
                    [
                        'type' => 'minecraft_rcon_command',
                        'integration_id' => $integration->id,
                        'config' => [
                            'command' => 'ban {username} Автоблокировка',
                        ],
                    ],
                ],
            ];
        });
    }
}
