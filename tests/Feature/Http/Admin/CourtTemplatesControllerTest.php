<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CourtTemplatesControllerTest extends TestCase
{
    public function test_index_displays_templates(): void
    {
        $user = $this->makeManager();
        CourtTemplate::create([
            'key' => 'demo',
            'name' => 'Demo',
            'base_comment' => 'Example',
            'payload' => ['punishment' => ['socialrating' => -10]],
            'default_executor' => 'site',
            'is_active' => true,
        ]);

        CourtTemplate::create([
            'key' => 'arch',
            'name' => 'Archived',
            'base_comment' => 'Hidden',
            'payload' => ['punishment' => ['socialrating' => -5]],
            'default_executor' => 'site',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('socialprofile.admin.court.templates.index'));

        $response->assertOk()
            ->assertSee('Шаблоны наказаний')
            ->assertSee('Demo')
            ->assertDontSee('Archived');
    }

    public function test_store_creates_template(): void
    {
        $user = $this->makeManager();

        $payload = [
            'key' => 'tox',
            'name' => 'Toxic',
            'base_comment' => 'comment',
            'payload' => '{"punishment":{"socialrating":-5}}',
            'is_active' => 1,
        ];

        $this->actingAs($user)->post(route('socialprofile.admin.court.templates.manage.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('socialprofile_court_templates', [
            'key' => 'tox',
            'name' => 'Toxic',
        ]);
    }

    public function test_update_modifies_template(): void
    {
        $user = $this->makeManager();
        $template = CourtTemplate::create([
            'key' => 'tox',
            'name' => 'Old',
            'payload' => ['punishment' => ['socialrating' => -5]],
            'default_executor' => 'site',
        ]);

        $payload = [
            'key' => 'tox',
            'name' => 'Updated',
            'base_comment' => 'New',
            'payload' => '{"punishment":{"socialrating":-10}}',
            'is_active' => 0,
        ];

        $this->actingAs($user)->put(route('socialprofile.admin.court.templates.manage.update', $template), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('socialprofile_court_templates', [
            'key' => 'tox',
            'name' => 'Updated',
        ]);
    }

    public function test_destroy_archives_template(): void
    {
        $user = $this->makeManager();
        $template = CourtTemplate::create([
            'key' => 'tox',
            'name' => 'Delete',
            'payload' => ['punishment' => ['socialrating' => -5]],
            'default_executor' => 'site',
        ]);

        $this->actingAs($user)->delete(route('socialprofile.admin.court.templates.manage.destroy', $template))
            ->assertRedirect();

        $this->assertDatabaseHas('socialprofile_court_templates', ['key' => 'tox', 'is_active' => false]);
    }

    public function test_refresh_runs_seeder(): void
    {
        $user = $this->makeManager();
        CourtTemplate::query()->delete();
        config()->set('socialprofile.court.templates', [
            [
                'key' => 'refresh',
                'name' => 'Refresh',
                'payload' => ['punishment' => ['socialrating' => -15]],
            ],
            [
                'key' => 'arch',
                'name' => 'Archived',
                'payload' => ['punishment' => ['socialrating' => -5]],
            ],
        ]);

        CourtTemplate::create([
            'key' => 'arch',
            'name' => 'Archived',
            'payload' => ['punishment' => ['socialrating' => -5]],
            'default_executor' => 'site',
            'is_active' => false,
        ]);

        $this->actingAs($user)->post(route('socialprofile.admin.court.templates.refresh'))
            ->assertRedirect();

        $this->assertDatabaseHas('socialprofile_court_templates', ['key' => 'refresh']);
        $this->assertDatabaseHas('socialprofile_court_templates', ['key' => 'arch', 'is_active' => false]);
    }

    protected function makeManager()
    {
        $role = Role::factory()->create([
            'is_admin' => true,
            'power' => 100,
            'color' => '#000000',
        ]);

        $role->permissions()->create(['permission' => 'social.court.manage_settings']);

        $user = $this->createUser();
        $user->role()->associate($role);
        $user->save();

        return $user;
    }
}
