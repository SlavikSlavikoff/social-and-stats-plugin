<?php

use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\DashboardController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\SettingsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\TokensController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\UsersController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\ViolationsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])
    ->middleware('can:social.view')
    ->name('dashboard');

Route::get('/users', [UsersController::class, 'index'])
    ->middleware('can:social.edit')
    ->name('users.index');

Route::get('/users/{user}', [UsersController::class, 'show'])
    ->middleware('can:social.edit')
    ->name('users.show');

Route::post('/users/{user}/metrics', [UsersController::class, 'updateMetrics'])
    ->middleware('can:social.edit')
    ->name('users.metrics.update');

Route::post('/users/{user}/trust', [UsersController::class, 'updateTrust'])
    ->middleware('can:social.grant_trust')
    ->name('users.trust.update');

Route::post('/users/{user}/verification', [UsersController::class, 'updateVerification'])
    ->middleware('can:social.verify_accounts')
    ->name('users.verification.update');

Route::post('/users/{user}/violations', [UsersController::class, 'storeViolation'])
    ->middleware('can:social.moderate_violations')
    ->name('users.violations.store');

Route::get('/violations', [ViolationsController::class, 'index'])
    ->middleware('can:social.moderate_violations')
    ->name('violations.index');

Route::post('/violations', [ViolationsController::class, 'store'])
    ->middleware('can:social.moderate_violations')
    ->name('violations.store');

Route::delete('/violations/{violation}', [ViolationsController::class, 'destroy'])
    ->middleware('can:social.moderate_violations')
    ->name('violations.destroy');

Route::get('/tokens', [TokensController::class, 'index'])
    ->middleware('can:social.manage_tokens')
    ->name('tokens.index');

Route::post('/tokens', [TokensController::class, 'store'])
    ->middleware('can:social.manage_tokens')
    ->name('tokens.store');

Route::put('/tokens/{token}', [TokensController::class, 'update'])
    ->middleware('can:social.manage_tokens')
    ->name('tokens.update');

Route::post('/tokens/{token}/rotate', [TokensController::class, 'rotate'])
    ->middleware('can:social.manage_tokens')
    ->name('tokens.rotate');

Route::delete('/tokens/{token}', [TokensController::class, 'destroy'])
    ->middleware('can:social.manage_tokens')
    ->name('tokens.destroy');

Route::get('/settings', [SettingsController::class, 'edit'])
    ->middleware('can:social.edit')
    ->name('settings.edit');

Route::post('/settings', [SettingsController::class, 'update'])
    ->middleware('can:social.edit')
    ->name('settings.update');
