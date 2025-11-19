<?php

use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation\AutomationController as AutomationSettingsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation\AutomationIntegrationsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation\AutomationLogsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation\AutomationRulesController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation\AutomationSchedulerController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\CourtArchiveController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\CourtSettingsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\CourtTemplatesController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\DashboardController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\ProgressionRatingsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\ProgressionRulesController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\ProgressionThresholdActionsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\ProgressionThresholdsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\SettingsController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\TimelineCardController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\TimelineController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\TimelinePeriodController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\TokensController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\UsersController;
use Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\ViolationsController;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
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

Route::get('/violations', [ViolationsController::class, 'index'])
    ->middleware('can:social.moderate_violations')
    ->name('violations.index');

Route::post('/users/{user}/violations', [ViolationsController::class, 'store'])
    ->middleware('can:social.moderate_violations')
    ->name('users.violations.store');

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

Route::prefix('progression')
    ->name('progression.')
    ->middleware('can:social.progression.manage')
    ->group(function () {
        Route::get('/', [ProgressionRatingsController::class, 'index'])->name('index');
        Route::post('/ratings', [ProgressionRatingsController::class, 'store'])->name('ratings.store');
        Route::put('/ratings/{rating}', [ProgressionRatingsController::class, 'update'])->name('ratings.update');
        Route::delete('/ratings/{rating}', [ProgressionRatingsController::class, 'destroy'])->name('ratings.destroy');

        Route::get('/ratings/{rating}/thresholds', [ProgressionThresholdsController::class, 'index'])->name('thresholds.index');
        Route::post('/ratings/{rating}/thresholds', [ProgressionThresholdsController::class, 'store'])->name('thresholds.store');
        Route::put('/ratings/{rating}/thresholds/{threshold}', [ProgressionThresholdsController::class, 'update'])->name('thresholds.update');
        Route::delete('/ratings/{rating}/thresholds/{threshold}', [ProgressionThresholdsController::class, 'destroy'])->name('thresholds.destroy');

        Route::post('/thresholds/{threshold}/actions', [ProgressionThresholdActionsController::class, 'store'])->name('actions.store');
        Route::put('/actions/{action}', [ProgressionThresholdActionsController::class, 'update'])->name('actions.update');
        Route::delete('/actions/{action}', [ProgressionThresholdActionsController::class, 'destroy'])->name('actions.destroy');

        Route::get('/ratings/{rating}/rules', [ProgressionRulesController::class, 'index'])->name('rules.index');
        Route::post('/ratings/{rating}/rules', [ProgressionRulesController::class, 'store'])->name('rules.store');
        Route::put('/rules/{rule}', [ProgressionRulesController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{rule}', [ProgressionRulesController::class, 'destroy'])->name('rules.destroy');
    });

Route::prefix('automation')
    ->name('automation.')
    ->middleware('can:social.automation.manage')
    ->group(function () {
        Route::get('/', [AutomationSettingsController::class, 'index'])->name('index');

        Route::post('/rules', [AutomationRulesController::class, 'store'])->name('rules.store');
        Route::put('/rules/{rule}', [AutomationRulesController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{rule}', [AutomationRulesController::class, 'destroy'])->name('rules.destroy');

        Route::post('/integrations', [AutomationIntegrationsController::class, 'store'])->name('integrations.store');
        Route::put('/integrations/{integration}', [AutomationIntegrationsController::class, 'update'])->name('integrations.update');
        Route::delete('/integrations/{integration}', [AutomationIntegrationsController::class, 'destroy'])->name('integrations.destroy');
        Route::post('/integrations/{integration}/test', [AutomationIntegrationsController::class, 'test'])->name('integrations.test');

        Route::post('/scheduler', [AutomationSchedulerController::class, 'update'])->name('scheduler.update');

        Route::post('/logs/{log}/replay', [AutomationLogsController::class, 'replay'])->name('logs.replay');
    });

Route::middleware('can:social.timelines.manage')->prefix('timelines')->name('timelines.')->group(function () {
    Route::get('/', [TimelineController::class, 'index'])->name('index');
    Route::get('/create', [TimelineController::class, 'create'])->name('create');
    Route::post('/', [TimelineController::class, 'store'])->name('store');
    Route::get('/season-history', fn () => redirect()->route('socialprofile.admin.timelines.edit', Timeline::TYPE_SEASON_HISTORY))
        ->name('season_history');
    Route::get('/road-map', fn () => redirect()->route('socialprofile.admin.timelines.edit', Timeline::TYPE_ROAD_MAP))
        ->name('road_map');
    Route::get('/{timeline}/edit', [TimelineController::class, 'edit'])->name('edit');
    Route::put('/{timeline}', [TimelineController::class, 'update'])->name('update');
    Route::delete('/{timeline}', [TimelineController::class, 'destroy'])->name('destroy');

    Route::get('/{timeline}/periods', [TimelinePeriodController::class, 'index'])->name('periods.index');
    Route::get('/{timeline}/periods/create', [TimelinePeriodController::class, 'create'])->name('periods.create');
    Route::post('/{timeline}/periods', [TimelinePeriodController::class, 'store'])->name('periods.store');
    Route::get('/{timeline}/periods/{period}/edit', [TimelinePeriodController::class, 'edit'])->name('periods.edit');
    Route::put('/{timeline}/periods/{period}', [TimelinePeriodController::class, 'update'])->name('periods.update');
    Route::delete('/{timeline}/periods/{period}', [TimelinePeriodController::class, 'destroy'])->name('periods.destroy');
    Route::post('/{timeline}/periods/order', [TimelinePeriodController::class, 'updateOrder'])->name('periods.order');

    Route::get('/{timeline}/cards', [TimelineCardController::class, 'index'])->name('cards.index');
    Route::get('/{timeline}/cards/create', [TimelineCardController::class, 'create'])->name('cards.create');
    Route::post('/{timeline}/cards', [TimelineCardController::class, 'store'])->name('cards.store');
    Route::get('/{timeline}/cards/{card}/edit', [TimelineCardController::class, 'edit'])->name('cards.edit');
    Route::put('/{timeline}/cards/{card}', [TimelineCardController::class, 'update'])->name('cards.update');
    Route::delete('/{timeline}/cards/{card}', [TimelineCardController::class, 'destroy'])->name('cards.destroy');
    Route::post('/{timeline}/cards/order', [TimelineCardController::class, 'updateOrder'])->name('cards.order');
});

Route::prefix('court')->name('court.')->group(function () {
    Route::get('/archive', [CourtArchiveController::class, 'index'])
        ->middleware('can:social.court.archive')
        ->name('archive');

    Route::get('/settings', [CourtSettingsController::class, 'edit'])
        ->middleware('can:social.court.manage_settings')
        ->name('settings');

    Route::post('/settings/general', [CourtSettingsController::class, 'updateGeneral'])
        ->middleware('can:social.court.manage_settings')
        ->name('settings.general');

    Route::post('/settings/webhooks', [CourtSettingsController::class, 'storeWebhook'])
        ->middleware('can:social.court.webhooks')
        ->name('webhooks.store');

    Route::delete('/settings/webhooks/{webhook}', [CourtSettingsController::class, 'destroyWebhook'])
        ->middleware('can:social.court.webhooks')
        ->name('webhooks.destroy');

    Route::get('/templates', [CourtTemplatesController::class, 'index'])
        ->middleware('can:social.court.manage_settings')
        ->name('templates.index');

    Route::post('/templates', [CourtTemplatesController::class, 'store'])
        ->middleware('can:social.court.manage_settings')
        ->name('templates.manage.store');

    Route::put('/templates/{template}', [CourtTemplatesController::class, 'update'])
        ->middleware('can:social.court.manage_settings')
        ->name('templates.manage.update');

    Route::delete('/templates/{template}', [CourtTemplatesController::class, 'destroy'])
        ->middleware('can:social.court.manage_settings')
        ->name('templates.manage.destroy');

    Route::post('/templates/refresh', [CourtTemplatesController::class, 'refresh'])
        ->middleware('can:social.court.manage_settings')
        ->name('templates.refresh');
});
