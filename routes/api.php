<?php

use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\ActivityController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\BundleController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\CoinsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\SocialScoreController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\StatsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\TrustLevelController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\VerificationController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Api\ViolationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:socialprofile-public')->group(function () {
    Route::get('user/{nickname}/stats', [StatsController::class, 'show'])->name('stats.show');
    Route::get('user/{nickname}/activity', [ActivityController::class, 'show'])->name('activity.show');
    Route::get('user/{nickname}/coins', [CoinsController::class, 'show'])->name('coins.show');
    Route::get('user/{nickname}/social-score', [SocialScoreController::class, 'show'])->name('score.show');
    Route::get('user/{nickname}/trust-level', [TrustLevelController::class, 'show'])->name('trust.show');
    Route::get('user/{nickname}/violations', [ViolationsController::class, 'index'])->name('violations.index');
    Route::get('user/{nickname}/bundle', [BundleController::class, 'show'])->name('bundle.show');
    Route::get('user/{nickname}/verification', [VerificationController::class, 'show'])->name('verification.show');
});

Route::middleware('throttle:socialprofile-token')->group(function () {
    Route::put('user/{nickname}/stats', [StatsController::class, 'update'])->name('stats.update');
    Route::put('user/{nickname}/activity', [ActivityController::class, 'update'])->name('activity.update');
    Route::put('user/{nickname}/coins', [CoinsController::class, 'update'])->name('coins.update');
    Route::put('user/{nickname}/social-score', [SocialScoreController::class, 'update'])->name('score.update');
    Route::put('user/{nickname}/trust-level', [TrustLevelController::class, 'update'])->name('trust.update');
    Route::post('user/{nickname}/violations', [ViolationsController::class, 'store'])->name('violations.store');
    Route::put('user/{nickname}/verification', [VerificationController::class, 'update'])->name('verification.update');
});
