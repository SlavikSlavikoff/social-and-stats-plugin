<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\TimelineRequest;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $cards = $cardsQuery->get()->groupBy('period_id');

        return view('socialprofile::admin.timelines.edit', [
            'timeline' => $timeline,
            'tab' => in_array($tab, ['settings', 'periods', 'cards'], true) ? $tab : 'settings',
            'periods' => $periods,
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

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'settings'])
            ->with('success', __('socialprofile::messages.admin.timelines.updated'));
    }

    public function destroy(Timeline $timeline): RedirectResponse
    {
        $timeline->delete();

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
}
