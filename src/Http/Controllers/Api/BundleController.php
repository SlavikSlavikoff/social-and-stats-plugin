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
            'social_score' => SocialScore::firstOrCreate(['user_id' => $user->id]),
            'activity' => ActivityPoint::firstOrCreate(['user_id' => $user->id]),
            'coins' => CoinBalance::firstOrCreate(['user_id' => $user->id]),
            'trust' => TrustLevel::firstOrCreate(['user_id' => $user->id]),
            'stats' => GameStatistic::firstOrCreate(['user_id' => $user->id]),
        ];

        return $this->resourceResponse(BundleResource::makeWithAccess($data, $context->hasFullAccess));
    }
}
