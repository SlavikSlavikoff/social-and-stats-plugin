<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Events\SocialStatsUpdated;
use Azuriom\Plugin\SocialProfile\Http\Requests\UpdateStatsRequest;
use Azuriom\Plugin\SocialProfile\Http\Resources\GameStatisticResource;
use Azuriom\Plugin\SocialProfile\Models\GameStatistic;
use Illuminate\Http\Request;

class StatsController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'stats:read', $user);
        $stats = GameStatistic::firstOrCreate(['user_id' => $user->id]);

        return $this->resourceResponse(GameStatisticResource::makeWithAccess($stats, $context->hasFullAccess));
    }

    public function update(UpdateStatsRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'stats:write', $user, true);
        $stats = GameStatistic::firstOrCreate(['user_id' => $user->id]);
        $stats->fill($request->validated());
        $stats->save();

        event(new SocialStatsUpdated($user, $stats));

        if (function_exists('action')) {
            action()->log('socialprofile.stats.updated', [
                'user_id' => $user->id,
                'actor_id' => $context->actor?->id,
            ]);
        }

        return $this->resourceResponse(GameStatisticResource::makeWithAccess($stats, true));
    }
}
