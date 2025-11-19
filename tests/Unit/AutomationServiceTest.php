<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit;

use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationService;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class AutomationServiceTest extends TestCase
{
    public function test_role_change_executes_actions(): void
    {
        $service = $this->app->make(AutomationService::class);
        $user = $this->createUser();

        AutomationRule::factory()->create([
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'conditions' => [
                'from_roles' => ['*'],
                'to_roles' => ['*'],
            ],
            'actions' => [
                [
                    'type' => 'internal_reward',
                    'config' => ['social_score' => 5],
                ],
            ],
        ]);

        $service->handleRoleChanged($user, null, null, $this->createAdminUser());

        $this->assertDatabaseHas('socialprofile_social_scores', [
            'user_id' => $user->id,
            'score' => 5,
        ]);
        $this->assertDatabaseHas('socialprofile_automation_logs', [
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'status' => 'success',
        ]);
    }

    public function test_negative_rewards_are_supported(): void
    {
        $service = $this->app->make(AutomationService::class);
        $user = $this->createUser();

        AutomationRule::factory()->create([
            'trigger_type' => AutomationRule::TRIGGER_ROLE_CHANGED,
            'conditions' => [
                'from_roles' => ['*'],
                'to_roles' => ['*'],
            ],
            'actions' => [
                [
                    'type' => 'internal_reward',
                    'config' => [
                        'social_score' => -7,
                        'direction' => 'increase',
                    ],
                ],
            ],
        ]);

        $service->handleRoleChanged($user, null, null, $this->createAdminUser());

        $this->assertDatabaseHas('socialprofile_social_scores', [
            'user_id' => $user->id,
            'score' => -7,
        ]);
    }

    public function test_activity_trigger_executes_actions(): void
    {
        $service = $this->app->make(AutomationService::class);
        $user = $this->createUser();
        $activity = ActivityPoint::create(['user_id' => $user->id, 'points' => 120]);

        AutomationRule::factory()->create([
            'trigger_type' => AutomationRule::TRIGGER_ACTIVITY_CHANGED,
            'conditions' => [
                'points_min' => 100,
                'points_max' => 150,
            ],
            'actions' => [
                [
                    'type' => 'internal_reward',
                    'config' => ['coins' => 5],
                ],
            ],
        ]);

        $service->handleActivityChanged($user, $activity);

        $this->assertDatabaseHas('socialprofile_coin_balances', [
            'user_id' => $user->id,
        ]);
        $this->assertSame('5.00', CoinBalance::where('user_id', $user->id)->value('balance'));
    }

    public function test_monthly_scheduler_rewards_winners(): void
    {
        $service = $this->app->make(AutomationService::class);
        $user = $this->createUser();
        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
        $score->update(['score' => 120]);

        setting()->set('socialprofile_automation_monthly_enabled', true);
        setting()->set('socialprofile_automation_monthly_day', now()->day);
        setting()->set('socialprofile_automation_monthly_hour', now()->subMinute()->hour);
        setting()->set('socialprofile_automation_monthly_limit', 3);
        setting()->set('socialprofile_automation_monthly_sources', ['social_score']);
        setting()->set('socialprofile_automation_monthly_reward', [
            'social_score' => 15,
            'coins' => 0,
            'activity' => 0,
        ]);
        setting()->set('socialprofile_automation_monthly_last_run', null);

        $result = $service->runMonthlyScheduler();

        $this->assertSame('completed', $result['status']);
        $this->assertDatabaseHas('socialprofile_social_scores', [
            'user_id' => $user->id,
            'score' => 135,
        ]);
        $this->assertDatabaseHas('socialprofile_automation_logs', [
            'rule_id' => null,
            'trigger_type' => AutomationRule::TRIGGER_MONTHLY_TOP,
            'status' => 'success',
        ]);
    }
}
