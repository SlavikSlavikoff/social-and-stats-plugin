<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Http\Resources\BundleResource;
use Azuriom\Plugin\SocialProfile\Models\ActivityPoint;
use Azuriom\Plugin\SocialProfile\Models\CoinBalance;
use Azuriom\Plugin\SocialProfile\Models\GameStatistic;
use Azuriom\Plugin\SocialProfile\Models\SocialScore;
use Azuriom\Plugin\SocialProfile\Models\TrustLevel;
use Azuriom\Plugin\SocialProfile\Models\Verification;
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
            'verification' => Verification::firstOrCreate(['user_id' => $user->id]),
        ];

        return $this->resourceResponse(BundleResource::makeWithAccess($data, $context->hasFullAccess));
    }
}
