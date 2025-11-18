<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\CoinsChanged;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateCoinsRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\CoinResource;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\Verification;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinsController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'coins:read', $user);
        $coins = CoinBalance::firstOrCreate(['user_id' => $user->id]);
        $verification = Verification::firstOrCreate(['user_id' => $user->id]);

        $showCoinsPublic = (bool) setting('socialprofile_show_coins_public', true);
        $canViewBalance = $showCoinsPublic && $verification->status === 'verified';

        return $this->resourceResponse(
            CoinResource::makeWithAccess($coins, $context->hasFullAccess, $canViewBalance)
        );
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

        ActionLogger::log('socialprofile.coins.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
            'balance' => (float) $coins->balance,
        ]);

        return $this->resourceResponse(CoinResource::makeWithAccess($coins, true));
    }
}
