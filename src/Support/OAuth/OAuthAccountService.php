<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\OAuthIdentity;
use Azuriom\Plugin\InspiratoStats\Models\OAuthLoginSession;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\AccessToken;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthCallbackResult;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Exceptions\OAuthException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OAuthAccountService
{
    public function __construct(
        private readonly int $launcherSessionTtl,
    ) {
    }

    public function linkProviderToUser(string $provider, OAuthUser $oauthUser, User $user, ?AccessToken $token = null): OAuthIdentity
    {
        $existing = OAuthIdentity::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $oauthUser->providerUserId)
            ->first();

        if ($existing !== null && $existing->user_id !== $user->id) {
            throw new OAuthException('Этот аккаунт уже привязан к другому пользователю.');
        }

        $identity = OAuthIdentity::firstOrNew([
            'provider' => $provider,
            'user_id' => $user->id,
        ]);

        $identity->provider_user_id = $oauthUser->providerUserId;
        $identity->data = $oauthUser->raw;

        if ($token !== null) {
            $identity->access_token = $token->accessToken;
            $identity->refresh_token = $token->refreshToken;
            $identity->id_token = $token->idToken;
            $identity->expires_at = $token->expiresAt !== null
                ? Carbon::instance($token->expiresAt)
                : null;
        }

        $identity->save();

        return $identity;
    }

    public function unlinkProviderFromUser(string $provider, User $user): void
    {
        OAuthIdentity::query()
            ->where('provider', $provider)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function loginWithOAuth(OAuthUser $oauthUser): ?User
    {
        $identity = OAuthIdentity::query()
            ->with('user')
            ->where('provider', $oauthUser->provider)
            ->where('provider_user_id', $oauthUser->providerUserId)
            ->first();

        return $identity?->user;
    }

    public function createLoginSession(string $provider): OAuthLoginSession
    {
        $expiresAt = now()->addSeconds($this->launcherSessionTtl);

        return OAuthLoginSession::create([
            'id' => (string) Str::uuid(),
            'provider' => $provider,
            'status' => OAuthLoginSession::STATUS_PENDING,
            'expires_at' => $expiresAt,
        ]);
    }

    public function getLoginSessionOrFail(string $sessionId): OAuthLoginSession
    {
        /** @var OAuthLoginSession $session */
        $session = OAuthLoginSession::query()->findOrFail($sessionId);

        return $this->refreshSessionStatus($session);
    }

    public function handleLauncherCallback(OAuthCallbackResult $result): OAuthLoginSession
    {
        $sessionId = $result->context['login_session_id'] ?? null;

        if ($sessionId === null) {
            throw new OAuthException('Не удалось определить OAuth сессию лаунчера.');
        }

        $session = $this->getLoginSessionOrFail($sessionId);

        if ($session->status !== OAuthLoginSession::STATUS_PENDING) {
            return $session;
        }

        if ($session->isExpired()) {
            return $session->markExpired();
        }

        $user = $this->loginWithOAuth($result->user);

        if ($user === null) {
            return $session->markFailed('identity_not_linked');
        }

        $payload = [
            'session_token' => Str::random(64),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];

        return $session->markSuccess($user, $payload);
    }

    public function failLauncherSession(string $sessionId, string $errorCode): OAuthLoginSession
    {
        $session = $this->getLoginSessionOrFail($sessionId);

        if ($session->status === OAuthLoginSession::STATUS_PENDING) {
            $session->markFailed($errorCode);
        }

        return $session;
    }

    public function refreshSessionStatus(OAuthLoginSession $session): OAuthLoginSession
    {
        if ($session->status === OAuthLoginSession::STATUS_PENDING && $session->isExpired()) {
            $session->markExpired();
        }

        return $session;
    }
}
