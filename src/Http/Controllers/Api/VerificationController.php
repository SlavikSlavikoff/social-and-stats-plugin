<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Plugin\InspiratoStats\Events\VerificationChanged;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateVerificationRequest;
use Azuriom\Plugin\InspiratoStats\Http\Resources\VerificationResource;
use Azuriom\Plugin\InspiratoStats\Models\Verification;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
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

        ActionLogger::log('socialprofile.verification.updated', [
            'user_id' => $user->id,
            'actor_id' => $context->actor?->id,
            'status' => $verification->status,
        ]);

        return $this->resourceResponse(VerificationResource::makeWithAccess($verification, true));
    }
}
