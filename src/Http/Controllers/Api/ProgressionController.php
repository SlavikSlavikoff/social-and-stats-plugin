<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionUserThreshold;
use Azuriom\Plugin\InspiratoStats\Models\UserRatingValue;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressionController extends ApiController
{
    public function __construct(private readonly ProgressionService $progression)
    {
    }

    public function ratings(Request $request): JsonResponse
    {
        $ratings = ProgressionRating::query()
            ->enabled()
            ->with('thresholds.actions')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $ratings->map(fn (ProgressionRating $rating) => $this->formatRatingMetadata($rating, null)),
        ]);
    }

    public function show(Request $request, string $nickname): JsonResponse
    {
        $user = $this->resolveUser($nickname);
        $this->access($request, 'progression:read', $user);

        $ratings = ProgressionRating::query()
            ->enabled()
            ->with('thresholds.actions')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $this->formatUserRatings($user, $ratings),
        ]);
    }

    public function storeEvent(Request $request, string $nickname): JsonResponse
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'progression:write', $user, true, 'social.progression.manage');

        $validated = $request->validate([
            'rating' => ['required', 'string', 'exists:socialprofile_ratings,slug'],
            'delta' => ['required', 'integer'],
            'source' => ['nullable', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
        ]);

        $rating = ProgressionRating::where('slug', $validated['rating'])->firstOrFail();

        if (! $rating->is_enabled) {
            abort(422, __('socialprofile::messages.progression.ratings.disabled_message'));
        }

        $this->progression->adjust(
            $rating,
            $user,
            (int) $validated['delta'],
            $validated['payload'] ?? [],
            null,
            $validated['source'] ?? 'api'
        );

        $rating->load('thresholds.actions');

        return response()->json([
            'data' => $this->formatSingleUserRating($user, $rating),
            'actor_id' => $context->actor?->id,
        ]);
    }

    /**
     * @param iterable<ProgressionRating> $ratings
     * @return array<int, array<string, mixed>>
     */
    protected function formatUserRatings(User $user, iterable $ratings): array
    {
        $values = UserRatingValue::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('rating_id');

        $states = ProgressionUserThreshold::query()
            ->where('user_id', $user->id)
            ->where('action_state', 'applied')
            ->get()
            ->groupBy('threshold_id')
            ->all();

        $data = [];

        foreach ($ratings as $rating) {
            $valueRecord = $values[$rating->id] ?? null;
            $data[] = $this->formatRatingMetadata($rating, $valueRecord, $states);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatSingleUserRating(User $user, ProgressionRating $rating): array
    {
        $value = UserRatingValue::firstOrCreate([
            'user_id' => $user->id,
            'rating_id' => $rating->id,
        ]);

        $states = ProgressionUserThreshold::query()
            ->where('user_id', $user->id)
            ->whereIn('threshold_id', $rating->thresholds->pluck('id'))
            ->where('action_state', 'applied')
            ->get()
            ->groupBy('threshold_id')
            ->all();

        return $this->formatRatingMetadata($rating, $value, $states);
    }

    /**
     * @param array<int, \Illuminate\Support\Collection<int, \Azuriom\Plugin\InspiratoStats\Models\ProgressionUserThreshold>>|null $states
     * @return array<string, mixed>
     */
    protected function formatRatingMetadata(ProgressionRating $rating, ?UserRatingValue $valueRecord = null, ?array $states = null): array
    {
        $scaleSpan = max(1, $rating->scale_max - $rating->scale_min);
        $currentValue = (int) ($valueRecord?->value ?? 0);
        $percent = max(0, min(100, (($currentValue - $rating->scale_min) / $scaleSpan) * 100));
        [$visualMin, $visualMax] = $rating->visualBounds();
        $visualSpan = max(1, $visualMax - $visualMin);
        $visualPercent = max(0, min(100, (($currentValue - $visualMin) / $visualSpan) * 100));
        $visualValue = max($visualMin, min($visualMax, $currentValue));
        $meta = $valueRecord?->meta ?? [];
        $supportKey = $rating->settings['support_meta_key'] ?? 'support_points';
        $supportPoints = (int) ($meta[$supportKey] ?? 0);

        return [
            'id' => $rating->id,
            'slug' => $rating->slug,
            'name' => $rating->name,
            'description' => $rating->description,
            'type' => $rating->type,
            'is_enabled' => (bool) $rating->is_enabled,
            'scale' => [
                'min' => $rating->scale_min,
                'max' => $rating->scale_max,
            ],
            'visual_scale' => [
                'min' => $visualMin,
                'max' => $visualMax,
            ],
            'value' => $currentValue,
            'progress_percent' => $percent,
            'visual_progress_percent' => $visualPercent,
            'visual_value' => $visualValue,
            'visual_overflow' => $currentValue > $visualMax,
            'visual_underflow' => $currentValue < $visualMin,
            'support_points' => $supportPoints,
            'meta' => $meta,
            'thresholds' => $rating->thresholds->map(function ($threshold) use ($rating, $states, $scaleSpan, $visualMin, $visualSpan, $currentValue) {
                $position = max(0, min(100, (($threshold->value - $rating->scale_min) / $scaleSpan) * 100));
                $visualPosition = max(0, min(100, (($threshold->value - $visualMin) / $visualSpan) * 100));
                $stateEntries = $states !== null ? collect($states[$threshold->id] ?? []) : null;
                $hasApplied = $stateEntries?->contains(fn ($entry) => ($entry->action_state ?? null) === 'applied');
                $isActive = $states === null
                    ? null
                    : ($threshold->isPunishment() ? $threshold->valueWithinBand($currentValue) : $hasApplied);

                return [
                    'id' => $threshold->id,
                    'value' => $threshold->value,
                    'label' => $threshold->label,
                    'description' => $threshold->description,
                    'direction' => $threshold->direction,
                    'position_percent' => $position,
                    'visual_position_percent' => $visualPosition,
                    'is_punishment' => $threshold->isPunishment(),
                    'band' => $threshold->band(),
                    'band_configured' => $threshold->band_min !== null || $threshold->band_max !== null,
                    'reached' => $hasApplied,
                    'active' => $isActive,
                ];
            })->all(),
        ];
    }
}
