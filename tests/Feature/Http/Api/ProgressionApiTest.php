<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Api;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class ProgressionApiTest extends TestCase
{
    public function test_progression_index_returns_metadata(): void
    {
        $rating = ProgressionRating::factory()->create([
            'type' => ProgressionRating::TYPE_CUSTOM,
            'slug' => 'api-check',
        ]);

        $response = $this->json('GET', '/api/social/v1/progression/ratings');

        $response->assertSuccessful()
            ->assertJsonFragment(['slug' => $rating->slug]);
    }

    public function test_user_progression_and_events_flow(): void
    {
        $user = $this->createBasicUser(['name' => 'ProgressionUser']);
        $rating = ProgressionRating::factory()->create([
            'slug' => 'api-rating',
            'scale_min' => 0,
            'scale_max' => 200,
        ]);
        $rating->thresholds()->create([
            'label' => 'Warning zone',
            'value' => 25,
            'direction' => ProgressionThreshold::DIRECTION_ASCEND,
            'is_punishment' => true,
            'band_min' => 0,
            'band_max' => 50,
        ]);

        [$plain] = $this->issueToken([
            'scopes' => ['progression:write'],
            'allowed_ips' => null,
        ]);

        $response = $this->json('POST', '/api/social/v1/user/'.$user->name.'/progression/events', [
            'rating' => $rating->slug,
            'delta' => 45,
        ], [
            'Authorization' => 'Bearer '.$plain,
        ]);

        $response->assertSuccessful()
            ->assertJsonFragment(['slug' => $rating->slug]);

        $this->json('POST', '/api/social/v1/user/'.$user->name.'/progression/events', [
            'rating' => $rating->slug,
            'delta' => 1200,
        ], [
            'Authorization' => 'Bearer '.$plain,
        ])->assertSuccessful();

        $payload = $this->json('GET', '/api/social/v1/user/'.$user->name.'/progression')
            ->assertSuccessful()
            ->json('data');

        $this->assertSame(1245, $payload[0]['value']);
        $this->assertSame(245, $payload[0]['support_points']);
        $this->assertTrue($payload[0]['visual_overflow']);
        $this->assertFalse($payload[0]['visual_underflow']);
        $this->assertSame(200, $payload[0]['visual_value']);
        $this->assertEquals(100.0, $payload[0]['visual_progress_percent']);
        $this->assertSame(0, $payload[0]['visual_scale']['min']);
        $this->assertSame(200, $payload[0]['visual_scale']['max']);
        $thresholdPayload = $payload[0]['thresholds'][0];
        $this->assertTrue($thresholdPayload['is_punishment']);
        $this->assertTrue($thresholdPayload['band_configured']);
        $this->assertSame(['min' => 0, 'max' => 50], $thresholdPayload['band']);
        $this->assertFalse($thresholdPayload['active']);
    }
}
