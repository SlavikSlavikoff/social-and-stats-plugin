<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\CoinsChanged;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateCoinsRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\CoinResource;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinsController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'coins:read', $user);
        $coins = $this->metricOrNew(CoinBalance::class, $user->id, [
            'balance' => 0,
            'hold' => 0,
        ]);
        $canViewBalance = (bool) setting('socialprofile_show_coins_public', true);

        return $this->resourceResponse(
            CoinResource::makeWithAccess($coins, $context->hasFullAccess, $canViewBalance)
        );
    }

    public function update(UpdateCoinsRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'coins:write', $user, true);
        $payload = $request->validated();

        [$coins, $delta] = DB::transaction(function () use ($user, $payload) {
            $coins = CoinBalance::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $before = (float) $coins->balance;
            $coins->fill($payload);
            $coins->save();

            return [$coins, (float) $coins->balance - $before];
        });

        event(new CoinsChanged($user, $coins, [
            'delta' => $delta,
            'source' => 'api',
            'payload' => $payload,
        ]));

        ActionLogger::log('socialprofile.coins.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
            'balance' => (float) $coins->balance,
        ]);

        return $this->resourceResponse(CoinResource::makeWithAccess($coins, true));
    }
}
