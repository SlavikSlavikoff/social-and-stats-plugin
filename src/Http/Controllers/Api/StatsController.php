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
        $stats = $this->metricOrNew(GameStatistic::class, $user->id, [
            'played_minutes' => 0,
            'kills' => 0,
            'deaths' => 0,
            'extra_metrics' => [],
        ]);

        return $this->resourceResponse(GameStatisticResource::makeWithAccess($stats, $context->hasFullAccess));
    }

    public function update(UpdateStatsRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'stats:write', $user, true);
        $stats = GameStatistic::firstOrCreate(['user_id' => $user->id]);
        $before = $stats->only(['played_minutes', 'kills', 'deaths']);
        $stats->fill($request->validated());
        $stats->save();

        event(new SocialStatsUpdated($user, $stats, [
            'values' => $stats->only(['played_minutes', 'kills', 'deaths']),
            'delta' => [
                'played_minutes' => ($stats->played_minutes ?? 0) - ($before['played_minutes'] ?? 0),
                'kills' => ($stats->kills ?? 0) - ($before['kills'] ?? 0),
                'deaths' => ($stats->deaths ?? 0) - ($before['deaths'] ?? 0),
            ],
            'source' => 'api',
        ]));

        ActionLogger::log('socialprofile.stats.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
        ]);

        return $this->resourceResponse(GameStatisticResource::makeWithAccess($stats, true));
    }
}
