<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Http\Requests\UpdateSocialScoreRequest;
use Azuriom\Plugin\SocialProfile\Http\Resources\SocialScoreResource;
use Azuriom\Plugin\SocialProfile\Models\SocialScore;
use Illuminate\Http\Request;

class SocialScoreController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'score:read', $user);
        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);

        return $this->resourceResponse(SocialScoreResource::makeResource($score));
    }

    public function update(UpdateSocialScoreRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'score:write', $user, true);
        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
        $score->fill($request->validated());
        $score->save();

        if (function_exists('action')) {
            action()->log('socialprofile.score.updated', [
                'user_id' => $user->id,
                'actor_id' => $context->actor?->id,
                'score' => (int) $score->score,
            ]);
        }

        return $this->resourceResponse(SocialScoreResource::makeResource($score));
    }
}
