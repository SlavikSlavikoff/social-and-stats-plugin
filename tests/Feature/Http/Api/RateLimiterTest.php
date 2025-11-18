<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Api;

use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiterTest extends TestCase
{
    public function test_public_rate_limiter_is_respected(): void
    {
        $user = $this->createBasicUser(['name' => 'Limited']);
        setting()->set('socialprofile_public_rate_limit', 1);

        RateLimiter::clear('socialprofile-public|127.0.0.1');

        $this->json('GET', '/api/social/v1/user/'.$user->name.'/stats')->assertSuccessful();
        $this->json('GET', '/api/social/v1/user/'.$user->name.'/stats')->assertStatus(429);
    }

    public function test_token_rate_limiter_is_respected(): void
    {
        $user = $this->createBasicUser(['name' => 'TokenLimited']);
        [$plain, $token] = $this->issueToken([
            'scopes' => ['stats:write'],
            'rate_limit' => ['per_minute' => 1],
        ]);

        RateLimiter::clear('socialprofile-token|token-'.$token->id);

        $payload = [
            'played_minutes' => 10,
            'kills' => 0,
            'deaths' => 0,
        ];

        $this->json('PUT', '/api/social/v1/user/'.$user->name.'/stats', $payload, [
            'Authorization' => 'Bearer '.$plain,
        ])->assertSuccessful();

        $this->json('PUT', '/api/social/v1/user/'.$user->name.'/stats', $payload, [
            'Authorization' => 'Bearer '.$plain,
        ])->assertStatus(429);
    }
}
