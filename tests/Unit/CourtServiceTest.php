<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\CourtRevertJob;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Support\Court\CourtService;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CourtServiceTest extends TestCase
{
    public function test_issue_from_template_applies_metrics_and_schedules_revert(): void
    {
        $judge = $this->createUser();
        $subject = $this->createUser();

        $banRole = Role::factory()->create(['name' => 'Ban', 'color' => '#000000', 'power' => 10]);
        $muteRole = Role::factory()->create(['name' => 'Mute', 'color' => '#000000', 'power' => 5]);
        $noviceRole = Role::factory()->create(['name' => 'Novice', 'color' => '#000000', 'power' => 1]);

        setting()->set('socialprofile_court_ban_role_id', $banRole->id);
        setting()->set('socialprofile_court_mute_role_id', $muteRole->id);
        setting()->set('socialprofile_court_novice_role_id', $noviceRole->id);

        $template = CourtTemplate::firstOrCreate(
            ['key' => 'toxicity'],
            [
                'name' => 'Токсичность',
                'base_comment' => 'Мут за токсичность',
                'payload' => [
                    'punishment' => [
                        'socialrating' => -30,
                    ],
                    'mute' => ['duration' => '3h'],
                ],
            ]
        );

        $service = $this->app->make(CourtService::class);

        $case = $service->issueFromTemplate($judge, $subject, [
            'template_key' => $template->key,
            'executor' => 'site',
        ]);

        $score = SocialScore::firstWhere('user_id', $subject->id);

        $this->assertNotNull($score);
        $this->assertSame(-30, $score->score);
        $this->assertSame('awaiting_revert', $case->status);
        $this->assertGreaterThanOrEqual(2, $case->actions->count());
        $this->assertDatabaseCount('socialprofile_court_revert_jobs', 1);
    }
}
