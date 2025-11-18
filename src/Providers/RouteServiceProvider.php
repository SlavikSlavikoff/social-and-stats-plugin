<?php

namespace Azuriom\Plugin\SocialProfile\Providers;

use Azuriom\Extensions\Plugin\BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     * Define the routes for the application.
     */
    public function loadRoutes(): void
    {
        Route::middleware('web')
            ->group(plugin_path('socialprofile/routes/web.php'));

        Route::middleware(['web', 'admin-access'])
            ->prefix('admin/socialprofile')
            ->name('socialprofile.admin.')
            ->group(plugin_path('socialprofile/routes/admin.php'));

        Route::middleware('api')
            ->prefix('api/social/v1')
            ->name('socialprofile.api.')
            ->group(plugin_path('socialprofile/routes/api.php'));
    }
}
