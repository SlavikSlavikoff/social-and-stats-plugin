<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Events\ViolationAdded;
use Azuriom\Plugin\SocialProfile\Http\Requests\StoreViolationRequest;
use Azuriom\Plugin\SocialProfile\Http\Resources\ViolationResource;
use Azuriom\Plugin\SocialProfile\Models\Violation;
use Illuminate\Http\Request;

class ViolationsController extends ApiController
{
    public function index(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'violations:read', $user);

        if (! $context->hasFullAccess) {
            abort(403, __('socialprofile::messages.api.errors.restricted'));
        }

        $violations = Violation::where('user_id', $user->id)->latest()->get();

        return ViolationResource::collection($violations);
    }

    public function store(StoreViolationRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'violations:write', $user, true, 'social.moderate_violations');
        $payload = $request->validated();
        $payload['user_id'] = $user->id;
        $payload['issued_by'] = $context->actor?->id;

        $violation = Violation::create($payload);

        event(new ViolationAdded($user, $violation));

        if (function_exists('action')) {
            action()->log('socialprofile.violation.created', [
                'user_id' => $user->id,
                'actor_id' => $context->actor?->id,
                'violation_id' => $violation->id,
            ]);
        }

        return new ViolationResource($violation);
    }
}
