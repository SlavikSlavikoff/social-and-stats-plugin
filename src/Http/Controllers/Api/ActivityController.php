<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\ActivityChanged;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateActivityRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\ActivityResource;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\Request;

class ActivityController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'activity:read', $user);
        $activity = ActivityPoint::firstOrCreate(['user_id' => $user->id]);

        return $this->resourceResponse(ActivityResource::makeWithAccess($activity, $context->hasFullAccess));
    }

    public function update(UpdateActivityRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'activity:write', $user, true);
        $activity = ActivityPoint::firstOrCreate(['user_id' => $user->id]);
        $activity->fill($request->validated());
        $activity->save();

        event(new ActivityChanged($user, $activity));

        ActionLogger::log('socialprofile.activity.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
        ]);

        return $this->resourceResponse(ActivityResource::makeWithAccess($activity, true));
    }
}
