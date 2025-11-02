<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\SocialProfile\Models\ActivityPoint;
use Azuriom\Plugin\SocialProfile\Models\SocialScore;
use Azuriom\Plugin\SocialProfile\Models\Verification;
use Azuriom\Plugin\SocialProfile\Models\Violation;

class DashboardController extends Controller
{
    public function index()
    {
        $topScores = SocialScore::with('user')->orderByDesc('score')->limit(5)->get();
        $topActivity = ActivityPoint::with('user')->orderByDesc('points')->limit(5)->get();
        $recentViolations = Violation::with(['user', 'issuer'])->latest()->limit(5)->get();
        $pendingVerifications = Verification::with('user')->where('status', 'pending')->limit(5)->get();

        return view('socialprofile::admin.dashboard', [
            'topScores' => $topScores,
            'topActivity' => $topActivity,
            'recentViolations' => $recentViolations,
            'pendingVerifications' => $pendingVerifications,
        ]);
    }
}
