<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature\Http\Admin;

use Azuriom\Plugin\InspiratoStats\Events\ActivityChanged;
use Azuriom\Plugin\InspiratoStats\Events\CoinsChanged;
use Azuriom\Plugin\InspiratoStats\Events\SocialStatsUpdated;
use Azuriom\Plugin\InspiratoStats\Events\TrustLevelChanged;
use Azuriom\Plugin\InspiratoStats\Events\VerificationChanged;
use Azuriom\Plugin\InspiratoStats\Events\ViolationAdded;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Models\Verification;
use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class UsersTest extends TestCase
{
    public function test_admin_can_update_metrics_and_fire_events(): void
    {
        Event::fake([
            SocialStatsUpdated::class,
            ActivityChanged::class,
            CoinsChanged::class,
        ]);

        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();

        $response = $this->actingAs($admin)->post("/admin/socialprofile/users/{$user->id}/metrics", [
            'score' => 150,
            'activity' => 500,
            'balance' => 200,
            'hold' => 25,
            'played_minutes' => 60,
            'kills' => 3,
            'deaths' => 1,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        Event::assertDispatched(SocialStatsUpdated::class);
        Event::assertDispatched(ActivityChanged::class);
        Event::assertDispatched(CoinsChanged::class);
    }

    public function test_admin_can_manage_trust_and_verification(): void
    {
        Event::fake([TrustLevelChanged::class, VerificationChanged::class]);

        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();

        $this->actingAs($admin)->post("/admin/socialprofile/users/{$user->id}/trust", [
            'level' => TrustLevel::LEVELS[2],
            'note' => 'Promoted',
        ])->assertRedirect();

        Event::assertDispatched(TrustLevelChanged::class);
        $this->assertEquals('Promoted', TrustLevel::firstWhere('user_id', $user->id)?->note);

        $this->actingAs($admin)->post("/admin/socialprofile/users/{$user->id}/verification", [
            'status' => 'verified',
            'method' => 'manual',
            'meta' => ['reviewed_by' => 'admin'],
        ])->assertRedirect();

        Event::assertDispatched(VerificationChanged::class);
        $this->assertEquals('verified', Verification::firstWhere('user_id', $user->id)?->status);
    }

    public function test_admin_can_record_violations(): void
    {
        Event::fake([ViolationAdded::class]);

        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();

        $response = $this->actingAs($admin)->post("/admin/socialprofile/users/{$user->id}/violations", [
            'type' => 'warning',
            'reason' => 'Testing violation',
            'points' => 5,
            'evidence_url' => 'https://example.com/evidence',
        ]);

        $response->assertRedirect();
        Event::assertDispatched(ViolationAdded::class);
        $this->assertDatabaseHas('socialprofile_violations', [
            'user_id' => $user->id,
            'type' => 'warning',
        ]);
    }
}
