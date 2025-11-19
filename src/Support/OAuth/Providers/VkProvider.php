<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\AccessToken;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Exceptions\OAuthException;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthProviderInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class VkProvider implements OAuthProviderInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function getName(): string
    {
        return 'vk';
    }

    public function getAuthorizationUrl(string $state, array $context = []): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes'] ?? []),
            'state' => $state,
        ];

        if (! empty($this->config['api_version'])) {
            $params['v'] = $this->config['api_version'];
        }

        return $this->config['authorization_endpoint'].'?'.http_build_query($params);
    }

    public function getToken(string $code): AccessToken
    {
        $response = Http::asForm()->post($this->config['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'],
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if ($response->failed()) {
            throw new OAuthException('Не удалось получить токен VK ID: '.$response->body());
        }

        $data = $response->json();

        return new AccessToken(
            accessToken: Arr::get($data, 'access_token'),
            refreshToken: Arr::get($data, 'refresh_token'),
            expiresAt: isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in'])->toDateTimeImmutable() : null,
            idToken: Arr::get($data, 'id_token'),
            raw: $data ?? []
        );
    }

    public function getUserInfo(AccessToken $token): OAuthUser
    {
        $data = $this->decodeIdToken($token->idToken);

        if ($data === null) {
            $response = Http::withToken($token->accessToken)
                ->acceptJson()
                ->get($this->config['userinfo_endpoint']);

            if ($response->failed()) {
                throw new OAuthException('Не удалось получить профиль VK ID: '.$response->body());
            }

            $data = $response->json();
        }

        $id = (string) ($data['sub'] ?? $data['id'] ?? '');

        if ($id === '') {
            throw new OAuthException('Ответ VK ID не содержит идентификатора пользователя.');
        }

        $name = trim(implode(' ', array_filter([
            Arr::get($data, 'first_name'),
            Arr::get($data, 'last_name'),
        ])));

        if ($name === '') {
            $name = Arr::get($data, 'name');
        }

        return new OAuthUser(
            provider: $this->getName(),
            providerUserId: $id,
            email: Arr::get($data, 'email'),
            name: $name ?: null,
            avatarUrl: Arr::get($data, 'picture'),
            raw: $data ?? []
        );
    }

    private function decodeIdToken(?string $idToken): ?array
    {
        if ($idToken === null || $idToken === '') {
            return null;
        }

        $parts = explode('.', $idToken);

        if (count($parts) < 2) {
            return null;
        }

        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode(strtr($payload, '-_', '+/'));

        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);

        return is_array($data) ? $data : null;
    }
}
