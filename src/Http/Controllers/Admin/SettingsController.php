<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('socialprofile::admin.settings.edit', [
            'publicRateLimit' => setting('socialprofile_public_rate_limit', 60),
            'tokenRateLimit' => setting('socialprofile_token_rate_limit', 120),
            'showCoinsPublic' => setting('socialprofile_show_coins_public', true),
            'enableHmac' => setting('socialprofile_enable_hmac', false),
            'hmacSecret' => setting('socialprofile_hmac_secret'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'public_rate_limit' => ['required', 'integer', 'min:1'],
            'token_rate_limit' => ['required', 'integer', 'min:1'],
            'show_coins_public' => ['sometimes', 'boolean'],
            'enable_hmac' => ['sometimes', 'boolean'],
            'hmac_secret' => ['nullable', 'string', 'max:255'],
        ]);

        setting()->set('socialprofile_public_rate_limit', $validated['public_rate_limit']);
        setting()->set('socialprofile_token_rate_limit', $validated['token_rate_limit']);
        setting()->set('socialprofile_show_coins_public', $request->boolean('show_coins_public'));
        setting()->set('socialprofile_enable_hmac', $request->boolean('enable_hmac'));
        setting()->set('socialprofile_hmac_secret', $validated['hmac_secret'] ?? null);

        ActionLogger::log('socialprofile.admin.settings.updated', [
            'actor_id' => auth()->id(),
        ]);

        return redirect()->route('socialprofile.admin.settings.edit')->with('status', __('socialprofile::messages.admin.settings.saved'));
    }
}
