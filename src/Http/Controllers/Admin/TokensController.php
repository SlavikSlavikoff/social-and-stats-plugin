<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TokensController extends Controller
{
    /**
     * @var array<int, string>
     */
    protected array $scopeKeys = [
        'stats:read',
        'stats:write',
        'activity:read',
        'activity:write',
        'coins:read',
        'coins:write',
        'score:read',
        'score:write',
        'trust:read',
        'trust:write',
        'violations:read',
        'violations:write',
        'verify:read',
        'verify:write',
        'bundle:read',
        'progression:read',
        'progression:write',
    ];

    public function index()
    {
        $tokens = ApiToken::orderBy('created_at', 'desc')->get();

        return view('socialprofile::admin.tokens.index', [
            'tokens' => $tokens,
            'availableScopes' => $this->getAvailableScopes(),
            'generatedToken' => session('socialprofile_generated_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['required', 'array'],
            'allowed_ips' => ['nullable', 'string'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $allowedIps = $this->normalizeIps($validated['allowed_ips'] ?? '');
        $plainToken = Str::random(60);

        ApiToken::create([
            'name' => $validated['name'],
            'token_hash' => ApiToken::hash($plainToken),
            'scopes' => $this->filterScopes($validated['scopes']),
            'allowed_ips' => $allowedIps,
            'rate_limit' => $validated['rate_limit'] ? ['per_minute' => $validated['rate_limit']] : null,
            'created_by' => auth()->id(),
        ]);

        ActionLogger::log('socialprofile.admin.token.created', [
            'actor_id' => auth()->id(),
            'name' => $validated['name'],
        ]);

        return redirect()->route('socialprofile.admin.tokens.index')
            ->with('status', __('socialprofile::messages.admin.tokens.created'))
            ->with('socialprofile_generated_token', $plainToken);
    }

    public function update(Request $request, ApiToken $token): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['required', 'array'],
            'allowed_ips' => ['nullable', 'string'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $token->update([
            'name' => $validated['name'],
            'scopes' => $this->filterScopes($validated['scopes']),
            'allowed_ips' => $this->normalizeIps($validated['allowed_ips'] ?? ''),
            'rate_limit' => $validated['rate_limit'] ? ['per_minute' => $validated['rate_limit']] : null,
        ]);

        ActionLogger::log('socialprofile.admin.token.updated', [
            'actor_id' => auth()->id(),
            'token_id' => $token->id,
        ]);

        return back()->with('status', __('socialprofile::messages.admin.tokens.updated'));
    }

    public function destroy(ApiToken $token): RedirectResponse
    {
        $token->delete();

        ActionLogger::log('socialprofile.admin.token.deleted', [
            'actor_id' => auth()->id(),
            'token_id' => $token->id,
        ]);

        return back()->with('status', __('socialprofile::messages.admin.tokens.deleted'));
    }

    public function rotate(ApiToken $token): RedirectResponse
    {
        $plainToken = Str::random(60);
        $token->update([
            'token_hash' => ApiToken::hash($plainToken),
        ]);

        ActionLogger::log('socialprofile.admin.token.rotated', [
            'actor_id' => auth()->id(),
            'token_id' => $token->id,
        ]);

        return redirect()->route('socialprofile.admin.tokens.index')
            ->with('status', __('socialprofile::messages.admin.tokens.rotated'))
            ->with('socialprofile_generated_token', $plainToken);
    }

    protected function normalizeIps(string $ips): ?array
    {
        $ips = array_filter(array_map('trim', preg_split('/[,\n]/', $ips)));

        return empty($ips) ? null : array_values($ips);
    }

    /**
     * @param array<int, string> $requested
     * @return array<int, string>
     */
    protected function filterScopes(array $requested): array
    {
        $valid = array_intersect($requested, $this->scopeKeys);

        return array_values(array_unique($valid));
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getAvailableScopes(): array
    {
        $scopes = [];

        foreach ($this->scopeKeys as $scope) {
            $normalized = str_replace(':', '_', $scope);

            $scopes[$scope] = [
                'label' => "socialprofile::messages.admin.tokens.scope_labels.$normalized",
                'description' => "socialprofile::messages.admin.tokens.scope_descriptions.$normalized",
            ];
        }

        return $scopes;
    }
}
