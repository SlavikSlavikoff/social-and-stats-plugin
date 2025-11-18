<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class TokensTest extends TestCase
{
    public function test_admin_can_create_update_rotate_and_delete_tokens(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/socialprofile/tokens', [
            'name' => 'CI Token',
            'scopes' => ['stats:read', 'stats:write'],
            'allowed_ips' => "127.0.0.1\n10.0.0.5",
            'rate_limit' => 10,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('socialprofile_generated_token');

        $token = ApiToken::first();

        $this->actingAs($admin)->put("/admin/socialprofile/tokens/{$token->id}", [
            'name' => 'CI Token v2',
            'scopes' => ['stats:read'],
            'allowed_ips' => null,
            'rate_limit' => null,
        ])->assertRedirect();

        $this->assertEquals('CI Token v2', $token->fresh()->name);

        $this->actingAs($admin)->post("/admin/socialprofile/tokens/{$token->id}/rotate")
            ->assertRedirect()
            ->assertSessionHas('socialprofile_generated_token');

        $this->actingAs($admin)->delete("/admin/socialprofile/tokens/{$token->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('socialprofile_api_tokens', ['id' => $token->id]);
    }
}
