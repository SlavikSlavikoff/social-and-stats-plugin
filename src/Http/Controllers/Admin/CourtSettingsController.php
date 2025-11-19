<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\CourtWebhook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourtSettingsController extends Controller
{
    public function edit()
    {
        $roles = Role::orderBy('name')->get();
        $webhooks = CourtWebhook::orderBy('name')->get();

        return view('socialprofile::admin.court.settings', [
            'roles' => $roles,
            'webhooks' => $webhooks,
            'settings' => [
                'ban_role_id' => (int) setting('socialprofile_court_ban_role_id'),
                'mute_role_id' => (int) setting('socialprofile_court_mute_role_id'),
                'novice_role_id' => (int) setting('socialprofile_court_novice_role_id'),
                'default_visibility' => setting('socialprofile_court_default_visibility', config('socialprofile.court.default_visibility')),
                'per_judge_hour_limit' => setting('socialprofile_court_judge_hour_limit', config('socialprofile.court.limits.per_judge_hour_limit')),
                'per_user_daily_limit' => setting('socialprofile_court_user_daily_limit', config('socialprofile.court.limits.per_user_daily_limit')),
            ],
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ban_role_id' => 'nullable|exists:roles,id',
            'mute_role_id' => 'nullable|exists:roles,id',
            'novice_role_id' => 'nullable|exists:roles,id',
            'default_visibility' => 'required|in:private,judges,public',
            'per_judge_hour_limit' => 'required|integer|min:1|max:1000',
            'per_user_daily_limit' => 'required|integer|min:1|max:1000',
        ]);

        setting()->set('socialprofile_court_ban_role_id', $data['ban_role_id'] ?? null);
        setting()->set('socialprofile_court_mute_role_id', $data['mute_role_id'] ?? null);
        setting()->set('socialprofile_court_novice_role_id', $data['novice_role_id'] ?? null);
        setting()->set('socialprofile_court_default_visibility', $data['default_visibility']);
        setting()->set('socialprofile_court_judge_hour_limit', $data['per_judge_hour_limit']);
        setting()->set('socialprofile_court_user_daily_limit', $data['per_user_daily_limit']);

        return back()->with('status', __('socialprofile::messages.court.flash.settings_saved'));
    }

    public function storeWebhook(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'secret' => 'nullable|string|max:255',
            'events' => 'nullable|array',
            'events.*' => 'required|string',
        ]);

        CourtWebhook::create([
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => $data['secret'] ?? null,
            'events' => $data['events'] ?? [],
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', __('socialprofile::messages.court.flash.webhook_saved'));
    }

    public function destroyWebhook(CourtWebhook $webhook): RedirectResponse
    {
        $webhook->delete();

        return back()->with('status', __('socialprofile::messages.court.flash.webhook_deleted'));
    }

}
