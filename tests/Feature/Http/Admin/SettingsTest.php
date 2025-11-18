<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_admin_can_update_settings(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/socialprofile/settings', [
            'public_rate_limit' => 30,
            'token_rate_limit' => 90,
            'show_coins_public' => 1,
            'enable_hmac' => 1,
            'hmac_secret' => 'integration',
        ]);

        $response->assertRedirect();

        $this->assertEquals(30, setting('socialprofile_public_rate_limit'));
        $this->assertEquals(90, setting('socialprofile_token_rate_limit'));
        $this->assertTrue(setting('socialprofile_show_coins_public'));
        $this->assertTrue(setting('socialprofile_enable_hmac'));
        $this->assertEquals('integration', setting('socialprofile_hmac_secret'));
    }
}
