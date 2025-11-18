<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Api;

use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\Verification;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;

class CoinsVisibilityTest extends TestCase
{
    public function test_public_calls_hide_balance_for_unverified_users(): void
    {
        $user = $this->createBasicUser(['name' => 'Wallet']);
        CoinBalance::firstOrCreate(['user_id' => $user->id])->update(['balance' => 250]);

        $response = $this->json('GET', '/api/social/v1/user/'.$user->name.'/coins');

        $response->assertOk();
        $this->assertNull($response->json('balance'));
        $this->assertArrayNotHasKey('hold', $response->json());
    }

    public function test_verified_users_can_expose_balance_when_flag_enabled(): void
    {
        $user = $this->createBasicUser(['name' => 'Wallet']);
        CoinBalance::firstOrCreate(['user_id' => $user->id])->update(['balance' => 125.75, 'hold' => 10]);
        Verification::firstOrCreate(['user_id' => $user->id])->update(['status' => 'verified']);

        setting()->set('socialprofile_show_coins_public', true);

        $response = $this->json('GET', '/api/social/v1/user/'.$user->name.'/coins');

        $response->assertOk();
        $this->assertSame(125.75, $response->json('balance'));
        $this->assertArrayNotHasKey('hold', $response->json());
    }

    public function test_bundle_includes_private_fields_when_full_access(): void
    {
        $user = $this->createBasicUser(['name' => 'Wallet']);
        CoinBalance::firstOrCreate(['user_id' => $user->id])->update(['balance' => 300, 'hold' => 50]);
        Verification::firstOrCreate(['user_id' => $user->id])->update(['status' => 'verified', 'method' => 'manual']);

        [$plain] = $this->issueToken([
            'scopes' => ['bundle:read'],
            'allowed_ips' => null,
        ]);

        $response = $this->json('GET', '/api/social/v1/user/'.$user->name.'/bundle', [], [
            'Authorization' => 'Bearer '.$plain,
        ]);

        $response->assertOk();
        $this->assertSame(300.0, (float) $response->json('coins'));
        $this->assertSame(50.0, (float) $response->json('coins_hold', 0));
        $this->assertSame('manual', $response->json('verification.method'));
    }
}
