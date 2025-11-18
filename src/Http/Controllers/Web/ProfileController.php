<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Models\Verification;
use Azuriom\Plugin\InspiratoStats\Models\Violation;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        abort_if($user === null, 403);

        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
        $activity = ActivityPoint::firstOrCreate(['user_id' => $user->id]);
        $coins = CoinBalance::firstOrCreate(['user_id' => $user->id]);
        $stats = GameStatistic::firstOrCreate(['user_id' => $user->id]);
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);
        $verification = Verification::firstOrCreate(['user_id' => $user->id]);
        $violations = Violation::where('user_id', $user->id)->latest()->limit(10)->get();

        return view('socialprofile::profile', [
            'user' => $user,
            'score' => $score,
            'activity' => $activity,
            'coins' => $coins,
            'stats' => $stats,
            'trust' => $trust,
            'verification' => $verification,
            'violations' => $violations,
        ]);
    }
}
