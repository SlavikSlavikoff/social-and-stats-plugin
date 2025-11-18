<?php

namespace Azuriom\Plugin\InspiratoStats\Tests;

use Azuriom\Models\Permission;
use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase as BaseTestCase;
use Azuriom\Plugin\InspiratoStats\Tests\Concerns\CreatesUser;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use CreatesUser;

    /**
     * Flag to avoid registering permissions multiple times.
     */
    protected static bool $pluginPermissionsRegistered = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPluginSettings();
        $this->registerPluginRateLimiters();
        $this->registerPluginPermissions();
        $this->loadPluginRoutes();
    }

    protected function afterRefreshingDatabase(): void
    {
        $this->artisan('migrate', [
            '--path' => 'plugins/inspiratostats/database/migrations',
        ]);
    }

    protected function registerPluginPermissions(): void
    {
        if (self::$pluginPermissionsRegistered) {
            return;
        }

        $permissions = [
            'social.view' => 'View social dashboard',
            'social.edit' => 'Edit social metrics',
            'social.grant_trust' => 'Manage trust levels',
            'social.manage_tokens' => 'Manage social tokens',
            'social.moderate_violations' => 'Moderate violations',
        ];

        foreach ($permissions as $permission => $description) {
            Permission::registerPermissions([$permission => $description]);
        }

        self::$pluginPermissionsRegistered = true;
    }

    protected function registerPluginRateLimiters(): void
    {
        RateLimiter::for('socialprofile-public', function (Request $request) {
            $perMinute = (int) setting('socialprofile_public_rate_limit', 60);

            return Limit::perMinute($perMinute)->by($request->ip());
        });

        RateLimiter::for('socialprofile-token', function (Request $request) {
            $token = ApiToken::findFromRequest($request);
            $key = $token?->id ? 'token-'.$token->id : 'ip-'.$request->ip();
            $limitConfig = $token?->rate_limit ?? [];
            $perMinute = (int) ($limitConfig['per_minute'] ?? setting('socialprofile_token_rate_limit', 120));

            return Limit::perMinute($perMinute)->by($key);
        });
    }

    protected function loadPluginRoutes(): void
    {
        Route::middleware('web')
            ->group(function () {
                require base_path('plugins/inspiratostats/routes/web.php');
            });

        Route::middleware(['web', 'admin-access'])
            ->prefix('admin/socialprofile')
            ->name('socialprofile.admin.')
            ->group(function () {
                require base_path('plugins/inspiratostats/routes/admin.php');
            });

        Route::middleware('api')
            ->prefix('api/social/v1')
            ->name('socialprofile.api.')
            ->group(function () {
                require base_path('plugins/inspiratostats/routes/api.php');
            });

        app('router')->getRoutes()->refreshNameLookups();
    }

    protected function seedPluginSettings(): void
    {
        $settings = setting();

        if (! $settings->has('socialprofile_public_rate_limit')) {
            $settings->set('socialprofile_public_rate_limit', 60);
        }

        if (! $settings->has('socialprofile_token_rate_limit')) {
            $settings->set('socialprofile_token_rate_limit', 120);
        }

        if (! $settings->has('socialprofile_show_coins_public')) {
            $settings->set('socialprofile_show_coins_public', true);
        }

        if (! $settings->has('socialprofile_enable_hmac')) {
            $settings->set('socialprofile_enable_hmac', false);
        }
    }
}
