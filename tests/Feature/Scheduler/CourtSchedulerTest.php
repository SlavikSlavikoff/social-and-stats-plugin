<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Scheduler;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\CourtRevertJob;
use Azuriom\Plugin\InspiratoStats\Support\Court\CourtService;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CourtSchedulerTest extends TestCase
{
    public function test_scheduler_reverts_expired_roles(): void
    {
        $judge = $this->makeJudgeUser();
        $subjectRole = Role::factory()->create(['name' => 'Citizen', 'color' => '#000000', 'power' => 1]);
        $subject = $this->createUser();
        $subject->role()->associate($subjectRole);
        $subject->save();

        $banRole = Role::factory()->create(['name' => 'BanRole', 'color' => '#000000', 'power' => 0]);
        $muteRole = Role::factory()->create(['name' => 'MuteRole', 'color' => '#111111', 'power' => 0]);
        $noviceRole = Role::factory()->create(['name' => 'NoviceRole', 'color' => '#222222', 'power' => 0]);

        setting()->set('socialprofile_court_ban_role_id', $banRole->id);
        setting()->set('socialprofile_court_mute_role_id', $muteRole->id);
        setting()->set('socialprofile_court_novice_role_id', $noviceRole->id);

        $service = $this->app->make(CourtService::class);

        $case = $service->issueManual($judge, $subject, [
            'comment' => 'Time-limited ban',
            'executor' => 'site',
            'ban' => ['duration' => 1],
        ]);

        $job = CourtRevertJob::first();
        $this->assertNotNull($job);

        $job->update(['run_at' => now()->subMinute()]);

        $this->artisan('socialprofile:court:tick')->assertExitCode(0);

        $subject->refresh();
        $this->assertSame($subjectRole->id, $subject->role_id);
        $this->assertDatabaseHas('socialprofile_court_actions', [
            'case_id' => $case->id,
            'status' => 'reverted',
        ]);
    }

    protected function makeJudgeUser()
    {
        $role = Role::factory()->create([
            'is_admin' => true,
            'color' => '#333333',
            'power' => 100,
        ]);
        $role->permissions()->create(['permission' => 'social.court.judge']);

        $judge = $this->createUser();
        $judge->role()->associate($role);
        $judge->save();

        return $judge;
    }
}
