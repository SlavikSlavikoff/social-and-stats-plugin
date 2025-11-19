<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelineCard;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class TimelineControllerTest extends TestCase
{
    public function test_admin_can_create_timeline(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('socialprofile.admin.timelines.store'), [
            'type' => Timeline::TYPE_SEASON_HISTORY,
            'title' => 'Season History',
            'subtitle' => 'Subtitle',
            'intro_text' => 'Intro',
            'is_active' => true,
            'show_period_labels' => true,
            'meta_title' => 'Meta title',
            'meta_description' => 'Meta description',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('socialprofile_timelines', [
            'type' => Timeline::TYPE_SEASON_HISTORY,
            'title' => 'Season History',
            'is_active' => true,
        ]);
    }

    public function test_periods_and_cards_can_be_managed(): void
    {
        $user = $this->createAdminUser();
        $timeline = Timeline::factory()->create([
            'type' => Timeline::TYPE_ROAD_MAP,
        ]);

        $storePeriod = $this->actingAs($user)->post(route('socialprofile.admin.timelines.periods.store', $timeline), [
            'title' => 'Phase 1',
            'description' => 'Desc',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
            'position' => 1,
        ]);

        $storePeriod->assertRedirect();
        $period = TimelinePeriod::first();

        $storeCard = $this->actingAs($user)->post(route('socialprofile.admin.timelines.cards.store', $timeline), [
            'period_id' => $period->id,
            'title' => 'Beta release',
            'subtitle' => 'Subtitle',
            'button_label' => 'Read',
            'button_url' => 'https://example.com',
            'items' => ['One', 'Two'],
            'position' => 1,
            'is_visible' => true,
        ]);

        $storeCard->assertRedirect();
        $card = TimelineCard::first();

        $this->actingAs($user)
            ->postJson(route('socialprofile.admin.timelines.periods.order', $timeline), [
                'items' => [
                    ['id' => $period->id, 'position' => 3],
                ],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('socialprofile.admin.timelines.cards.order', $timeline), [
                'items' => [
                    [
                        'id' => $card->id,
                        'position' => 2,
                        'period_id' => $period->id,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('socialprofile_timeline_periods', [
            'id' => $period->id,
            'position' => 3,
        ]);

        $this->assertDatabaseHas('socialprofile_timeline_cards', [
            'id' => $card->id,
            'position' => 2,
            'period_id' => $period->id,
        ]);
    }
}
