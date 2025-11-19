<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\TimelineRequest;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Support\TimelineCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TimelineController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('socialprofile.admin.timelines.season_history');
    }

    public function create(): View
    {
        $existingTypes = Timeline::query()->pluck('type')->all();
        $availableTypes = array_values(array_diff(Timeline::TYPES, $existingTypes));

        return view('socialprofile::admin.timelines.form', [
            'timeline' => new Timeline([
                'is_active' => true,
                'show_period_labels' => true,
            ]),
            'availableTypes' => $availableTypes,
            'mode' => 'create',
        ]);
    }

    public function store(TimelineRequest $request): RedirectResponse
    {
        $timeline = Timeline::create($this->timelinePayload($request));
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', $timeline)
            ->with('success', __('socialprofile::messages.admin.timelines.created'));
    }

    public function edit(Request $request, Timeline $timeline): View
    {
        $tab = strtolower($request->query('tab', 'settings'));
        $periodFilter = $request->filled('period_id') ? (int) $request->input('period_id') : null;
        $visibilityFilter = strtolower($request->query('visibility', ''));

        $periods = $timeline->periods()
            ->withCount('cards')
            ->orderBy('position')
            ->get();
        $visiblePeriods = $this->resolveVisiblePeriods($periods, $periodFilter);
        $visiblePeriodIds = $visiblePeriods->pluck('id');

        $cardsQuery = $timeline->cards()
            ->with('period')
            ->orderBy('position');

        if ($periodFilter !== null) {
            $cardsQuery->where('period_id', $periodFilter);
        }

        if ($visibilityFilter === 'visible') {
            $cardsQuery->where('is_visible', true);
        } elseif ($visibilityFilter === 'hidden') {
            $cardsQuery->where('is_visible', false);
        }

        $cards = $visiblePeriodIds->isEmpty()
            ? collect()
            : $cardsQuery->whereIn('period_id', $visiblePeriodIds)->get()->groupBy('period_id');

        return view('socialprofile::admin.timelines.edit', [
            'timeline' => $timeline,
            'tab' => in_array($tab, ['settings', 'periods', 'cards'], true) ? $tab : 'settings',
            'periods' => $periods,
            'visiblePeriods' => $visiblePeriods,
            'limitedPeriods' => $visiblePeriods->count() < $periods->count(),
            'cards' => $cards,
            'filters' => [
                'period_id' => $periodFilter,
                'visibility' => $visibilityFilter ?: null,
            ],
        ]);
    }

    public function update(TimelineRequest $request, Timeline $timeline): RedirectResponse
    {
        $timeline->update($this->timelinePayload($request, $timeline));
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'settings'])
            ->with('success', __('socialprofile::messages.admin.timelines.updated'));
    }

    public function destroy(Timeline $timeline): RedirectResponse
    {
        $type = $timeline->type;
        $timeline->delete();
        if ($type !== null) {
            TimelineCache::forget($type);
        }

        return redirect()
            ->route('socialprofile.admin.timelines.index')
            ->with('success', __('socialprofile::messages.admin.timelines.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function timelinePayload(TimelineRequest $request, ?Timeline $timeline = null): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['show_period_labels'] = $request->boolean('show_period_labels');

        if ($timeline === null) {
            $data['slug'] = $data['type'];
        } else {
            unset($data['type']);
        }

        return $data;
    }

    /**
     * @return Collection<int, Timeline>
     */
    protected function resolveVisiblePeriods(Collection $periods, ?int $periodFilter): Collection
    {
        if ($periods->isEmpty()) {
            return $periods;
        }

        if ($periodFilter === null) {
            return $periods->take(3);
        }

        $index = $periods->search(static fn ($period) => $period->id === $periodFilter);

        if ($index === false) {
            return $periods->take(3);
        }

        $start = max($index - 1, 0);

        return $periods->slice($start, 3)->values();
    }
}
