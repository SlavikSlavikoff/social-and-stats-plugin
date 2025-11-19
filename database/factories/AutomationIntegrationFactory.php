<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationIntegration>
 */
class AutomationIntegrationFactory extends Factory
{
    protected $model = AutomationIntegration::class;

    public function definition(): array
    {
        return [
            'name' => 'RCON #'.$this->faker->numberBetween(1, 9),
            'type' => AutomationIntegration::TYPE_RCON,
            'config' => [
                'host' => 'localhost',
                'port' => 25575,
                'password' => 'secret',
                'timeout' => 5,
            ],
            'description' => $this->faker->sentence(),
            'is_default' => false,
        ];
    }

    public function database(): self
    {
        return $this->state(fn () => [
            'type' => AutomationIntegration::TYPE_DATABASE,
            'config' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'minecraft',
                'username' => 'root',
                'password' => 'secret',
            ],
        ]);
    }

    public function bot(): self
    {
        return $this->state(fn () => [
            'type' => AutomationIntegration::TYPE_SOCIAL_BOT,
            'config' => [
                'base_url' => 'https://bot.test/hooks',
                'token' => 'token',
                'default_headers' => ['X-Test' => '1'],
            ],
        ]);
    }
}
