<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Web;

use Azuriom\Plugin\InspiratoStats\Models\OAuthIdentity;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class ProfileSecurityCardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('socialprofile.oauth.providers.vk.client_id', 'vk-test');
        config()->set('socialprofile.oauth.providers.vk.client_secret', 'secret');
        config()->set('socialprofile.oauth.providers.yandex.client_id', 'ya-test');
        config()->set('socialprofile.oauth.providers.yandex.client_secret', 'secret');
    }

    public function test_security_card_lists_providers(): void
    {
        $user = $this->createUser(['money' => 0]);

        $this->actingAs($user);

        $response = $this->get(route('profile.index'));

        $response->assertOk();
        $response->assertSee(__('socialprofile::messages.profile.cards.security.title'), false);
        $response->assertSee(__('socialprofile::messages.profile.cards.security.link'), false);
        $response->assertSee('VK ID', false);
        $response->assertSee('Yandex ID', false);
    }

    public function test_security_card_marks_linked_providers(): void
    {
        $user = $this->createUser(['money' => 0]);

        OAuthIdentity::create([
            'user_id' => $user->id,
            'provider' => 'vk',
            'provider_user_id' => 'vk-user',
            'access_token' => 'token',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('profile.index'));

        $response->assertOk();
        $response->assertSee(__('socialprofile::messages.profile.cards.security.linked'), false);
        $response->assertSee(__('socialprofile::messages.profile.cards.security.unlink'), false);
    }
}
