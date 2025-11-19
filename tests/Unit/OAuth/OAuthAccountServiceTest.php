<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit\OAuth;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\AccessToken;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthCallbackResult;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthFlowType;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class OAuthAccountServiceTest extends TestCase
{
    public function test_it_links_identities_and_logs_in(): void
    {
        $user = $this->createUser();
        $service = $this->app->make(OAuthAccountService::class);
        $oauthUser = new OAuthUser('yandex', 'oauth-1', 'mail@example.com', 'OAuth User');
        $token = new AccessToken('access', refreshToken: 'refresh');

        $identity = $service->linkProviderToUser('yandex', $oauthUser, $user, $token);

        $this->assertSame('oauth-1', $identity->provider_user_id);
        $this->assertTrue($user->is($service->loginWithOAuth($oauthUser)));
    }

    public function test_launcher_sessions_flow(): void
    {
        $user = $this->createUser();
        $service = $this->app->make(OAuthAccountService::class);
        $oauthUser = new OAuthUser('vk', 'vk-user');
        $service->linkProviderToUser('vk', $oauthUser, $user, new AccessToken('access'));

        $session = $service->createLoginSession('vk');
        $this->assertSame('pending', $session->status);

        $callback = new OAuthCallbackResult(
            provider: 'vk',
            flowType: OAuthFlowType::LAUNCHER_LOGIN,
            context: ['login_session_id' => $session->id],
            accessToken: new AccessToken('another'),
            user: $oauthUser
        );

        $completed = $service->handleLauncherCallback($callback);

        $this->assertSame('success', $completed->status);
        $this->assertSame($user->id, $completed->user_id);

        $failed = $service->failLauncherSession($session->id, 'provider_error');
        $this->assertSame('success', $failed->status, 'Completed sessions should stay successful');
    }
}
