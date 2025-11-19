<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Web;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CourtControllerTest extends TestCase
{
    public function test_archive_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('socialprofile.court.index'))->assertForbidden();
    }

    public function test_archive_visible_for_authorized_user(): void
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
            'comment' => 'History entry',
            'payload' => [],
            'issued_at' => now(),
        ]);

        $viewer = $this->makeArchiveUser();

        $this->actingAs($viewer)->get(route('socialprofile.court.index'))
            ->assertOk()
            ->assertSee('History entry');
    }

    protected function makeArchiveUser()
    {
        $role = Role::factory()->create([
            'is_admin' => false,
            'color' => '#555555',
            'power' => 1,
        ]);
        $role->permissions()->create(['permission' => 'social.court.archive']);

        $user = $this->createUser();
        $user->role()->associate($role);
        $user->save();

        return $user;
    }
}
