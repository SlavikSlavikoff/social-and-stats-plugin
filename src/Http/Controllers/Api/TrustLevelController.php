<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\TrustLevelChanged;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateTrustLevelRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\TrustLevelResource;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\Request;

class TrustLevelController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'trust:read', $user);
        $trust = $this->metricOrNew(TrustLevel::class, $user->id, [
            'level' => TrustLevel::LEVELS[0],
        ]);

        return $this->resourceResponse(TrustLevelResource::makeWithAccess($trust, $context->hasFullAccess));
    }

    public function update(UpdateTrustLevelRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'trust:write', $user, true, 'social.grant_trust');
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);
        $payload = $request->validated();
        $payload['granted_by'] = $context->actor?->id;
        $oldLevel = $trust->level ?? TrustLevel::LEVELS[0];
        $trust->fill($payload);
        $trust->save();

        event(new TrustLevelChanged($user, $trust, $oldLevel, $trust->level, $context->actor));

        ActionLogger::log('socialprofile.trust.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
            'level' => $trust->level,
        ]);

        return $this->resourceResponse(TrustLevelResource::makeWithAccess($trust, true));
    }
}
