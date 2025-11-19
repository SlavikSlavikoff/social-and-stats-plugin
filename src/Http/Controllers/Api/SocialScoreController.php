<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\SocialScoreChanged;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateSocialScoreRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\SocialScoreResource;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\Request;

class SocialScoreController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'score:read', $user);
        $score = $this->metricOrNew(SocialScore::class, $user->id, ['score' => 0]);

        return $this->resourceResponse(SocialScoreResource::makeResource($score));
    }

    public function update(UpdateSocialScoreRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'score:write', $user, true);
        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
        $before = (int) $score->score;
        $score->fill($request->validated());
        $score->save();

        event(new SocialScoreChanged($user, $score, [
            'delta' => (int) $score->score - $before,
            'source' => 'api',
            'payload' => $request->validated(),
        ]));

        ActionLogger::log('socialprofile.score.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
            'score' => (int) $score->score,
        ]);

        return $this->resourceResponse(SocialScoreResource::makeResource($score));
    }
}
