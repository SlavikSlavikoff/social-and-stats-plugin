<?php

use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\LeaderboardController;
use Illuminate\Support\Facades\Route;

Route::get('/leaderboards/social', [LeaderboardController::class, 'index'])->name('socialprofile.leaderboards.index');
