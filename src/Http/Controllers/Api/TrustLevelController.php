<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Events\TrustLevelChanged;
use Azuriom\Plugin\SocialProfile\Http\Requests\UpdateTrustLevelRequest;
use Azuriom\Plugin\SocialProfile\Http\Resources\TrustLevelResource;
use Azuriom\Plugin\SocialProfile\Models\TrustLevel;
use Illuminate\Http\Request;

class TrustLevelController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'trust:read', $user);
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);

        return $this->resourceResponse(TrustLevelResource::makeWithAccess($trust, $context->hasFullAccess));
    }

    public function update(UpdateTrustLevelRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'trust:write', $user, true, 'social.grant_trust');
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);
        $payload = $request->validated();
        $payload['granted_by'] = $context->actor?->id;
        $trust->fill($payload);
        $trust->save();

        event(new TrustLevelChanged($user, $trust, $context->actor));

        if (function_exists('action')) {
            action()->log('socialprofile.trust.updated', [
                'user_id' => $user->id,
                'actor_id' => $context->actor?->id,
                'level' => $trust->level,
            ]);
        }

        return $this->resourceResponse(TrustLevelResource::makeWithAccess($trust, true));
    }
}
