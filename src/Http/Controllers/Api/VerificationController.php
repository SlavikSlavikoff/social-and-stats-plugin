<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Api;

use Azuriom\Plugin\SocialProfile\Events\VerificationChanged;
use Azuriom\Plugin\SocialProfile\Http\Requests\UpdateVerificationRequest;
use Azuriom\Plugin\SocialProfile\Http\Resources\VerificationResource;
use Azuriom\Plugin\SocialProfile\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends ApiController
{
    public function show(Request $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'verify:read', $user);
        $verification = Verification::firstOrCreate(['user_id' => $user->id]);

        return $this->resourceResponse(VerificationResource::makeWithAccess($verification, $context->hasFullAccess));
    }

    public function update(UpdateVerificationRequest $request, string $nickname)
    {
        $user = $this->resolveUser($nickname);
        $context = $this->access($request, 'verify:write', $user, true, 'social.verify_accounts');
        $verification = Verification::firstOrCreate(['user_id' => $user->id]);
        $verification->fill($request->validated());
        $verification->save();

        event(new VerificationChanged($user, $verification));

        if (function_exists('action')) {
            action()->log('socialprofile.verification.updated', [
                'user_id' => $user->id,
                'actor_id' => $context->actor?->id,
                'status' => $verification->status,
            ]);
        }

        return $this->resourceResponse(VerificationResource::makeWithAccess($verification, true));
    }
}
