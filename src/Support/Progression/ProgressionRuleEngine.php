<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Progression;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionEvent;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRule;
use Illuminate\Support\Arr;

class ProgressionRuleEngine
{
    public const TRIGGER_ACTIVITY_UPDATED = 'activity.updated';
    public const TRIGGER_SCORE_UPDATED = 'score.updated';
    public const TRIGGER_COINS_UPDATED = 'coins.updated';
    public const TRIGGER_TRUST_UPDATED = 'trust.updated';
    public const TRIGGER_VIOLATION_ADDED = 'violation.added';
    public const TRIGGER_STATS_UPDATED = 'stats.updated';
    public const TRIGGER_EXTERNAL = 'external.event';

    /**
     * @var array<int, string>
     */
    public const AVAILABLE_TRIGGERS = [
        self::TRIGGER_ACTIVITY_UPDATED,
        self::TRIGGER_SCORE_UPDATED,
        self::TRIGGER_COINS_UPDATED,
        self::TRIGGER_TRUST_UPDATED,
        self::TRIGGER_VIOLATION_ADDED,
        self::TRIGGER_STATS_UPDATED,
        self::TRIGGER_EXTERNAL,
    ];

    public function __construct(protected ProgressionService $service)
    {
    }

    /**
     * Dispatch a trigger to all matching rules.
     *
     * @param array<string, mixed> $payload
     */
    public function handle(string $trigger, User $user, array $payload = []): void
    {
        $rules = ProgressionRule::query()
            ->where('trigger_key', $trigger)
            ->where('is_active', true)
            ->with('rating')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matchesConditions($rule, $payload)) {
                continue;
            }

            if ($this->isOnCooldown($rule, $user)) {
                continue;
            }

            $delta = $this->resolveDelta($rule, $payload);

            if ($delta === 0) {
                continue;
            }

            $this->service->adjust(
                $rule->rating,
                $user,
                $delta,
                ['trigger' => $trigger, 'payload' => $payload],
                $rule,
                'rule:'.$rule->id
            );

            $rule->forceFill(['last_triggered_at' => now()])->save();
        }
    }

    /**
     * @return array<int, string>
     */
    public static function triggers(): array
    {
        return self::AVAILABLE_TRIGGERS;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function matchesConditions(ProgressionRule $rule, array $payload): bool
    {
        $conditions = $rule->conditions ?? [];

        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $expected = $condition['value'] ?? null;

            if ($field === null) {
                continue;
            }

            $actual = data_get($payload, $field);

            if (! $this->compare($actual, $expected, $operator)) {
                return false;
            }
        }

        return true;
    }

    protected function compare(mixed $actual, mixed $expected, string $operator): bool
    {
        return match ($operator) {
            '=', '==' => $actual == $expected,
            '!=', '<>' => $actual != $expected,
            '>' => is_numeric($actual) && (float) $actual > (float) $expected,
            '>=' => is_numeric($actual) && (float) $actual >= (float) $expected,
            '<' => is_numeric($actual) && (float) $actual < (float) $expected,
            '<=' => is_numeric($actual) && (float) $actual <= (float) $expected,
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'not_in' => is_array($expected) && ! in_array($actual, $expected, true),
            'contains' => is_array($actual) && in_array($expected, $actual, true),
            'not_contains' => is_array($actual) && ! in_array($expected, $actual, true),
            default => false,
        };
    }

    protected function isOnCooldown(ProgressionRule $rule, User $user): bool
    {
        if (! $rule->cooldown_seconds) {
            return false;
        }

        return ProgressionEvent::query()
            ->where('rule_id', $rule->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subSeconds($rule->cooldown_seconds))
            ->exists();
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function resolveDelta(ProgressionRule $rule, array $payload): int
    {
        $delta = $rule->delta ?? 0;
        $deltaField = Arr::get($rule->options, 'delta_field');
        $multiplier = (float) Arr::get($rule->options, 'delta_multiplier', 1);

        if ($deltaField) {
            $value = data_get($payload, $deltaField);

            if (is_numeric($value)) {
                $delta = (int) round(((float) $value) * $multiplier);
            }
        }

        return (int) $delta;
    }
}
