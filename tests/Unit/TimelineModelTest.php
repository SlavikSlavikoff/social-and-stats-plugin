<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelineCard;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class TimelineModelTest extends TestCase
{
    public function test_timeline_relationships_work(): void
    {
        $timeline = Timeline::factory()->create();
        $period = TimelinePeriod::factory()->for($timeline)->create(['position' => 1]);

        $card = TimelineCard::factory()
            ->for($timeline)
            ->for($period, 'period')
            ->create([
                'items' => ['One', '', 'Two', 'Three', 'Four', 'Five', 'Six'],
            ]);

        $this->assertCount(1, $timeline->fresh()->periods);
        $this->assertCount(1, $period->fresh()->cards);
        $this->assertSame($timeline->id, $card->timeline_id);
        $this->assertSame($timeline->type, $card->type);
        $this->assertCount(5, $card->fresh()->items, 'Items are trimmed to at most 5 entries');
    }

    public function test_deleting_timeline_cascades_relations(): void
    {
        $timeline = Timeline::factory()->create();
        $period = TimelinePeriod::factory()->for($timeline)->create();
        $card = TimelineCard::factory()->for($timeline)->for($period, 'period')->create();

        $timeline->delete();

        $this->assertDatabaseMissing('socialprofile_timelines', ['id' => $timeline->id]);
        $this->assertDatabaseMissing('socialprofile_timeline_periods', ['id' => $period->id]);
        $this->assertDatabaseMissing('socialprofile_timeline_cards', ['id' => $card->id]);
    }
}
