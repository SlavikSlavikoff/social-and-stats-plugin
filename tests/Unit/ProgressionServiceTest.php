<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionGate;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionService;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ThresholdActionExecutor;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Mockery;

class ProgressionServiceTest extends TestCase
{
    public function test_punishment_band_applies_and_reverts(): void
    {
        $user = $this->createBasicUser();
        $rating = ProgressionRating::factory()->create([
            'scale_min' => -500,
            'scale_max' => 1200,
        ]);

        $threshold = $rating->thresholds()->create([
            'label' => 'Auto punishment',
            'description' => 'Below zero = ban',
            'value' => 0,
            'direction' => ProgressionThreshold::DIRECTION_DESCEND,
            'is_punishment' => true,
            'band_min' => null,
            'band_max' => -1,
        ]);

        $executor = Mockery::mock(ThresholdActionExecutor::class);
        $executor->shouldReceive('apply')->once()->andReturn([]);
        $executor->shouldReceive('revert')->once()->andReturnNull();

        $service = new ProgressionService($executor, app(ProgressionGate::class));

        $service->adjust($rating, $user, -50);
        $this->assertDatabaseHas('socialprofile_rating_user_thresholds', [
            'threshold_id' => $threshold->id,
            'user_id' => $user->id,
            'action_state' => 'applied',
        ]);

        $service->adjust($rating, $user, 200);
        $this->assertDatabaseHas('socialprofile_rating_user_thresholds', [
            'threshold_id' => $threshold->id,
            'user_id' => $user->id,
            'action_state' => 'reverted',
        ]);
    }
}
