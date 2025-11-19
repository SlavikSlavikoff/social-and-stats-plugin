<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Web;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class TimelinePublicControllerTest extends TestCase
{
    public function test_season_history_page_shows_timeline(): void
    {
        $timeline = Timeline::factory()->create([
            'type' => Timeline::TYPE_SEASON_HISTORY,
            'slug' => Timeline::TYPE_SEASON_HISTORY,
            'title' => 'Season Story',
            'is_active' => true,
        ]);

        $period = TimelinePeriod::factory()->for($timeline)->create(['title' => 'Season 1']);
        $timeline->cards()->create([
            'period_id' => $period->id,
            'title' => 'Launch',
            'items' => ['First milestone'],
            'position' => 1,
            'is_visible' => true,
        ]);

        $response = $this->get(route('socialprofile.timelines.season_history'));

        $response->assertOk();
        $response->assertSeeText('Season Story');
        $response->assertSeeText('Season 1');
        $response->assertSeeText('Launch');
    }

    public function test_road_map_page_handles_missing_data(): void
    {
        $response = $this->get(route('socialprofile.timelines.road_map'));

        $response->assertOk();
        $response->assertSee(__('socialprofile::messages.timelines.road_map.empty'));
    }
}
