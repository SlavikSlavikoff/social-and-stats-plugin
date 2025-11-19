<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CourtControllerTest extends TestCase
{
    public function test_judge_can_view_judge_workspace(): void
    {
        $judge = $this->makeJudgeUser();

        $response = $this->actingAs($judge)->get(route('socialprofile.court.judge'));

        $response->assertOk()->assertSee(__('socialprofile::messages.court.auto.title'));
    }

    public function test_manual_decision_creates_case(): void
    {
        $judge = $this->makeJudgeUser();
        $subject = $this->createUser();

        $response = $this->actingAs($judge)->post(route('socialprofile.court.decisions.manual.store'), [
            'subject' => $subject->name,
            'comment' => 'Test punishment',
            'punishment' => ['socialrating' => -5],
        ]);

        $response->assertRedirect(route('socialprofile.court.judge'));

        $this->assertDatabaseHas('socialprofile_court_cases', [
            'user_id' => $subject->id,
            'judge_id' => $judge->id,
        ]);
    }

    protected function makeJudgeUser()
    {
        $role = Role::factory()->create([
            'is_admin' => true,
            'color' => '#000000',
            'power' => 100,
        ]);

        $role->permissions()->create(['permission' => 'social.court.judge']);

        $banRole = Role::factory()->create(['name' => 'BanRole', 'color' => '#000000', 'power' => 10]);
        $muteRole = Role::factory()->create(['name' => 'MuteRole', 'color' => '#000000', 'power' => 5]);
        $noviceRole = Role::factory()->create(['name' => 'NoviceRole', 'color' => '#000000', 'power' => 1]);

        setting()->set('socialprofile_court_ban_role_id', $banRole->id);
        setting()->set('socialprofile_court_mute_role_id', $muteRole->id);
        setting()->set('socialprofile_court_novice_role_id', $noviceRole->id);

        $judge = $this->createUser();
        $judge->role()->associate($role);
        $judge->save();

        return $judge;
    }
}
