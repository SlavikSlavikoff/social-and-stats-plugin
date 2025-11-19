<?php

use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\ActivityController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\BundleController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\CoinsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\CourtCasesController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\OAuthSessionController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\ProgressionController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\SocialScoreController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\StatsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\TrustLevelController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\ViolationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:socialprofile-public')->group(function () {
    Route::get('progression/ratings', [ProgressionController::class, 'ratings'])->name('progression.ratings');
    Route::get('user/{nickname}/stats', [StatsController::class, 'show'])->name('stats.show');
    Route::get('user/{nickname}/activity', [ActivityController::class, 'show'])->name('activity.show');
    Route::get('user/{nickname}/coins', [CoinsController::class, 'show'])->name('coins.show');
    Route::get('user/{nickname}/social-score', [SocialScoreController::class, 'show'])->name('score.show');
    Route::get('user/{nickname}/trust-level', [TrustLevelController::class, 'show'])->name('trust.show');
    Route::get('user/{nickname}/violations', [ViolationsController::class, 'index'])->name('violations.index');
    Route::get('user/{nickname}/bundle', [BundleController::class, 'show'])->name('bundle.show');
    Route::get('user/{nickname}/progression', [ProgressionController::class, 'show'])->name('progression.show');

    Route::get('court/public', [CourtCasesController::class, 'publicIndex'])->name('court.cases.public');

    Route::post('oauth/sessions', [OAuthSessionController::class, 'store'])->name('oauth.sessions.store');
    Route::get('oauth/sessions/{session}', [OAuthSessionController::class, 'show'])->name('oauth.sessions.show');
});

Route::middleware('throttle:socialprofile-token')->group(function () {
    Route::put('user/{nickname}/stats', [StatsController::class, 'update'])->name('stats.update');
    Route::put('user/{nickname}/activity', [ActivityController::class, 'update'])->name('activity.update');
    Route::put('user/{nickname}/coins', [CoinsController::class, 'update'])->name('coins.update');
    Route::put('user/{nickname}/social-score', [SocialScoreController::class, 'update'])->name('score.update');
    Route::put('user/{nickname}/trust-level', [TrustLevelController::class, 'update'])->name('trust.update');
    Route::post('user/{nickname}/violations', [ViolationsController::class, 'store'])->name('violations.store');
    Route::post('user/{nickname}/progression/events', [ProgressionController::class, 'storeEvent'])->name('progression.events.store');
});

Route::middleware(['throttle:socialprofile-court-internal', 'auth', 'can:social.court.judge'])->group(function () {
    Route::get('court/cases', [CourtCasesController::class, 'internalIndex'])->name('court.cases.index');
    Route::post('court/cases', [CourtCasesController::class, 'store'])->name('court.cases.store');
    Route::get('court/cases/{case}', [CourtCasesController::class, 'show'])->name('court.cases.show');
});
