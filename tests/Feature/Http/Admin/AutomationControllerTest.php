<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Models\AutomationLog;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class AutomationControllerTest extends TestCase
{
    public function test_admin_manages_integrations_rules_and_scheduler(): void
    {
        Http::fake([
            'https://bot.example/*' => Http::response('ok'),
        ]);

        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('socialprofile.admin.automation.integrations.store'), [
            'name' => 'Discord Bot',
            'type' => AutomationIntegration::TYPE_SOCIAL_BOT,
            'config' => [
                'base_url' => 'https://bot.example/hook',
                'token' => 'secret',
                'default_headers_json' => '{"X-Test":"1"}',
            ],
            'is_default' => true,
        ])->assertRedirect();

        $integration = AutomationIntegration::first();
        $this->assertNotNull($integration);

        $this->actingAs($admin)->post(route('socialprofile.admin.automation.rules.store'), [
            'name' => 'Notify on change',
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'priority' => 10,
            'enabled' => true,
            'conditions' => [
                'from_roles' => ['*'],
                'to_roles' => ['*'],
            ],
            'actions' => [
                [
                    'type' => 'social_bot_request',
                    'integration_id' => $integration->id,
                    'config' => [
                        'method' => 'POST',
                        'url' => 'https://bot.example/hook',
                        'headers_json' => '{"Content-Type":"application/json"}',
                        'body' => '{"user":"{username}"}',
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('socialprofile_automation_rules', ['name' => 'Notify on change']);

        $this->actingAs($admin)->post(route('socialprofile.admin.automation.scheduler.update'), [
            'enabled' => true,
            'day' => 1,
            'hour' => 12,
            'top_limit' => 3,
            'sources' => ['social_score'],
            'reward' => [
                'social_score' => 10,
                'coins' => 5,
                'activity' => 2,
            ],
        ])->assertRedirect();

        $this->assertTrue((bool) setting('socialprofile_automation_monthly_enabled'));
        $this->assertSame(3, (int) setting('socialprofile_automation_monthly_limit'));

        $this->actingAs($admin)->post(route('socialprofile.admin.automation.integrations.test', $integration))
            ->assertSessionHas('status');
    }

    public function test_admin_can_replay_logs(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();

        $fromRole = Role::factory()->create();
        $toRole = Role::factory()->create();

        $rule = AutomationRule::factory()->create([
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'conditions' => [
                'from_roles' => [$fromRole->id],
                'to_roles' => [$toRole->id],
            ],
            'actions' => [
                [
                    'type' => 'internal_reward',
                    'config' => ['social_score' => 5],
                ],
            ],
        ]);

        $log = AutomationLog::create([
            'rule_id' => $rule->id,
            'trigger_type' => $rule->trigger_type,
            'status' => 'skipped',
            'payload' => [
                'user_id' => $user->id,
                'username' => $user->name,
                'old_role_id' => $fromRole->id,
                'new_role_id' => $toRole->id,
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('socialprofile.admin.automation.logs.replay', $log))
            ->assertRedirect();

        $this->assertDatabaseHas('socialprofile_automation_logs', [
            'rule_id' => $rule->id,
            'status' => 'success',
        ]);
    }
}
