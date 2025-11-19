<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Web;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class OAuthFlowTest extends TestCase
{
    public function test_authenticated_user_can_link_oauth_identity(): void
    {
        Http::fake([
            'oauth.yandex.ru/token' => Http::response([
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'expires_in' => 3600,
            ], 200),
            'login.yandex.ru/info*' => Http::response([
                'id' => 777,
                'real_name' => 'Linked User',
                'default_email' => 'linked@example.com',
            ], 200),
        ]);

        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/oauth/link/yandex?redirect=/profile');
        $response->assertRedirect();

        $state = $this->extractStateFromLocation($response->headers->get('Location'));

        $callback = $this->get("/oauth/callback/yandex?code=test-code&state={$state}");
        $callback->assertRedirect('/profile');

        $this->assertDatabaseHas('socialprofile_oauth_identities', [
            'user_id' => $user->id,
            'provider' => 'yandex',
            'provider_user_id' => '777',
        ]);
    }

    public function test_user_can_log_in_through_oauth_when_identity_exists(): void
    {
        Http::fake([
            'oauth.yandex.ru/token' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600,
            ], 200),
            'login.yandex.ru/info*' => Http::response([
                'id' => 999,
                'real_name' => 'OAuth Login',
            ], 200),
        ]);

        $user = $this->createUser();
        /** @var OAuthAccountService $service */
        $service = $this->app->make(OAuthAccountService::class);
        $service->linkProviderToUser('yandex', new OAuthUser('yandex', '999'), $user, null);

        $response = $this->get('/oauth/login/yandex?redirect=/dashboard');
        $response->assertRedirect();

        $state = $this->extractStateFromLocation($response->headers->get('Location'));

        $callback = $this->get("/oauth/callback/yandex?code=another-code&state={$state}");
        $callback->assertRedirect('/dashboard');

        $this->assertTrue(Auth::check());
        $this->assertTrue(Auth::user()->is($user));
    }

    private function extractStateFromLocation(?string $location): string
    {
        $this->assertNotNull($location, 'Redirect location was not provided.');

        $query = parse_url($location, PHP_URL_QUERY);
        parse_str((string) $query, $params);

        return $params['state'] ?? '';
    }
}
