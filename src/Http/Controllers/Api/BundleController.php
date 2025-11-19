<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Http\Resources\BundleResource;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Illuminate\Http\Request;

class BundleController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'bundle:read', $user);

        $data = [
            'user' => $user,
            'social_score' => $this->metricOrNew(SocialScore::class, $user->id, ['score' => 0]),
            'activity' => $this->metricOrNew(ActivityPoint::class, $user->id, ['points' => 0]),
            'coins' => $this->metricOrNew(CoinBalance::class, $user->id, [
                'balance' => 0,
                'hold' => 0,
            ]),
            'trust' => $this->metricOrNew(TrustLevel::class, $user->id, ['level' => TrustLevel::LEVELS[0]]),
            'stats' => $this->metricOrNew(GameStatistic::class, $user->id, [
                'played_minutes' => 0,
                'kills' => 0,
                'deaths' => 0,
                'extra_metrics' => [],
            ]),
        ];

        return $this->resourceResponse(BundleResource::makeWithAccess($data, $context->hasFullAccess));
    }
}
