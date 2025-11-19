<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit\OAuth;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers\VkProvider;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class VkProviderTest extends TestCase
{
    public function test_authorization_url_contains_scope(): void
    {
        $provider = $this->makeProvider();

        $url = $provider->getAuthorizationUrl('state-xyz');

        $this->assertStringContainsString('client_id=vk-client', $url);
        $this->assertStringContainsString('scope=openid+email', $url);
    }

    public function test_it_fetches_token_and_profile(): void
    {
        Http::fake([
            'id.vk.com/oauth2/token' => Http::response([
                'access_token' => 'access',
                'refresh_token' => 'refresh',
                'expires_in' => 7200,
                'id_token' => $this->fakeIdToken(['sub' => '123', 'email' => 'vk@example.com']),
            ], 200),
        ]);

        $provider = $this->makeProvider();
        $token = $provider->getToken('code-abc');
        $user = $provider->getUserInfo($token);

        $this->assertSame('access', $token->accessToken);
        $this->assertSame('123', $user->providerUserId);
        $this->assertSame('vk@example.com', $user->email);
    }

    private function makeProvider(): VkProvider
    {
        return new VkProvider([
            'client_id' => 'vk-client',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://example.com/oauth/callback/vk',
            'authorization_endpoint' => 'https://id.vk.com/authorize',
            'token_endpoint' => 'https://id.vk.com/oauth2/token',
            'userinfo_endpoint' => 'https://id.vk.com/oauth2/userinfo',
            'scopes' => ['openid', 'email'],
            'api_version' => '5.131',
        ]);
    }

    private function fakeIdToken(array $payload): string
    {
        $header = $this->encodeSegment(['alg' => 'none']);
        $body = $this->encodeSegment($payload);

        return "{$header}.{$body}.";
    }

    private function encodeSegment(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }
}
