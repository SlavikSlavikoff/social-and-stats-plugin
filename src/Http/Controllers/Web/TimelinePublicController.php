<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Illuminate\View\View;

class TimelinePublicController extends Controller
{
    public function showSeasonHistory(): View
    {
        return $this->showTimeline(Timeline::TYPE_SEASON_HISTORY, 'season-history');
    }

    public function showRoadMap(): View
    {
        return $this->showTimeline(Timeline::TYPE_ROAD_MAP, 'road-map');
    }

    protected function showTimeline(string $type, string $view): View
    {
        $timeline = Timeline::query()
            ->type($type)
            ->active()
            ->with(['periods' => function ($query) {
                $query->orderBy('position')->with(['cards' => function ($cardQuery) {
                    $cardQuery->where('is_visible', true)->orderBy('position');
                }]);
            }])
            ->first();

        return view("socialprofile::timelines.$view", [
            'timeline' => $timeline,
        ]);
    }
}
