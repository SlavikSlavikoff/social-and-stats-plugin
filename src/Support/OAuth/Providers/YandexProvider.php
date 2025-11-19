<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\AccessToken;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Exceptions\OAuthException;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthProviderInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class YandexProvider implements OAuthProviderInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function getName(): string
    {
        return 'yandex';
    }

    public function getAuthorizationUrl(string $state, array $context = []): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes'] ?? []),
            'state' => $state,
        ]);

        return $this->config['authorization_endpoint'].'?'.$query;
    }

    public function getToken(string $code): AccessToken
    {
        $response = Http::asForm()->post($this->config['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
        ]);

        if ($response->failed()) {
            throw new OAuthException('Не удалось получить токен Яндекса: '.$response->body());
        }

        $data = $response->json();

        return new AccessToken(
            accessToken: Arr::get($data, 'access_token'),
            refreshToken: Arr::get($data, 'refresh_token'),
            expiresAt: isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in'])->toDateTimeImmutable() : null,
            raw: $data ?? []
        );
    }

    public function getUserInfo(AccessToken $token): OAuthUser
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth '.$token->accessToken,
        ])->get($this->config['userinfo_endpoint'], [
            'format' => 'json',
        ]);

        if ($response->failed()) {
            throw new OAuthException('Не удалось получить профиль Яндекса: '.$response->body());
        }

        $data = $response->json();

        $id = (string) Arr::get($data, 'id', Arr::get($data, 'sub', ''));

        if ($id === '') {
            throw new OAuthException('Ответ Яндекса не содержит идентификатора пользователя.');
        }

        $name = Arr::get($data, 'real_name') ?: Arr::get($data, 'display_name');

        return new OAuthUser(
            provider: $this->getName(),
            providerUserId: $id,
            email: Arr::get($data, 'default_email'),
            name: $name,
            avatarUrl: Arr::get($data, 'default_avatar_id')
                ? 'https://avatars.yandex.net/get-yapic/'.Arr::get($data, 'default_avatar_id').'/islands-retina-50'
                : null,
            raw: $data ?? []
        );
    }
}
