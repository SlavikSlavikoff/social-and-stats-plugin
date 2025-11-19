<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit\OAuth;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers\YandexProvider;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class YandexProviderTest extends TestCase
{
    public function test_authorization_url_contains_expected_data(): void
    {
        $provider = $this->makeProvider();

        $url = $provider->getAuthorizationUrl('state-123');

        $this->assertStringContainsString('client_id=test-client', $url);
        $this->assertStringContainsString('state=state-123', $url);
        $this->assertStringContainsString('scope=login%3Aemail+login%3Ainfo', $url);
    }

    public function test_it_fetches_token_and_user_info(): void
    {
        Http::fake([
            'oauth.yandex.ru/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
            ], 200),
            'login.yandex.ru/info*' => Http::response([
                'id' => 42,
                'real_name' => 'OAuth User',
                'default_email' => 'mail@example.com',
            ], 200),
        ]);

        $provider = $this->makeProvider();
        $token = $provider->getToken('code-123');
        $user = $provider->getUserInfo($token);

        $this->assertSame('access-token', $token->accessToken);
        $this->assertSame('yandex', $user->provider);
        $this->assertSame('42', $user->providerUserId);
        $this->assertSame('mail@example.com', $user->email);
    }

    private function makeProvider(): YandexProvider
    {
        return new YandexProvider([
            'client_id' => 'test-client',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://example.com/oauth/callback/yandex',
            'authorization_endpoint' => 'https://oauth.yandex.ru/authorize',
            'token_endpoint' => 'https://oauth.yandex.ru/token',
            'userinfo_endpoint' => 'https://login.yandex.ru/info',
            'scopes' => ['login:email', 'login:info'],
        ]);
    }
}
