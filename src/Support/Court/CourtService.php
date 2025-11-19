<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Court;

use Azuriom\Models\Role;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Events\CourtDecisionChanged;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\CourtAction;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Models\CourtLog;
use Azuriom\Plugin\InspiratoStats\Models\CourtRevertJob;
use Azuriom\Plugin\InspiratoStats\Models\CourtStateSnapshot;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CourtService
{
    public function issueFromTemplate(User $judge, User $subject, array $input): CourtCase
    {
        $template = CourtTemplate::where('key', $input['template_key'] ?? null)
            ->where('is_active', true)
            ->firstOrFail();

        $payload = $template->payload;

        if (! empty($input['overrides']) && is_array($input['overrides'])) {
            $payload = array_replace_recursive($payload, $input['overrides']);
        }

        $data = [
            'mode' => 'auto',
            'template_id' => $template->id,
            'comment' => $input['comment'] ?? $template->base_comment,
            'payload' => [
                'template' => $template->key,
                'overrides' => $input['overrides'] ?? [],
            ],
            'executor' => $input['executor'] ?? $template->default_executor ?? config('socialprofile.court.default_executor', 'site'),
            'continued_case_id' => $input['continued_case_id'] ?? null,
        ];

        return $this->storeDecision($judge, $subject, $payload, $data);
    }

    public function issueManual(User $judge, User $subject, array $input): CourtCase
    {
        $payload = [
            'punishment' => Arr::get($input, 'punishment', []),
            'ban' => Arr::get($input, 'ban'),
            'mute' => Arr::get($input, 'mute'),
            'unverify' => Arr::get($input, 'unverify', false),
            'role' => Arr::get($input, 'role'),
        ];

        $data = [
            'mode' => 'manual',
            'template_id' => null,
            'comment' => Arr::get($input, 'comment'),
            'executor' => Arr::get($input, 'executor', config('socialprofile.court.default_executor', 'site')),
            'continued_case_id' => Arr::get($input, 'continued_case_id'),
            'internal_notes' => Arr::get($input, 'internal_notes'),
        ];

        return $this->storeDecision($judge, $subject, $payload, $data);
    }

    protected function storeDecision(User $judge, User $subject, array $payload, array $data): CourtCase
    {
        $this->guardRateLimits($judge, $subject);

        $payload = $this->normalizePayload($payload);
        $actions = $this->buildActions($payload);

        $case = null;

        DB::transaction(function () use (&$case, $judge, $subject, $data, $payload, $actions) {
            $case = CourtCase::create([
                'user_id' => $subject->id,
                'judge_id' => $judge->id,
                'mode' => $data['mode'],
                'template_id' => $data['template_id'] ?? null,
                'executor' => $data['executor'],
                'status' => CourtCase::STATUS_ACTIVE,
                'visibility' => setting('socialprofile_court_default_visibility', config('socialprofile.court.default_visibility', 'judges')),
                'comment' => $data['comment'],
                'internal_notes' => $data['internal_notes'] ?? null,
                'payload' => $payload,
                'continued_case_id' => $data['continued_case_id'] ?? null,
                'issued_at' => now(),
                'expires_at' => $this->resolveExpiration($actions),
            ]);

            foreach ($actions as $action) {
                $actionModel = $case->actions()->create($action);
                $this->applyAction($actionModel, $subject);
            }

            if ($case->actions()->where('status', 'awaiting_revert')->exists()) {
                $case->status = CourtCase::STATUS_AWAITING_REVERT;
                $case->save();
            }

            $this->log($case, 'issued', [
                'mode' => $case->mode,
                'executor' => $case->executor,
                'payload' => $payload,
            ]);
        });

        event(new CourtDecisionChanged($case));

        return $case->fresh(['actions', 'judge', 'subject']);
    }

    protected function normalizePayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'punishment.socialrating' => 'nullable|integer',
            'punishment.activity' => 'nullable|integer',
            'punishment.coins' => 'nullable|integer',
            'punishment.money' => 'nullable|integer',
            'ban.duration' => 'nullable',
            'mute.duration' => 'nullable',
            'unverify' => 'boolean',
            'role.role_id' => 'nullable|exists:roles,id',
            'role.duration' => 'nullable',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function buildActions(array $payload): array
    {
        $actions = [];
        $limits = config('socialprofile.court.limits');

        foreach (Arr::get($payload, 'punishment', []) as $metric => $value) {
            if ($value === null || $value === 0) {
                continue;
            }

            if ($value < $limits['metric_delta_min'] || $value > $limits['metric_delta_max']) {
                throw ValidationException::withMessages([
                    'punishment.'.$metric => __('socialprofile::messages.court.errors.metric_limit'),
                ]);
            }

            $actions[] = [
                'type' => CourtAction::TYPE_METRIC,
                'metric_key' => $metric,
                'delta' => $value,
                'status' => 'pending',
            ];
        }

        if ($ban = Arr::get($payload, 'ban')) {
            $actions[] = $this->buildRoleBasedAction(CourtAction::TYPE_BAN, $ban);
        }

        if ($mute = Arr::get($payload, 'mute')) {
            $actions[] = $this->buildRoleBasedAction(CourtAction::TYPE_MUTE, $mute);
        }

        if (Arr::get($payload, 'unverify')) {
            $roleId = (int) setting('socialprofile_court_novice_role_id');

            if (! $roleId) {
                throw ValidationException::withMessages([
                    'unverify' => __('socialprofile::messages.court.errors.role_not_configured'),
                ]);
            }

            $actions[] = [
                'type' => CourtAction::TYPE_ROLE,
                'role_id' => $roleId ?: null,
                'duration_minutes' => null,
                'status' => 'pending',
            ];
        }

        if ($role = Arr::get($payload, 'role')) {
            if (empty($role['role_id'])) {
                throw ValidationException::withMessages([
                    'role.role_id' => __('socialprofile::messages.court.errors.role_not_configured'),
                ]);
            }

            $actions[] = [
                'type' => CourtAction::TYPE_ROLE,
                'role_id' => $role['role_id'] ?? null,
                'duration_minutes' => $this->normalizeDuration($role['duration'] ?? null, 'role.duration'),
                'status' => 'pending',
            ];
        }

        return $actions;
    }

    protected function buildRoleBasedAction(string $type, array|string $definition): array
    {
        $field = $type === CourtAction::TYPE_BAN ? 'ban.duration' : 'mute.duration';
        $duration = $this->normalizeDuration(is_array($definition) ? ($definition['duration'] ?? null) : $definition, $field);
        $roleKey = $type === CourtAction::TYPE_BAN ? 'socialprofile_court_ban_role_id' : 'socialprofile_court_mute_role_id';
        $roleId = (int) setting($roleKey);

        if (! $roleId) {
            throw ValidationException::withMessages([
                'role' => __('socialprofile::messages.court.errors.role_not_configured'),
            ]);
        }

        return [
            'type' => $type,
            'role_id' => $roleId ?: null,
            'duration_minutes' => $duration,
            'allow_zero_cancel' => true,
            'status' => 'pending',
        ];
    }

    protected function normalizeDuration($value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $minutes = null;

        if (is_numeric($value)) {
            $minutes = (int) $value;
        } elseif (is_string($value)) {
            $value = strtolower($value);
            if (str_ends_with($value, 'h')) {
                $minutes = (int) $value * 60;
            } elseif (str_ends_with($value, 'd')) {
                $minutes = (int) $value * 60 * 24;
            } elseif (str_ends_with($value, 'm')) {
                $minutes = (int) $value * 60 * 24 * 30;
            } else {
                $minutes = (int) $value;
            }
        }

        $provided = $value !== null && $value !== '';

        if ($minutes === null) {
            if (! $provided) {
                return null;
            }

            throw ValidationException::withMessages([
                $field => __('socialprofile::messages.court.errors.invalid_duration'),
            ]);
        }

        if ($minutes < 0) {
            throw ValidationException::withMessages([
                $field => __('socialprofile::messages.court.errors.invalid_duration'),
            ]);
        }

        return $minutes;
    }

    protected function guardRateLimits(User $judge, User $subject): void
    {
        $judgeLimit = (int) setting('socialprofile_court_judge_hour_limit', config('socialprofile.court.limits.per_judge_hour_limit', 30));
        $userLimit = (int) setting('socialprofile_court_user_daily_limit', config('socialprofile.court.limits.per_user_daily_limit', 3));

        $recentJudgeCases = CourtCase::where('judge_id', $judge->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentJudgeCases >= $judgeLimit) {
            throw ValidationException::withMessages([
                'judge' => __('socialprofile::messages.court.errors.judge_rate_limit'),
            ]);
        }

        $recentUserCases = CourtCase::where('user_id', $subject->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentUserCases >= $userLimit) {
            throw ValidationException::withMessages([
                'user' => __('socialprofile::messages.court.errors.user_rate_limit'),
            ]);
        }
    }

    protected function resolveExpiration(array $actions): ?CarbonImmutable
    {
        $max = null;

        foreach ($actions as $action) {
            if (($action['duration_minutes'] ?? null) !== null) {
                $candidate = now()->addMinutes((int) $action['duration_minutes']);
                $max = $max && $max->greaterThan($candidate) ? $max : $candidate;
            }
        }

        return $max ? CarbonImmutable::instance($max) : null;
    }

    protected function applyAction(CourtAction $action, User $subject): void
    {
        match ($action->type) {
            CourtAction::TYPE_METRIC => $this->applyMetricAction($action, $subject),
            CourtAction::TYPE_BAN,
            CourtAction::TYPE_MUTE,
            CourtAction::TYPE_ROLE => $this->applyRoleAction($action, $subject),
            default => null,
        };

        $action->status = $action->shouldScheduleRevert() ? 'awaiting_revert' : 'applied';
        $action->executed_at = now();

        if ($action->duration_minutes && $action->duration_minutes > 0) {
            $action->expires_at = now()->addMinutes($action->duration_minutes);
            CourtRevertJob::create([
                'action_id' => $action->id,
                'run_at' => $action->expires_at,
            ]);
        }

        $action->save();
    }

    protected function applyMetricAction(CourtAction $action, User $subject): void
    {
        $metric = $action->metric_key;
        $delta = (int) $action->delta;

        switch ($metric) {
            case 'socialrating':
                $record = SocialScore::firstOrCreate(['user_id' => $subject->id]);
                $record->increment('score', $delta);
                break;
            case 'activity':
                $record = ActivityPoint::firstOrCreate(['user_id' => $subject->id]);
                $record->increment('points', $delta);
                break;
            case 'coins':
                $record = CoinBalance::firstOrCreate(['user_id' => $subject->id]);
                $record->increment('balance', $delta);
                break;
            case 'money':
                $subject->increment('money', $delta);
                break;
            default:
                throw ValidationException::withMessages([
                    'metric_key' => __('socialprofile::messages.court.errors.unknown_metric'),
                ]);
        }
    }

    protected function applyRoleAction(CourtAction $action, User $subject): void
    {
        if ($action->duration_minutes === 0 && $action->allow_zero_cancel) {
            $this->cancelActiveAction($action, $subject);

            return;
        }

        $snapshot = CourtStateSnapshot::create([
            'action_id' => $action->id,
            'user_id' => $subject->id,
            'snapshot' => ['role_id' => $subject->role_id],
        ]);

        $meta = $action->meta ?? [];
        $meta['snapshot_id'] = $snapshot->id;
        $action->meta = $meta;

        if ($action->role_id) {
            $role = Role::find($action->role_id);

            if ($role) {
                $subject->role()->associate($role);
                $subject->save();
            }
        }
    }

    protected function cancelActiveAction(CourtAction $action, User $subject): void
    {
        $active = CourtAction::whereIn('type', [$action->type])
            ->where('status', '!=', 'reverted')
            ->whereHas('courtCase', function ($query) use ($subject) {
                $query->where('user_id', $subject->id)
                    ->whereIn('status', [CourtCase::STATUS_ACTIVE, CourtCase::STATUS_AWAITING_REVERT]);
            })
            ->latest('id')
            ->first();

        if (! $active) {
            return;
        }

        $this->revertRoleAction($active, $subject);
    }

    public function revertRoleAction(CourtAction $action, User $subject): void
    {
        $snapshotId = $action->meta['snapshot_id'] ?? null;
        $snapshot = $snapshotId ? CourtStateSnapshot::find($snapshotId) : null;

        if ($snapshot && isset($snapshot->snapshot['role_id'])) {
            $role = Role::find($snapshot->snapshot['role_id']);
            if ($role) {
                $subject->role()->associate($role);
                $subject->save();
            }
        }

        $action->status = 'reverted';
        $action->reverted_at = now();
        $action->save();
        CourtRevertJob::where('action_id', $action->id)->delete();

        CourtLog::create([
            'case_id' => $action->case_id,
            'event' => 'reverted',
            'channel' => 'scheduler',
            'payload' => ['action' => $action->type],
        ]);
    }

    protected function log(CourtCase $case, string $event, array $payload = []): void
    {
        CourtLog::create([
            'case_id' => $case->id,
            'event' => $event,
            'channel' => 'site',
            'actor_id' => $case->judge_id,
            'payload' => $payload,
        ]);
    }
}
