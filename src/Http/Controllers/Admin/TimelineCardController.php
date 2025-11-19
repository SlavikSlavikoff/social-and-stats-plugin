<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\TimelineCardOrderRequest;
use Azuriom\Plugin\InspiratoStats\Http\Requests\TimelineCardRequest;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelineCard;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Azuriom\Plugin\InspiratoStats\Support\TimelineCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TimelineCardController extends Controller
{
    public function index(Timeline $timeline): RedirectResponse
    {
        return redirect()->route('socialprofile.admin.timelines.edit', [
            'timeline' => $timeline,
            'tab' => 'cards',
        ]);
    }

    public function create(Timeline $timeline): View
    {
        $nextPosition = ($timeline->cards()->max('position') ?? 0) + 1;

        return view('socialprofile::admin.timelines.cards.form', [
            'timeline' => $timeline,
            'card' => new TimelineCard([
                'is_visible' => true,
                'position' => $nextPosition,
            ]),
            'periods' => $timeline->periods()->orderBy('position')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(TimelineCardRequest $request, Timeline $timeline): RedirectResponse
    {
        $data = $this->prepareCardData($request);
        $period = $this->findPeriod($timeline, (int) $request->input('period_id'));
        $data['timeline_id'] = $timeline->id;
        $data['period_id'] = $period->id;

        if ($path = $this->storeImage($request)) {
            $data['image_path'] = $path;
        }

        $timeline->cards()->create($data);
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'cards'])
            ->with('success', __('socialprofile::messages.admin.timelines.cards.created'));
    }

    public function edit(Timeline $timeline, TimelineCard $card): View
    {
        $this->ensureCard($timeline, $card);

        return view('socialprofile::admin.timelines.cards.form', [
            'timeline' => $timeline,
            'card' => $card,
            'periods' => $timeline->periods()->orderBy('position')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(TimelineCardRequest $request, Timeline $timeline, TimelineCard $card): RedirectResponse
    {
        $this->ensureCard($timeline, $card);
        $data = $this->prepareCardData($request);
        $period = $this->findPeriod($timeline, (int) $request->input('period_id'));
        $data['period_id'] = $period->id;

        if ($path = $this->storeImage($request)) {
            $this->deleteImage($card->image_path);
            $data['image_path'] = $path;
        }

        $card->update($data);
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'cards'])
            ->with('success', __('socialprofile::messages.admin.timelines.cards.updated'));
    }

    public function destroy(Timeline $timeline, TimelineCard $card): RedirectResponse
    {
        $this->ensureCard($timeline, $card);
        $this->deleteImage($card->image_path);
        $card->delete();
        TimelineCache::forgetForTimeline($timeline);

        return redirect()
            ->route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'cards'])
            ->with('success', __('socialprofile::messages.admin.timelines.cards.deleted'));
    }

    public function updateOrder(TimelineCardOrderRequest $request, Timeline $timeline): JsonResponse
    {
        $payload = collect($request->validated('items'));
        $cards = $timeline->cards()->get()->keyBy('id');
        $periods = $timeline->periods()->get()->keyBy('id');
        $timestamp = now();
        $records = [];

        foreach ($payload as $item) {
            if (! isset($cards[$item['id']], $periods[$item['period_id']])) {
                continue;
            }

            $records[] = [
                'id' => $item['id'],
                'period_id' => $item['period_id'],
                'position' => $item['position'],
                'updated_at' => $timestamp,
            ];
        }

        $updated = 0;

        if ($records !== []) {
            TimelineCard::withoutEvents(function () use ($records, &$updated): void {
                foreach ($records as $record) {
                    $updated += TimelineCard::whereKey($record['id'])->update([
                        'period_id' => $record['period_id'],
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

    protected function prepareCardData(TimelineCardRequest $request): array
    {
        $data = $request->validated();
        $items = collect($data['items'] ?? [])->filter(static fn ($value) => filled($value));
        $data['items'] = $items->values()->all();

        /** @var Timeline $timeline */
        $timeline = $request->route('timeline');
        $nextPosition = ($timeline?->cards()->max('position') ?? 0) + 1;

        /** @var TimelineCard|null $card */
        $card = $request->route('card');

        if (! isset($data['position'])) {
            $data['position'] = $card instanceof TimelineCard
                ? $card->position
                : $nextPosition;
        }

        $data['is_visible'] = $request->boolean('is_visible');
        $data['highlight'] = $request->boolean('highlight');

        unset($data['image']);
        unset($data['period_id']);

        return $data;
    }

    protected function findPeriod(Timeline $timeline, int $periodId): TimelinePeriod
    {
        $period = $timeline->periods()->where('id', $periodId)->first();

        abort_if($period === null, 404);

        return $period;
    }

    protected function ensureCard(Timeline $timeline, TimelineCard $card): void
    {
        abort_unless($card->timeline_id === $timeline->id, 404);
    }

    protected function storeImage(TimelineCardRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('socialprofile/timelines', 'public');
    }

    protected function deleteImage(?string $path): void
    {
        if ($path !== null && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
