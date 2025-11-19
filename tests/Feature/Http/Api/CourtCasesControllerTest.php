<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Api;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CourtCasesControllerTest extends TestCase
{
    public function test_public_api_returns_public_cases(): void
    {
        $judge = $this->createUser();
        $subject = $this->createUser();

        CourtCase::create([
            'user_id' => $subject->id,
            'judge_id' => $judge->id,
            'mode' => 'manual',
            'executor' => 'site',
            'status' => 'active',
            'visibility' => 'public',
            'comment' => 'Test case',
            'payload' => [],
            'issued_at' => now(),
        ]);

        $response = $this->getJson(route('socialprofile.api.court.cases.public'));

        $response->assertOk()->assertJsonFragment(['comment' => 'Test case']);
    }

    public function test_internal_api_can_create_case(): void
    {
        $judge = $this->makeApiJudge();
        $subject = $this->createUser();

        $template = CourtTemplate::create([
            'key' => 'grief',
            'name' => 'Griefing',
            'payload' => [
                'punishment' => ['socialrating' => -15],
            ],
        ]);

        $response = $this->actingAs($judge)->postJson(route('socialprofile.api.court.cases.store'), [
            'subject_id' => $subject->id,
            'mode' => 'auto',
            'template_key' => $template->key,
        ]);

        $response->assertCreated()->assertJsonFragment(['status' => 'active']);
    }

    protected function makeApiJudge()
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
