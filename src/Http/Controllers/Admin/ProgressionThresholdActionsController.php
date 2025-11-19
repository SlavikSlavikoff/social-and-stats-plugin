<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThresholdAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

class ProgressionThresholdActionsController extends Controller
{
    public function store(Request $request, ProgressionThreshold $threshold): RedirectResponse
    {
        $data = $this->validateAction($request);
        $threshold->actions()->create($data);

        return back()->with('status', __('socialprofile::messages.progression.actions.created'));
    }

    public function update(Request $request, ProgressionThresholdAction $action): RedirectResponse
    {
        $data = $this->validateAction($request);
        $action->update($data);

        return back()->with('status', __('socialprofile::messages.progression.actions.updated'));
    }

    public function destroy(ProgressionThresholdAction $action): RedirectResponse
    {
        $action->delete();

        return back()->with('status', __('socialprofile::messages.progression.actions.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAction(Request $request): array
    {
        $action = $request->validate([
            'action' => ['required', 'in:azuriom_role_grant,azuriom_role_revoke,azuriom_permission_grant,azuriom_permission_revoke,plugin_feature_enable,plugin_feature_disable,external_webhook,automation_rcon,automation_bot'],
            'auto_revert' => ['sometimes', 'boolean'],
        ])['action'];

        return [
            'action' => $action,
            'auto_revert' => $request->boolean('auto_revert', true),
            'config' => $this->extractConfig($action, $request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractConfig(string $action, Request $request): array
    {
        return match ($action) {
            ProgressionThresholdAction::ACTION_ROLE_GRANT => [
                'role_id' => $this->requireInteger($request, 'config.role_id'),
            ],
            ProgressionThresholdAction::ACTION_ROLE_REVOKE => [
                'role_id' => $this->requireInteger($request, 'config.role_id'),
                'fallback_role_id' => $this->requireInteger($request, 'config.fallback_role_id'),
            ],
            ProgressionThresholdAction::ACTION_PERMISSION_GRANT,
            ProgressionThresholdAction::ACTION_PERMISSION_REVOKE => [
                'permission' => $this->requireString($request, 'config.permission'),
            ],
            ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
            ProgressionThresholdAction::ACTION_FEATURE_DISABLE => [
                'feature' => $this->requireString($request, 'config.feature'),
            ],
            ProgressionThresholdAction::ACTION_EXTERNAL_WEBHOOK => [
                'url' => $this->requireString($request, 'config.url'),
                'method' => strtoupper($request->input('config.method', 'POST')),
                'revert_url' => $request->input('config.revert_url'),
                'revert_method' => strtoupper($request->input('config.revert_method', $request->input('config.method', 'POST'))),
                'timeout' => (int) $request->input('config.timeout', 10),
                'headers' => $this->normalizeHeaders($request->input('config.headers')),
            ],
            ProgressionThresholdAction::ACTION_AUTOMATION_RCON => [
                'integration_id' => $this->requireInteger($request, 'config.integration_id', true),
                'command' => $this->requireString($request, 'config.command'),
                'revert_command' => $request->input('config.revert_command') ?: null,
            ],
            ProgressionThresholdAction::ACTION_AUTOMATION_BOT => [
                'integration_id' => $this->requireInteger($request, 'config.integration_id', true),
                'endpoint' => $request->input('config.endpoint', '/'),
                'method' => strtoupper($request->input('config.method', 'POST')),
                'revert_method' => strtoupper($request->input('config.revert_method', $request->input('config.method', 'POST'))),
                'payload' => $this->decodeJsonField($request->input('config.payload'), 'config.payload'),
                'revert_payload' => $this->decodeJsonField($request->input('config.revert_payload'), 'config.revert_payload'),
            ],
            default => [],
        };
    }

    protected function requireInteger(Request $request, string $key, bool $allowNull = false): ?int
    {
        $raw = $request->input($key);

        if ($raw === null || $raw === '') {
            if ($allowNull) {
                return null;
            }

            throw ValidationException::withMessages([
                $key => __('socialprofile::messages.progression.actions.required_field'),
            ]);
        }

        $value = (int) $raw;

        if ($value <= 0) {
            throw ValidationException::withMessages([
                $key => __('socialprofile::messages.progression.actions.required_field'),
            ]);
        }

        return $value;
    }

    protected function requireString(Request $request, string $key): string
    {
        $value = trim((string) $request->input($key));

        if ($value === '') {
            throw ValidationException::withMessages([
                $key => __('socialprofile::messages.progression.actions.required_field'),
            ]);
        }

        return $value;
    }

    /**
     * @param string|null $headers
     * @return array<string, string>|null
     */
    protected function normalizeHeaders(?string $headers): ?array
    {
        if ($headers === null) {
            return null;
        }

        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $headers)));
        $parsed = [];

        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $parsed[$key] = $value;
        }

        return $parsed === [] ? null : $parsed;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeJsonField(?string $value, string $field): ?array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ValidationException::withMessages([
                $field => __('socialprofile::messages.progression.actions.invalid_json'),
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => __('socialprofile::messages.progression.actions.invalid_json'),
            ]);
        }

        return $decoded;
    }
}
