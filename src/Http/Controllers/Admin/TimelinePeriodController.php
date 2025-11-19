<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\TimelinePeriodOrderRequest;
use Azuriom\Plugin\InspiratoStats\Http\Requests\TimelinePeriodRequest;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Azuriom\Plugin\InspiratoStats\Support\TimelineCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TimelinePeriodController extends Controller
{
    public function index(Timeline $timeline): RedirectResponse
    {
        return redirect()->route('socialprofile.admin.timelines.edit', [
            'timeline' => $timeline,
            'tab' => 'periods',
        ]);
    }

    public function create(Timeline $timeline): View
    {
        $nextPosition = ($timeline->periods()->max('position') ?? 0) + 1;

        return view('socialprofile::admin.timelines.periods.form', [
            'timeline' => $timeline,
            'period' => new TimelinePeriod([
                'position' => $nextPosition,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(TimelinePeriodRequest $request, Timeline $timeline): RedirectResponse
    {
        $data = $request->validated();
        $data['timeline_id'] = $timeline->id;
        $data['position'] = $data['position'] ?? (($timeline->periods()->max('position') ?? 0) + 1);

        $timeline->periods()->create($data);
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'periods'])
            ->with('success', __('socialprofile::messages.admin.timelines.periods.created'));
    }

    public function edit(Timeline $timeline, TimelinePeriod $period): View
    {
        $this->ensurePeriod($timeline, $period);

        return view('socialprofile::admin.timelines.periods.form', [
            'timeline' => $timeline,
            'period' => $period,
            'mode' => 'edit',
        ]);
    }

    public function update(TimelinePeriodRequest $request, Timeline $timeline, TimelinePeriod $period): RedirectResponse
    {
        $this->ensurePeriod($timeline, $period);
        $data = $request->validated();

        if (! isset($data['position'])) {
            $data['position'] = $period->position;
        }

        $period->update($data);
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'periods'])
            ->with('success', __('socialprofile::messages.admin.timelines.periods.updated'));
    }

    public function destroy(Timeline $timeline, TimelinePeriod $period): RedirectResponse
    {
        $this->ensurePeriod($timeline, $period);
        $period->delete();
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'periods'])
            ->with('success', __('socialprofile::messages.admin.timelines.periods.deleted'));
    }

    public function updateOrder(TimelinePeriodOrderRequest $request, Timeline $timeline): JsonResponse
    {
        $items = collect($request->validated('items'));
        $periods = $timeline->periods()->get()->keyBy('id');
        $timestamp = now();
        $records = [];

        foreach ($items as $item) {
            if (! isset($periods[$item['id']])) {
                continue;
            }

            $records[] = [
                'id' => $item['id'],
                'position' => $item['position'],
                'updated_at' => $timestamp,
            ];
        }

        $updated = 0;

        if ($records !== []) {
            TimelinePeriod::withoutEvents(function () use ($records, &$updated): void {
                foreach ($records as $record) {
                    $updated += TimelinePeriod::whereKey($record['id'])->update([
                        'position' => $record['position'],
                        'updated_at' => $record['updated_at'],
                    ]);
                }
            });

            TimelineCache::forgetForTimeline($timeline);
        }

        return response()->json([
            'status' => 'ok',
            'updated' => $updated,
        ]);
    }

    protected function ensurePeriod(Timeline $timeline, TimelinePeriod $period): void
    {
        abort_unless($period->timeline_id === $timeline->id, 404);
    }
}
