<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Progression;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Events\ProgressionRatingChanged;
use Azuriom\Plugin\InspiratoStats\Events\ProgressionThresholdChanged;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionEvent;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRule;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionUserThreshold;
use Azuriom\Plugin\InspiratoStats\Models\UserRatingValue;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProgressionService
{
    public function __construct(
        protected ThresholdActionExecutor $actionExecutor,
        protected ProgressionGate $gate
    ) {
    }

    /**
     * Adjust a rating by delta value.
     *
     * @param ProgressionRating|string $rating
     * @param array<string, mixed> $payload
     */
    public function adjust($rating, User $user, int $delta, array $payload = [], ?ProgressionRule $rule = null, ?string $source = null): ?ProgressionEvent
    {
        if ($delta === 0) {
            return null;
        }

        return DB::transaction(function () use ($rating, $user, $delta, $payload, $rule, $source) {
            $ratingModel = $this->resolveRating($rating);

            if (! $ratingModel->is_enabled) {
                return null;
            }

            $valueModel = $ratingModel->findOrCreateValueForUser($user);
            $before = (int) $valueModel->value;
            $after = $before + $delta;

            $valueModel->forceFill([
                'value' => $after,
                'meta' => $this->mergeMeta($valueModel, $source),
            ])->save();

            $this->handleSupportBonus($ratingModel, $valueModel, $before, $after);

            $event = ProgressionEvent::create([
                'rating_id' => $ratingModel->id,
                'user_id' => $user->id,
                'rule_id' => $rule?->id,
                'source' => $source,
                'amount' => $delta,
                'value_before' => $before,
                'value_after' => $after,
                'payload' => $payload,
            ]);

            $this->evaluateThresholds($ratingModel, $user, $before, $after, $event, $payload);

            event(new ProgressionRatingChanged($user, $ratingModel, $before, $after, $rule, $event, $payload));

            return $event;
        });
    }

    /**
     * Set the rating value to a specific number.
     *
     * @param ProgressionRating|string $rating
     * @param array<string, mixed> $payload
     */
    public function set($rating, User $user, int $value, array $payload = [], ?string $source = null): ?ProgressionEvent
    {
        $ratingModel = $this->resolveRating($rating);
        $current = $ratingModel->findOrCreateValueForUser($user);
        $delta = $value - (int) $current->value;

        if ($delta === 0) {
            return null;
        }

        return $this->adjust($ratingModel, $user, $delta, $payload, null, $source);
    }

    /**
     * @param ProgressionRating|string $rating
     */
    public function userValue($rating, User $user): ?UserRatingValue
    {
        $ratingModel = $this->resolveRating($rating);

        return $ratingModel->findValueForUser($user);
    }

    protected function resolveRating($rating): ProgressionRating
    {
        if ($rating instanceof ProgressionRating) {
            return $rating;
        }

        $model = ProgressionRating::where('slug', $rating)->first();

        if ($model === null) {
            throw new RuntimeException("Rating {$rating} not found.");
        }

        return $model;
    }

    protected function mergeMeta(UserRatingValue $value, ?string $source): array
    {
        $meta = $value->meta ?? [];

        if ($source !== null) {
            $meta['last_source'] = $source;
            $meta['updated_at'] = now()->toDateTimeString();
        }

        return $meta;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function evaluateThresholds(
        ProgressionRating $rating,
        User $user,
        int $before,
        int $after,
        ProgressionEvent $event,
        array $payload = []
    ): void {
        $thresholds = $rating->thresholds()->with('actions')->get();
        $runtimeBase = [
            'event' => $event->toArray(),
            'payload' => $payload,
            'rating' => [
                'id' => $rating->id,
                'slug' => $rating->slug,
            ],
            'values' => [
                'before' => $before,
                'after' => $after,
            ],
        ];

        foreach ($thresholds as $threshold) {
            $this->evaluateSingleThreshold($rating, $threshold, $user, $before, $after, $runtimeBase);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function evaluateSingleThreshold(
        ProgressionRating $rating,
        ProgressionThreshold $threshold,
        User $user,
        int $before,
        int $after,
        array $runtimeBase
    ): void {
        if ($threshold->isPunishment()) {
            $this->evaluatePunishmentThreshold($rating, $threshold, $user, $before, $after, $runtimeBase);

            return;
        }

        $value = $threshold->value;

        $applyAscend = $before < $value && $after >= $value;
        $revertAscend = $before >= $value && $after < $value;
        $applyDescend = $before > $value && $after <= $value;
        $revertDescend = $before <= $value && $after > $value;

        if ($this->matchesDirection($threshold, ProgressionThreshold::DIRECTION_ASCEND) && $applyAscend) {
            $this->applyThreshold($rating, $threshold, $user, ProgressionThreshold::DIRECTION_ASCEND, $runtimeBase);
        } elseif ($this->matchesDirection($threshold, ProgressionThreshold::DIRECTION_ASCEND) && $revertAscend) {
            $this->revertThreshold($rating, $threshold, $user, ProgressionThreshold::DIRECTION_ASCEND, $runtimeBase);
        }

        if ($this->matchesDirection($threshold, ProgressionThreshold::DIRECTION_DESCEND) && $applyDescend) {
            $this->applyThreshold($rating, $threshold, $user, ProgressionThreshold::DIRECTION_DESCEND, $runtimeBase);
        } elseif ($this->matchesDirection($threshold, ProgressionThreshold::DIRECTION_DESCEND) && $revertDescend) {
            $this->revertThreshold($rating, $threshold, $user, ProgressionThreshold::DIRECTION_DESCEND, $runtimeBase);
        }
    }

    protected function evaluatePunishmentThreshold(
        ProgressionRating $rating,
        ProgressionThreshold $threshold,
        User $user,
        int $before,
        int $after,
        array $runtime
    ): void {
        $direction = ProgressionThreshold::DIRECTION_ASCEND;
        $state = ProgressionUserThreshold::where([
            'threshold_id' => $threshold->id,
            'user_id' => $user->id,
            'direction' => $direction,
        ])->first();

        $wasActive = $threshold->valueWithinBand($before);
        $isActive = $threshold->valueWithinBand($after);

        if ($isActive && ($state === null || $state->action_state === 'reverted')) {
            $this->applyThreshold($rating, $threshold, $user, $direction, $runtime);

            return;
        }

        if (! $isActive && $state !== null && $state->action_state === 'applied') {
            $this->revertThreshold($rating, $threshold, $user, $direction, $runtime);

            return;
        }

        if ($isActive && ! $wasActive && $state !== null && $state->action_state !== 'applied') {
            $this->applyThreshold($rating, $threshold, $user, $direction, $runtime);
        }
    }

    protected function matchesDirection(ProgressionThreshold $threshold, string $direction): bool
    {
        return $threshold->direction === $direction || $threshold->direction === ProgressionThreshold::DIRECTION_ANY;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function applyThreshold(
        ProgressionRating $rating,
        ProgressionThreshold $threshold,
        User $user,
        string $direction,
        array $runtime
    ): void {
        $state = ProgressionUserThreshold::firstOrNew([
            'threshold_id' => $threshold->id,
            'user_id' => $user->id,
            'direction' => $direction,
        ]);

        $runtime['rating']['label'] = $rating->name;
        $contexts = $this->actionExecutor->apply($threshold, $user, $direction, $runtime);

        $state->forceFill([
            'action_state' => 'applied',
            'reached_at' => now(),
            'reverted_at' => null,
            'context' => ['actions' => $contexts],
        ])->save();

        $this->gate->flushUserCache($user->id);

        event(new ProgressionThresholdChanged($user, $threshold, $direction, 'applied', $state->context ?? []));
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function revertThreshold(ProgressionRating $rating, ProgressionThreshold $threshold, User $user, string $direction, array $runtime): void
    {
        $state = ProgressionUserThreshold::where([
            'threshold_id' => $threshold->id,
            'user_id' => $user->id,
            'direction' => $direction,
        ])->first();

        if ($state === null || $state->action_state === 'reverted') {
            return;
        }

        $context = $state->context['actions'] ?? [];

        $runtime['rating']['label'] = $rating->name;
        $this->actionExecutor->revert($threshold, $user, $direction, $context, $runtime);

        $state->forceFill([
            'action_state' => 'reverted',
            'reverted_at' => now(),
        ])->save();

        $this->gate->flushUserCache($user->id);

        event(new ProgressionThresholdChanged($user, $threshold, $direction, 'reverted', $state->context ?? []));
    }

    protected function handleSupportBonus(ProgressionRating $rating, UserRatingValue $value, int $before, int $after): void
    {
        $threshold = (int) ($rating->settings['support_threshold'] ?? 0);

        if ($threshold <= 0 || $after <= $threshold) {
            return;
        }

        $awarded = max(0, $after - max($threshold, $before));

        if ($awarded === 0) {
            return;
        }

        $metaKey = $rating->settings['support_meta_key'] ?? 'support_points';
        $meta = $value->meta ?? [];
        $meta[$metaKey] = (int) ($meta[$metaKey] ?? 0) + $awarded;
        $value->forceFill(['meta' => $meta])->save();
    }
}
