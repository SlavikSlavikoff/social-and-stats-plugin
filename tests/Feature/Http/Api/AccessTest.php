<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Api;

use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class AccessTest extends TestCase
{
    public function test_write_requires_scope_and_ip(): void
    {
        $user = $this->createBasicUser(['name' => 'ScopeTester']);

        [$plain] = $this->issueToken([
            'scopes' => ['stats:read'],
            'allowed_ips' => ['127.0.0.2'],
        ]);

        $response = $this->json('PUT', '/api/social/v1/user/'.$user->name.'/stats', [
            'played_minutes' => 10,
            'kills' => 1,
            'deaths' => 0,
        ], [
            'Authorization' => 'Bearer '.$plain,
        ]);

        $response->assertStatus(403);

        // Ensure an allowed scope but wrong IP is still rejected.
        [$plain, $token] = $this->issueToken([
            'scopes' => ['stats:write'],
            'allowed_ips' => ['198.51.100.5'],
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->json('PUT', '/api/social/v1/user/'.$user->name.'/stats', [
                'played_minutes' => 10,
                'kills' => 1,
                'deaths' => 0,
            ], [
                'Authorization' => 'Bearer '.$plain,
            ]);

        $response->assertStatus(403);
    }

    public function test_hmac_signature_is_enforced_when_enabled(): void
    {
        $user = $this->createBasicUser(['name' => 'HmacUser']);

        [$plain] = $this->issueToken([
            'scopes' => ['stats:write'],
            'allowed_ips' => null,
        ]);

        setting()->set([
            'socialprofile_enable_hmac' => true,
            'socialprofile_hmac_secret' => 'secret',
        ]);

        $payload = [
            'played_minutes' => 42,
            'kills' => 5,
            'deaths' => 2,
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $response = $this->json('PUT', '/api/social/v1/user/'.$user->name.'/stats', $payload, [
            'Authorization' => 'Bearer '.$plain,
        ]);

        $response->assertStatus(401);

        $signature = hash_hmac('sha256', $body, 'secret');

        $response = $this->json('PUT', '/api/social/v1/user/'.$user->name.'/stats', $payload, [
            'Authorization' => 'Bearer '.$plain,
            'X-Social-Signature' => $signature,
        ]);

        $response->assertSuccessful();
        $this->assertEquals(42, GameStatistic::where('user_id', $user->id)->first()->played_minutes);
    }

    public function test_admin_user_can_write_without_token(): void
    {
        $target = $this->createBasicUser(['name' => 'AdminTarget']);

        $response = $this->actingAs($this->createAdminUser())
            ->json('PUT', '/api/social/v1/user/'.$target->name.'/stats', [
                'played_minutes' => 5,
                'kills' => 0,
                'deaths' => 0,
            ]);

        $response->assertSuccessful();
        $this->assertEquals(5, GameStatistic::where('user_id', $target->id)->first()->played_minutes);
    }
}
