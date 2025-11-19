<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Progression;

use Azuriom\Models\Role;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThresholdAction;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationActionExecutor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class ThresholdActionExecutor
{
    public function __construct(private readonly AutomationActionExecutor $automationExecutor)
    {
    }

    /**
     * @param array<string, mixed> $runtime
     * @return array<int, array<string, mixed>>
     */
    public function apply(ProgressionThreshold $threshold, User $user, string $direction, array $runtime = []): array
    {
        $contexts = [];

        foreach ($threshold->actions as $action) {
            $contexts[$action->id] = $this->applySingle($action, $threshold, $user, $direction, $runtime);
        }

        return $contexts;
    }

    /**
     * @param array<int, array<string, mixed>> $contexts
     * @param array<string, mixed> $runtime
     */
    public function revert(ProgressionThreshold $threshold, User $user, string $direction, array $contexts = [], array $runtime = []): void
    {
        foreach ($threshold->actions as $action) {
            $context = $contexts[$action->id] ?? [];

            if (! $threshold->isPunishment() && ! $action->auto_revert && $action->action !== ProgressionThresholdAction::ACTION_EXTERNAL_WEBHOOK) {
                continue;
            }

            $this->revertSingle($action, $threshold, $user, $direction, $context, $runtime);
        }
    }

    /**
     * @param array<string, mixed> $runtime
     * @return array<string, mixed>
     */
    protected function applySingle(ProgressionThresholdAction $action, ProgressionThreshold $threshold, User $user, string $direction, array $runtime): array
    {
        return match ($action->action) {
            ProgressionThresholdAction::ACTION_ROLE_GRANT => $this->grantRole($action, $user),
            ProgressionThresholdAction::ACTION_ROLE_REVOKE => $this->revokeRole($action, $user),
            ProgressionThresholdAction::ACTION_PERMISSION_GRANT,
            ProgressionThresholdAction::ACTION_PERMISSION_REVOKE,
            ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
            ProgressionThresholdAction::ACTION_FEATURE_DISABLE => [],
            ProgressionThresholdAction::ACTION_EXTERNAL_WEBHOOK => $this->dispatchWebhook($action, $threshold, $user, $direction, $runtime, 'apply'),
            ProgressionThresholdAction::ACTION_AUTOMATION_RCON => $this->executeAutomationRcon($action, $user, $runtime, 'apply'),
            ProgressionThresholdAction::ACTION_AUTOMATION_BOT => $this->executeAutomationBot($action, $user, $runtime, 'apply'),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $runtime
     */
    protected function revertSingle(ProgressionThresholdAction $action, ProgressionThreshold $threshold, User $user, string $direction, array $context, array $runtime): void
    {
        switch ($action->action) {
            case ProgressionThresholdAction::ACTION_ROLE_GRANT:
            case ProgressionThresholdAction::ACTION_ROLE_REVOKE:
                $this->restoreRole($action, $user, $context);

                break;

            case ProgressionThresholdAction::ACTION_EXTERNAL_WEBHOOK:
                $this->dispatchWebhook($action, $threshold, $user, $direction, $runtime, 'revert');

                break;

            case ProgressionThresholdAction::ACTION_AUTOMATION_RCON:
                $this->executeAutomationRcon($action, $user, $runtime, 'revert');

                break;

            case ProgressionThresholdAction::ACTION_AUTOMATION_BOT:
                $this->executeAutomationBot($action, $user, $runtime, 'revert');

                break;

            default:
                // No-op: permission/feature actions are resolved via the gate layer.
                break;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function grantRole(ProgressionThresholdAction $action, User $user): array
    {
        $roleId = (int) ($action->config['role_id'] ?? 0);

        if ($roleId <= 0) {
            throw new InvalidArgumentException('Role id is required for role grant action.');
        }

        if ($user->role_id === $roleId) {
            return ['previous_role_id' => $roleId, 'skipped' => true];
        }

        $role = Role::find($roleId);

        if ($role === null) {
            throw new InvalidArgumentException("Role {$roleId} not found.");
        }

        $previousRole = $user->role_id;
        $user->forceFill(['role_id' => $roleId])->save();

        return ['previous_role_id' => $previousRole];
    }

    /**
     * @return array<string, mixed>
     */
    protected function revokeRole(ProgressionThresholdAction $action, User $user): array
    {
        $roleId = (int) ($action->config['role_id'] ?? 0);

        if ($roleId <= 0) {
            throw new InvalidArgumentException('Role id is required for role revoke action.');
        }

        if ($user->role_id !== $roleId) {
            return ['previous_role_id' => $user->role_id, 'skipped' => true];
        }

        $fallback = (int) ($action->config['fallback_role_id'] ?? Role::defaultRoleId());
        $fallbackRole = Role::find($fallback);

        if ($fallbackRole === null) {
            throw new InvalidArgumentException('Fallback role is invalid.');
        }

        $previousRole = $user->role_id;
        $user->forceFill(['role_id' => $fallbackRole->id])->save();

        return ['previous_role_id' => $previousRole];
    }

    protected function restoreRole(ProgressionThresholdAction $action, User $user, array $context): void
    {
        $previous = (int) ($context['previous_role_id'] ?? 0);

        if ($previous <= 0) {
            return;
        }

        if ($user->role_id === $previous) {
            return;
        }

        $role = Role::find($previous);

        if ($role === null) {
            return;
        }

        $user->forceFill(['role_id' => $previous])->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeAutomationRcon(ProgressionThresholdAction $action, User $user, array $runtime, string $stage): array
    {
        $config = $action->config ?? [];
        $commandKey = $stage === 'apply' ? 'command' : 'revert_command';
        $command = $config[$commandKey] ?? null;

        if ($command === null) {
            return [];
        }

        $definition = [
            'type' => 'minecraft_rcon_command',
            'config' => [
                'integration_id' => $config['integration_id'] ?? null,
                'command' => $command,
            ],
        ];

        return $this->runAutomationDefinition($definition, $user, $runtime);
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeAutomationBot(ProgressionThresholdAction $action, User $user, array $runtime, string $stage): array
    {
        $config = $action->config ?? [];
        $payloadKey = $stage === 'apply' ? 'payload' : 'revert_payload';
        $payload = $config[$payloadKey] ?? null;

        if ($payload === null || $payload === '') {
            return [];
        }

        $definition = [
            'type' => 'social_bot_request',
            'config' => [
                'integration_id' => $config['integration_id'] ?? null,
                'endpoint' => $config['endpoint'] ?? '/',
                'method' => strtoupper($stage === 'apply'
                    ? ($config['method'] ?? 'POST')
                    : ($config['revert_method'] ?? $config['method'] ?? 'POST')),
                'payload' => $payload,
            ],
        ];

        return $this->runAutomationDefinition($definition, $user, $runtime);
    }

    /**
     * @return array<string, mixed>
     */
    protected function runAutomationDefinition(array $definition, User $user, array $runtime): array
    {
        if (empty($definition['config']['integration_id']) && $definition['config']['integration_id'] !== 0) {
            return [];
        }

        try {
            $results = $this->automationExecutor->executeMany([$definition], $this->automationPayload($user, $runtime), ['user' => $user]);

            return $results[0] ?? [];
        } catch (Throwable $e) {
            Log::warning('Progression automation action failed', [
                'action' => $definition['type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function automationPayload(User $user, array $runtime): array
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
        ];

        if (isset($runtime['rating']['slug'])) {
            $payload['rating_slug'] = $runtime['rating']['slug'];
        }

        if (isset($runtime['values']['after'])) {
            $payload['rating_value'] = $runtime['values']['after'];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $runtime
     * @return array<string, mixed>
     */
    protected function dispatchWebhook(
        ProgressionThresholdAction $action,
        ProgressionThreshold $threshold,
        User $user,
        string $direction,
        array $runtime,
        string $stage
    ): array {
        $url = Arr::get($action->config, $stage === 'apply' ? 'url' : 'revert_url');

        if (! $url) {
            return [];
        }

        $method = strtoupper(Arr::get($action->config, "{$stage}_method", Arr::get($action->config, 'method', 'POST')));
        $headers = Arr::get($action->config, 'headers', []);
        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role_id' => $user->role_id,
            ],
            'threshold' => [
                'id' => $threshold->id,
                'value' => $threshold->value,
                'label' => $threshold->label,
                'direction' => $direction,
            ],
            'stage' => $stage,
            'runtime' => $runtime,
        ];

        try {
            Http::withHeaders(is_array($headers) ? $headers : [])
                ->timeout((int) Arr::get($action->config, 'timeout', 10))
                ->send($method, (string) $url, ['json' => $payload]);
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch progression webhook', [
                'error' => $e->getMessage(),
                'action_id' => $action->id,
            ]);
        }

        return ['webhook' => true];
    }
}
