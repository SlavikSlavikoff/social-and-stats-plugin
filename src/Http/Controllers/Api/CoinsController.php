<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Events\CoinsChanged;
use Azuriom\Plugin\SocialProfile\Http\Requests\UpdateCoinsRequest;
use Azuriom\Plugin\SocialProfile\Http\Resources\CoinResource;
use Azuriom\Plugin\SocialProfile\Models\CoinBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinsController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'coins:read', $user);
        $coins = CoinBalance::firstOrCreate(['user_id' => $user->id]);

        return $this->resourceResponse(CoinResource::makeWithAccess($coins, $context->hasFullAccess));
    }

    public function update(UpdateCoinsRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'coins:write', $user, true);
        $payload = $request->validated();

        $coins = DB::transaction(function () use ($user, $payload) {
            $coins = CoinBalance::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $coins->fill($payload);
            $coins->save();

            return $coins;
        });

        event(new CoinsChanged($user, $coins));

        if (function_exists('action')) {
            action()->log('socialprofile.coins.updated', [
                'user_id' => $user->id,
                'actor_id' => $context->actor?->id,
                'balance' => (float) $coins->balance,
            ]);
        }

        return $this->resourceResponse(CoinResource::makeWithAccess($coins, true));
    }
}
