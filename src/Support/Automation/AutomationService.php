<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Automation;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\AutomationLog;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Models\Violation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AutomationService
{
    public function __construct(private readonly AutomationActionExecutor $executor)
    {
    }

    public function handleRoleChanged(User $user, ?int $oldRoleId, ?int $newRoleId, ?User $actor = null): void
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
            'uuid' => $user->uuid ?? null,
            'old_role_id' => $oldRoleId,
            'new_role_id' => $newRoleId,
            'actor_id' => $actor?->id ?? auth()->id(),
        ];

        $this->dispatchRules(AutomationRule::TRIGGER_ROLE_CHANGED, $payload, ['user' => $user]);
    }

    public function handleTrustLevelChanged(User $user, TrustLevel $trustLevel, string $oldLevel, string $newLevel, ?User $actor = null): void
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
            'old_trust_level' => $oldLevel,
            'new_trust_level' => $newLevel,
            'trust_level' => $trustLevel->level,
            'actor_id' => $actor?->id ?? auth()->id(),
        ];

        $this->dispatchRules(AutomationRule::TRIGGER_TRUST_LEVEL_CHANGED, $payload, ['user' => $user]);
    }

    public function handleActivityChanged(User $user, ActivityPoint $activity): void
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
            'activity_points' => $activity->points,
        ];

        $this->dispatchRules(AutomationRule::TRIGGER_ACTIVITY_CHANGED, $payload, ['user' => $user]);
    }

    public function handleCoinsChanged(User $user, CoinBalance $coins, ?string $context = null): void
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
            'coin_balance' => (float) $coins->balance,
            'coin_hold' => (float) ($coins->hold ?? 0),
            'coin_context' => $context,
        ];

        $this->dispatchRules(AutomationRule::TRIGGER_COINS_CHANGED, $payload, ['user' => $user]);
    }

    public function handleSocialStatsUpdated(User $user, GameStatistic $stats): void
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
            'played_minutes' => $stats->played_minutes,
            'kills' => $stats->kills,
            'deaths' => $stats->deaths,
            'extra_metrics' => $stats->extra_metrics ?? [],
        ];

        $this->dispatchRules(AutomationRule::TRIGGER_SOCIAL_STATS_UPDATED, $payload, ['user' => $user]);
    }

    public function handleViolationAdded(User $user, Violation $violation): void
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->name,
            'violation_id' => $violation->id,
            'violation_type' => $violation->type,
            'violation_points' => $violation->points,
            'violation_reason' => $violation->reason,
            'violation_evidence' => $violation->evidence_url,
            'violation_issuer_id' => $violation->issued_by,
        ];

        $this->dispatchRules(AutomationRule::TRIGGER_VIOLATION_ADDED, $payload, ['user' => $user]);
    }

    public function handleCourtDecisionChanged(CourtCase $case, string $action = 'issued'): void
    {
        $case->loadMissing('subject');
        $subject = $case->subject;
        $payload = [
            'case_id' => $case->id,
            'case_number' => $case->case_number,
            'case_status' => $case->status,
            'case_mode' => $case->mode,
            'case_action' => $action,
            'case_visibility' => $case->visibility,
            'case_executor' => $case->executor,
            'metrics_applied' => $case->metrics_applied,
            'user_id' => $subject?->id,
            'username' => $subject?->name,
        ];

        $context = [];

        if ($subject !== null) {
            $context['user'] = $subject;
        }

        $this->dispatchRules(AutomationRule::TRIGGER_COURT_DECISION_CHANGED, $payload, $context);
    }

    public function runMonthlyScheduler(): array
    {
        $settings = $this->schedulerSettings();

        if (! $settings['enabled']) {
            return ['status' => 'disabled'];
        }

        $now = Carbon::now();
        $lastRun = setting('socialprofile_automation_monthly_last_run');

        if ($lastRun === $now->format('Y-m')) {
            return ['status' => 'already_ran'];
        }

        if ($now->day < $settings['day'] || ($now->day === $settings['day'] && $now->hour < $settings['hour'])) {
            return ['status' => 'waiting'];
        }

        $winners = $this->resolveMonthlyWinners($settings['sources'], $settings['top_limit']);

        foreach ($winners as $winner) {
            $user = $winner['user'];
            $payload = [
                'user_id' => $user->id,
                'username' => $user->name,
                'position' => $winner['position'],
                'source_metric' => $winner['source_metric'],
            ];

            $log = new AutomationLog([
                'rule_id' => null,
                'trigger_type' => AutomationRule::TRIGGER_MONTHLY_TOP,
                'payload' => $payload,
                'status' => 'pending',
            ]);

            try {
                $result = $this->executor->rewardUser($user, $settings['reward'], [
                    'source_metric' => $winner['source_metric'],
                    'position' => $winner['position'],
                    'scheduler' => true,
                ]);
                $log->status = 'success';
                $log->actions = [$result];
            } catch (\Throwable $e) {
                $log->status = 'error';
                $log->error = $e->getMessage();
                report($e);
            }

            $log->save();

            $this->dispatchRules(
                AutomationRule::TRIGGER_MONTHLY_TOP,
                $payload,
                ['user' => $user]
            );
        }

        setting()->set('socialprofile_automation_monthly_last_run', $now->format('Y-m'));

        return ['status' => 'completed', 'processed' => count($winners)];
    }

    public function replayLog(AutomationLog $log): void
    {
        $rule = $log->rule;

        if (! $rule || ! $rule->enabled) {
            throw new RuntimeException('Это правило недоступно для повторного запуска.');
        }

        $payload = $log->payload ?? [];
        $context = [];

        if (isset($payload['user_id'])) {
            $user = User::find($payload['user_id']);

            if ($user) {
                $context['user'] = $user;
            }
        }

        $this->dispatchRules($rule->trigger_type, $payload, $context);
    }

    protected function dispatchRules(string $triggerType, array $payload, array $context = []): void
    {
        /** @var Collection<int, AutomationRule> $rules */
        $rules = AutomationRule::query()
            ->enabled()
            ->trigger($triggerType)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            $log = new AutomationLog([
                'rule_id' => $rule->id,
                'trigger_type' => $triggerType,
                'payload' => $this->sanitizePayload($payload),
                'status' => 'pending',
            ]);

            try {
                if (! $this->conditionsPass($rule, $payload)) {
                    $log->status = 'skipped';
                    $log->save();
                    continue;
                }

                $results = $this->executor->executeMany($rule->actions ?? [], $payload, $context);
                $log->status = 'success';
                $log->actions = $results;
            } catch (\Throwable $e) {
                $log->status = 'error';
                $log->error = $e->getMessage();
                report($e);
            }

            $log->save();
        }
    }

    protected function conditionsPass(AutomationRule $rule, array $payload): bool
    {
        $conditions = $rule->conditions ?? [];

        return match ($rule->trigger_type) {
            AutomationRule::TRIGGER_ROLE_CHANGED => $this->matchRoleConditions($conditions, $payload),
            AutomationRule::TRIGGER_TRUST_LEVEL_CHANGED => $this->matchTrustConditions($conditions, $payload),
            AutomationRule::TRIGGER_ACTIVITY_CHANGED => $this->matchActivityConditions($conditions, $payload),
            AutomationRule::TRIGGER_COINS_CHANGED => $this->matchCoinsConditions($conditions, $payload),
            AutomationRule::TRIGGER_SOCIAL_STATS_UPDATED => $this->matchStatsConditions($conditions, $payload),
            AutomationRule::TRIGGER_VIOLATION_ADDED => $this->matchViolationConditions($conditions, $payload),
            AutomationRule::TRIGGER_COURT_DECISION_CHANGED => $this->matchCourtConditions($conditions, $payload),
            AutomationRule::TRIGGER_MONTHLY_TOP => $this->matchMonthlyConditions($conditions, $payload),
            default => true,
        };
    }

    protected function matchRoleConditions(array $conditions, array $payload): bool
    {
        return $this->matchesList($payload['old_role_id'] ?? null, $conditions['from_roles'] ?? ['*'])
            && $this->matchesList($payload['new_role_id'] ?? null, $conditions['to_roles'] ?? ['*']);
    }

    protected function matchTrustConditions(array $conditions, array $payload): bool
    {
        $oldLevel = $payload['old_trust_level'] ?? null;
        $newLevel = $payload['new_trust_level'] ?? null;

        if (! $this->matchesList($oldLevel, $conditions['from_levels'] ?? ['*'])) {
            return false;
        }

        if (! $this->matchesList($newLevel, $conditions['to_levels'] ?? ['*'])) {
            return false;
        }

        $oldRank = $this->trustLevelIndex($oldLevel);
        $newRank = $this->trustLevelIndex($newLevel);

        if (isset($conditions['from_rank_min']) && $oldRank !== null && $oldRank < (int) $conditions['from_rank_min']) {
            return false;
        }

        if (isset($conditions['from_rank_max']) && $oldRank !== null && $oldRank > (int) $conditions['from_rank_max']) {
            return false;
        }

        if (isset($conditions['to_rank_min']) && $newRank !== null && $newRank < (int) $conditions['to_rank_min']) {
            return false;
        }

        if (isset($conditions['to_rank_max']) && $newRank !== null && $newRank > (int) $conditions['to_rank_max']) {
            return false;
        }

        return true;
    }

    protected function matchActivityConditions(array $conditions, array $payload): bool
    {
        $points = (int) ($payload['activity_points'] ?? 0);

        if (isset($conditions['points_min']) && $points < (int) $conditions['points_min']) {
            return false;
        }

        if (isset($conditions['points_max']) && $points > (int) $conditions['points_max']) {
            return false;
        }

        return true;
    }

    protected function matchCoinsConditions(array $conditions, array $payload): bool
    {
        $balance = (float) ($payload['coin_balance'] ?? 0);
        $hold = (float) ($payload['coin_hold'] ?? 0);

        if (isset($conditions['balance_min']) && $balance < (float) $conditions['balance_min']) {
            return false;
        }

        if (isset($conditions['balance_max']) && $balance > (float) $conditions['balance_max']) {
            return false;
        }

        if (isset($conditions['hold_min']) && $hold < (float) $conditions['hold_min']) {
            return false;
        }

        if (isset($conditions['hold_max']) && $hold > (float) $conditions['hold_max']) {
            return false;
        }

        return true;
    }

    protected function matchStatsConditions(array $conditions, array $payload): bool
    {
        $played = (int) ($payload['played_minutes'] ?? 0);
        $kills = (int) ($payload['kills'] ?? 0);
        $deaths = (int) ($payload['deaths'] ?? 0);

        if (isset($conditions['played_minutes_min']) && $played < (int) $conditions['played_minutes_min']) {
            return false;
        }

        if (isset($conditions['played_minutes_max']) && $played > (int) $conditions['played_minutes_max']) {
            return false;
        }

        if (isset($conditions['kills_min']) && $kills < (int) $conditions['kills_min']) {
            return false;
        }

        if (isset($conditions['kills_max']) && $kills > (int) $conditions['kills_max']) {
            return false;
        }

        if (isset($conditions['deaths_min']) && $deaths < (int) $conditions['deaths_min']) {
            return false;
        }

        if (isset($conditions['deaths_max']) && $deaths > (int) $conditions['deaths_max']) {
            return false;
        }

        return true;
    }

    protected function matchViolationConditions(array $conditions, array $payload): bool
    {
        if (! $this->matchesList($payload['violation_type'] ?? null, $conditions['violation_types'] ?? ['*'])) {
            return false;
        }

        $points = (int) ($payload['violation_points'] ?? 0);

        if (isset($conditions['violation_points_min']) && $points < (int) $conditions['violation_points_min']) {
            return false;
        }

        if (isset($conditions['violation_points_max']) && $points > (int) $conditions['violation_points_max']) {
            return false;
        }

        return true;
    }

    protected function matchCourtConditions(array $conditions, array $payload): bool
    {
        if (! $this->matchesList($payload['case_action'] ?? null, $conditions['case_actions'] ?? ['*'])) {
            return false;
        }

        if (! $this->matchesList($payload['case_status'] ?? null, $conditions['case_statuses'] ?? ['*'])) {
            return false;
        }

        if (! $this->matchesList($payload['case_mode'] ?? null, $conditions['case_modes'] ?? ['*'])) {
            return false;
        }

        if (isset($conditions['case_executor']) && $conditions['case_executor'] !== '') {
            $needle = mb_strtolower((string) $conditions['case_executor']);
            $haystack = mb_strtolower((string) ($payload['case_executor'] ?? ''));

            if (! str_contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }

    protected function matchMonthlyConditions(array $conditions, array $payload): bool
    {
        $position = (int) ($payload['position'] ?? 0);

        if (! $this->matchesList($payload['source_metric'] ?? null, $conditions['metrics'] ?? ['*'])) {
            return false;
        }

        if (isset($conditions['positions']) && $conditions['positions'] !== [] && ! $this->matchesList($position, $conditions['positions'])) {
            return false;
        }

        if (isset($conditions['position_min']) && $position < (int) $conditions['position_min']) {
            return false;
        }

        if (isset($conditions['position_max']) && $position > (int) $conditions['position_max']) {
            return false;
        }

        return true;
    }

    protected function matchesList(mixed $value, mixed $expected): bool
    {
        $list = is_array($expected) ? $expected : [$expected];

        if (in_array('*', $list, true)) {
            return true;
        }

        $valueString = (string) $value;

        foreach ($list as $candidate) {
            if ((string) $candidate === $valueString) {
                return true;
            }
        }

        return false;
    }

    protected function trustLevelIndex(?string $level): ?int
    {
        if ($level === null) {
            return null;
        }

        $index = array_search($level, TrustLevel::LEVELS, true);

        return $index === false ? null : $index;
    }

    /**
     * @return array<int, array{user: User, position: int, source_metric: string}>
     */
    protected function resolveMonthlyWinners(array $sources, int $limit): array
    {
        $sources = array_filter($sources, fn ($source) => in_array($source, ['social_score', 'activity', 'coins'], true));
        $sources = $sources === [] ? ['social_score'] : $sources;
        $winners = [];

        foreach ($sources as $source) {
            $records = match ($source) {
                'activity' => ActivityPoint::with('user')->orderByDesc('points')->limit($limit)->get(),
                'coins' => CoinBalance::with('user')->orderByDesc('balance')->limit($limit)->get(),
                default => SocialScore::with('user')->orderByDesc('score')->limit($limit)->get(),
            };

            foreach ($records as $index => $record) {
                if ($record->user === null) {
                    continue;
                }

                $winners[] = [
                    'user' => $record->user,
                    'position' => $index + 1,
                    'source_metric' => $source,
                ];
            }
        }

        return $winners;
    }

    protected function sanitizePayload(array $payload): array
    {
        return Arr::map($payload, function ($value) {
            if (is_object($value)) {
                return method_exists($value, 'getKey') ? $value->getKey() : null;
            }

            return $value;
        });
    }

    public function schedulerSettings(): array
    {
        $defaults = config('socialprofile.automation.monthly_rewards');

        return [
            'enabled' => (bool) setting('socialprofile_automation_monthly_enabled', $defaults['enabled']),
            'day' => (int) setting('socialprofile_automation_monthly_day', $defaults['day']),
            'hour' => (int) setting('socialprofile_automation_monthly_hour', $defaults['hour']),
            'top_limit' => (int) setting('socialprofile_automation_monthly_limit', $defaults['top_limit']),
            'sources' => (array) setting('socialprofile_automation_monthly_sources', $defaults['sources']),
            'reward' => (array) setting('socialprofile_automation_monthly_reward', $defaults['reward']),
        ];
    }
}
