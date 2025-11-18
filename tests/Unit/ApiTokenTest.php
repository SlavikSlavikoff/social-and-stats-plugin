<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Unit;

use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Http\Request;

class ApiTokenTest extends TestCase
{
    public function test_scope_matching_rules(): void
    {
        $token = ApiToken::create([
            'name' => 'Scopes',
            'token_hash' => ApiToken::hash('plain'),
            'scopes' => ['stats:*', 'coins:read'],
        ]);

        $this->assertTrue($token->allowsScope('stats:write'));
        $this->assertTrue($token->allowsScope('stats:read'));
        $this->assertTrue($token->allowsScope('coins:read'));
        $this->assertFalse($token->allowsScope('coins:write'));
        $this->assertFalse($token->allowsScope('trust:read'));
    }

    public function test_global_wildcard_allows_everything(): void
    {
        $token = ApiToken::create([
            'name' => 'Wildcard',
            'token_hash' => ApiToken::hash('plain'),
            'scopes' => ['*'],
        ]);

        $this->assertTrue($token->allowsScope('any:thing'));
    }

    public function test_ip_range_check(): void
    {
        $token = ApiToken::create([
            'name' => 'IP Guard',
            'token_hash' => ApiToken::hash('plain'),
            'scopes' => ['*'],
            'allowed_ips' => ['203.0.113.10', '10.0.0.1'],
        ]);

        $this->assertTrue($token->withinIpRange('203.0.113.10'));
        $this->assertFalse($token->withinIpRange('198.51.100.9'));
    }

    public function test_find_from_request_matches_bearer_token(): void
    {
        [$plain, $token] = $this->issueToken([
            'allowed_ips' => null,
            'rate_limit' => null,
        ]);

        $request = Request::create('/api/test', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$plain,
        ]);

        $found = ApiToken::findFromRequest($request);

        $this->assertNotNull($found);
        $this->assertTrue($token->is($found));
    }
}
