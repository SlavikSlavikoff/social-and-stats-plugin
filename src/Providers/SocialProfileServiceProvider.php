<?php

namespace Azuriom\Plugin\SocialProfile\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Azuriom\Plugin\SocialProfile\Models\ApiToken;

class SocialProfileServiceProvider extends BasePluginServiceProvider
{
    /**
     * Register any plugin services.
     */
    public function register(): void
    {
        // No bindings required for v1.
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->loadMigrations();
        $this->registerPermissions();
        $this->registerRouteDescriptions();
        $this->registerAdminNavigation();
        $this->registerUserNavigation();
        $this->registerRateLimiters();
    }

    /**
     * Returns the routes that should be able to be added to the navbar.
     *
     * @return array<string, string>
     */
    protected function routeDescriptions(): array
    {
        return [
            'socialprofile.leaderboards.index' => __('socialprofile::messages.leaderboards.title'),
            'socialprofile.profile.show' => __('socialprofile::messages.profile.title'),
        ];
    }

    /**
     * Return the admin navigations routes to register in the dashboard.
     *
     * @return array<string, array<string, string>>
     */
    protected function adminNavigation(): array
    {
        return [
            __('socialprofile::messages.admin.nav.dashboard') => [
                'route' => 'socialprofile.admin.dashboard',
                'icon' => 'fas fa-chart-network',
                'permission' => 'social.view',
            ],
            __('socialprofile::messages.admin.nav.users') => [
                'route' => 'socialprofile.admin.users.index',
                'icon' => 'fas fa-users',
                'permission' => 'social.edit',
            ],
            __('socialprofile::messages.admin.nav.violations') => [
                'route' => 'socialprofile.admin.violations.index',
                'icon' => 'fas fa-exclamation-triangle',
                'permission' => 'social.moderate_violations',
            ],
            __('socialprofile::messages.admin.nav.tokens') => [
                'route' => 'socialprofile.admin.tokens.index',
                'icon' => 'fas fa-key',
                'permission' => 'social.manage_tokens',
            ],
            __('socialprofile::messages.admin.nav.settings') => [
                'route' => 'socialprofile.admin.settings.edit',
                'icon' => 'fas fa-sliders-h',
                'permission' => 'social.edit',
            ],
        ];
    }

    /**
     * Return the user navigations routes to register in the user menu.
     *
     * @return array<string, array<string, string>>
     */
    protected function userNavigation(): array
    {
        return [
            __('socialprofile::messages.profile.menu') => [
                'route' => 'socialprofile.profile.show',
                'icon' => 'fas fa-id-card',
            ],
            __('socialprofile::messages.leaderboards.menu') => [
                'route' => 'socialprofile.leaderboards.index',
                'icon' => 'fas fa-trophy',
            ],
        ];
    }

    /**
     * Register plugin permissions.
     */
    protected function registerPermissions(): void
    {
        $permissions = [
            'social.view' => __('socialprofile::messages.permissions.view'),
            'social.edit' => __('socialprofile::messages.permissions.edit'),
            'social.grant_trust' => __('socialprofile::messages.permissions.grant_trust'),
            'social.manage_tokens' => __('socialprofile::messages.permissions.manage_tokens'),
            'social.moderate_violations' => __('socialprofile::messages.permissions.moderate_violations'),
            'social.verify_accounts' => __('socialprofile::messages.permissions.verify_accounts'),
        ];

        foreach ($permissions as $permission => $description) {
            Permission::firstOrCreate([
                'permission' => $permission,
                'plugin' => $this->plugin->id,
            ], [
                'description' => $description,
            ]);
        }
    }

    /**
     * Register the rate limiters used by the plugin.
     */
    protected function registerRateLimiters(): void
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
}
