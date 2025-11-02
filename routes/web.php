<?php

use Azuriom\Plugin\SocialProfile\Http\Controllers\Web\LeaderboardController;
use Azuriom\Plugin\SocialProfile\Http\Controllers\Web\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/account/social', [ProfileController::class, 'show'])->name('socialprofile.profile.show');
});

Route::get('/leaderboards/social', [LeaderboardController::class, 'index'])->name('socialprofile.leaderboards.index');
