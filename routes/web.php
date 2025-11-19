<?php

use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\CourtController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\LeaderboardController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\OAuthCallbackController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\OAuthLoginController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\OAuthProfileController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Web\TimelinePublicController;
use Illuminate\Support\Facades\Route;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\CourtDecisionsController;

Route::get('/leaderboards/social', [LeaderboardController::class, 'index'])->name('socialprofile.leaderboards.index');

Route::get('/season-history', [TimelinePublicController::class, 'showSeasonHistory'])
    ->name('socialprofile.timelines.season_history');

Route::get('/road-map', [TimelinePublicController::class, 'showRoadMap'])
    ->name('socialprofile.timelines.road_map');

Route::prefix('court')
    ->middleware('auth')
    ->name('socialprofile.court.')
    ->group(function () {
        Route::middleware('can:social.court.archive')->group(function () {
            Route::get('/', [CourtController::class, 'index'])->name('index');
        });

        Route::middleware('can:social.court.judge')->group(function () {
            Route::get('/judge', [CourtController::class, 'judge'])->name('judge');
            Route::post('/decisions/auto', [CourtDecisionsController::class, 'storeAuto'])->name('decisions.auto.store');
            Route::post('/decisions/manual', [CourtDecisionsController::class, 'storeManual'])->name('decisions.manual.store');
        });
    });

Route::prefix('oauth')
    ->name('socialprofile.oauth.')
    ->group(function () {
        Route::middleware('auth')->group(function () {
            Route::get('/link/{provider}', [OAuthProfileController::class, 'redirect'])->name('link');
            Route::delete('/link/{provider}', [OAuthProfileController::class, 'destroy'])->name('link.destroy');
        });

        Route::get('/login/{provider}', [OAuthLoginController::class, 'redirect'])->name('login');
        Route::get('/callback/{provider}', OAuthCallbackController::class)->name('callback');
    });
