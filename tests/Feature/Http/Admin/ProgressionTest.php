<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRule;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionRuleEngine;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class ProgressionTest extends TestCase
{
    public function test_admin_can_create_rating_threshold_and_action(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post('/admin/socialprofile/progression/ratings', [
            'name' => 'Custom ladder',
            'slug' => 'ladder',
            'type' => ProgressionRating::TYPE_CUSTOM,
            'scale_min' => 0,
            'scale_max' => 100,
            'visual_min' => -50,
            'visual_max' => 150,
            'is_enabled' => true,
        ])->assertRedirect();

        $rating = ProgressionRating::where('slug', 'ladder')->firstOrFail();
        $this->assertSame(-50, $rating->settings['visual_min']);
        $this->assertSame(150, $rating->settings['visual_max']);

        $this->actingAs($admin)->post("/admin/socialprofile/progression/ratings/{$rating->id}/thresholds", [
            'label' => 'Milestone',
            'value' => 50,
            'direction' => 'ascend',
            'is_major' => true,
            'is_punishment' => true,
            'band_min' => 0,
            'band_max' => 100,
        ])->assertRedirect();

        $threshold = $rating->thresholds()->first();
        $this->assertNotNull($threshold);
        $this->assertTrue($threshold->is_punishment);
        $this->assertSame(0, $threshold->band_min);
        $this->assertSame(100, $threshold->band_max);

        $this->actingAs($admin)->post("/admin/socialprofile/progression/thresholds/{$threshold->id}/actions", [
            'action' => 'plugin_feature_enable',
            'auto_revert' => 1,
            'config' => [
                'feature' => 'profile-badge',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('socialprofile_rating_threshold_actions', [
            'threshold_id' => $threshold->id,
            'action' => 'plugin_feature_enable',
        ]);
    }

    public function test_admin_can_create_rule(): void
    {
        $admin = $this->createAdminUser();
        $rating = ProgressionRating::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/socialprofile/progression/ratings/{$rating->id}/rules", [
            'name' => 'Activity ladder',
            'trigger_key' => ProgressionRuleEngine::TRIGGER_ACTIVITY_UPDATED,
            'source_type' => ProgressionRule::SOURCE_INTERNAL,
            'delta' => 15,
            'cooldown_seconds' => 60,
            'conditions' => [
                'field' => ['payload.delta'],
                'operator' => ['>='],
                'value' => ['5'],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('socialprofile_rating_rules', [
            'rating_id' => $rating->id,
            'name' => 'Activity ladder',
            'delta' => 15,
            'cooldown_seconds' => 60,
        ]);
    }
}
