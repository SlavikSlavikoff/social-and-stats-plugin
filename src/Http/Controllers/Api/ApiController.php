<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Azuriom\Plugin\InspiratoStats\Support\ApiAccessContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiController extends Controller
{
    protected function resolveUser(string $nickname): User
    {
        $column = config('auth.providers.users.field', 'name');

        return User::where($column, $nickname)->firstOrFail();
    }

    protected function access(Request $request, string $scope, User $target, bool $write = false, ?string $writePermission = 'social.edit'): ApiAccessContext
    {
        $token = ApiToken::findFromRequest($request);
        $actor = auth()->user();
        $tokenAllowed = $token !== null && $token->allowsScope($scope) && $token->withinIpRange($request->ip());

        $permissionAllowed = $actor !== null && ($writePermission === null || $actor->can($writePermission));

        if ($write) {
            if ($tokenAllowed && setting('socialprofile_enable_hmac', false)) {
                $secret = setting('socialprofile_hmac_secret');
                $signature = $request->header('X-Social-Signature');

                if (empty($secret) || empty($signature) || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
                    abort(401, __('socialprofile::messages.api.errors.scope_required'));
                }
            }

            if (!($tokenAllowed || $permissionAllowed)) {
                abort(403, __('socialprofile::messages.api.errors.scope_required'));
            }
        }

        $hasFullAccess = $tokenAllowed
            || ($actor !== null && ($actor->can('social.edit') || $permissionAllowed || $actor->is($target)));

        return new ApiAccessContext($tokenAllowed ? $token : null, $hasFullAccess, $actor);
    }

    protected function resourceResponse(JsonResource $resource): JsonResource
    {
        return $resource;
    }
}
