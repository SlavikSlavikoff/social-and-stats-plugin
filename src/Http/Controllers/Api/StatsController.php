<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\SocialStatsUpdated;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateStatsRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\GameStatisticResource;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
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

        ActionLogger::log('socialprofile.stats.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
        ]);

        return $this->resourceResponse(GameStatisticResource::makeWithAccess($stats, true));
    }
}
