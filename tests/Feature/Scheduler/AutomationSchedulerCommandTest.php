<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Scheduler;

use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class AutomationSchedulerCommandTest extends TestCase
{
    public function test_command_runs_scheduler(): void
    {
        $user = $this->createUser();
        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
        $score->update(['score' => 50]);

        setting()->set('socialprofile_automation_monthly_enabled', true);
        setting()->set('socialprofile_automation_monthly_day', now()->day);
        setting()->set('socialprofile_automation_monthly_hour', now()->subHour()->hour);
        setting()->set('socialprofile_automation_monthly_reward', [
            'social_score' => 5,
            'coins' => 0,
            'activity' => 0,
        ]);
        setting()->set('socialprofile_automation_monthly_sources', ['social_score']);
        setting()->set('socialprofile_automation_monthly_last_run', null);

        $this->artisan('socialprofile:automation:tick')->assertExitCode(0);

        $this->assertSame(now()->format('Y-m'), setting('socialprofile_automation_monthly_last_run'));
        $this->assertDatabaseHas('socialprofile_social_scores', [
            'user_id' => $user->id,
            'score' => 55,
        ]);
    }
}
