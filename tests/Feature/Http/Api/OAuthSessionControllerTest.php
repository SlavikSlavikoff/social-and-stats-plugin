<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Api;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\AccessToken;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthCallbackResult;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthFlowType;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class OAuthSessionControllerTest extends TestCase
{
    public function test_launcher_session_flow(): void
    {
        $user = $this->createUser();
        $service = $this->app->make(OAuthAccountService::class);
        $service->linkProviderToUser('yandex', new OAuthUser('yandex', 'launcher-user'), $user, null);

        $response = $this->postJson('/api/social/v1/oauth/sessions', [
            'provider' => 'yandex',
        ]);

        $response->assertCreated();
        $sessionId = $response->json('session_id');

        $callback = new OAuthCallbackResult(
            provider: 'yandex',
            flowType: OAuthFlowType::LAUNCHER_LOGIN,
            context: ['login_session_id' => $sessionId],
            accessToken: new AccessToken('token'),
            user: new OAuthUser('yandex', 'launcher-user')
        );

        $service->handleLauncherCallback($callback);

        $status = $this->getJson("/api/social/v1/oauth/sessions/{$sessionId}");
        $status->assertOk()
            ->assertJson([
                'session_id' => $sessionId,
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                ],
            ]);
    }
}
