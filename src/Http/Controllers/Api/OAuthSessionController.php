<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\OAuthLoginSession;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthFlowType;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OAuthSessionController extends Controller
{
    public function __construct(
        private readonly OAuthManager $oauthManager,
        private readonly OAuthAccountService $accountService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $provider = $request->string('provider')->toString();

        if (! $this->oauthManager->supports($provider)) {
            abort(404, 'Неизвестный OAuth провайдер.');
        }

        $session = $this->accountService->createLoginSession($provider);
        $authorizationUrl = $this->oauthManager->getAuthorizationUrl($provider, OAuthFlowType::LAUNCHER_LOGIN, [
            'login_session_id' => $session->id,
        ]);

        return response()->json([
            'session_id' => $session->id,
            'authorization_url' => $authorizationUrl,
            'provider' => $provider,
            'status' => $session->status,
            'expires_at' => $session->expires_at->toIso8601String(),
        ], 201);
    }

    public function show(string $sessionId): JsonResponse
    {
        /** @var OAuthLoginSession $session */
        $session = $this->accountService->getLoginSessionOrFail($sessionId);

        return response()->json([
            'session_id' => $session->id,
            'provider' => $session->provider,
            'status' => $session->status,
            'error_code' => $session->error_code,
            'expires_at' => $session->expires_at->toIso8601String(),
            'user' => $session->status === OAuthLoginSession::STATUS_SUCCESS
                ? $session->result_payload['user'] ?? null
                : null,
            'payload' => $session->result_payload,
        ]);
    }
}
