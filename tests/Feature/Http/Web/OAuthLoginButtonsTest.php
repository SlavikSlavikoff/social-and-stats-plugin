<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Web;

use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class OAuthLoginButtonsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('socialprofile.oauth.providers.vk.client_id', 'vk-test');
        config()->set('socialprofile.oauth.providers.vk.client_secret', 'secret');
        config()->set('socialprofile.oauth.providers.yandex.client_id', 'ya-test');
        config()->set('socialprofile.oauth.providers.yandex.client_secret', 'secret');
    }

    public function test_login_page_contains_social_section(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(__('socialprofile::messages.oauth.login_with'), false);
        $response->assertSee('VK ID', false);
        $response->assertSee('Yandex ID', false);
    }
}
