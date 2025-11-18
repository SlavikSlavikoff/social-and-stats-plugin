<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;

class LeaderboardController extends Controller
{
    public function index()
    {
        $activityLeaders = ActivityPoint::with('user')->orderByDesc('points')->limit(50)->get();
        $scoreLeaders = SocialScore::with('user')->orderByDesc('score')->limit(50)->get();

        return view('socialprofile::leaderboards', [
            'activityLeaders' => $activityLeaders,
            'scoreLeaders' => $scoreLeaders,
        ]);
    }
}
