<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\SocialProfile\Models\ActivityPoint;
use Azuriom\Plugin\SocialProfile\Models\SocialScore;

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
