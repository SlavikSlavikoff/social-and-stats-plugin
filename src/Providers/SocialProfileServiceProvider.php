<?php

namespace Azuriom\Plugin\InspiratoStats\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;
use Azuriom\Models\Role;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SocialProfileServiceProvider extends BasePluginServiceProvider
{
    private const BASELINE_CACHE_KEY = 'socialprofile.seed.version';
    private const BASELINE_VERSION = 1;

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
        $this->initializeUserRecords();
        $this->registerRoleListener();
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
        $items = [
            'socialprofile.admin.dashboard' => [
                'name' => __('socialprofile::messages.admin.nav.dashboard'),
                'permission' => 'social.view',
            ],
            'socialprofile.admin.users.index' => [
                'name' => __('socialprofile::messages.admin.nav.users'),
                'permission' => 'social.edit',
            ],
            'socialprofile.admin.violations.index' => [
                'name' => __('socialprofile::messages.admin.nav.violations'),
                'permission' => 'social.moderate_violations',
            ],
            'socialprofile.admin.tokens.index' => [
                'name' => __('socialprofile::messages.admin.nav.tokens'),
                'permission' => 'social.manage_tokens',
            ],
            'socialprofile.admin.settings.edit' => [
                'name' => __('socialprofile::messages.admin.nav.settings'),
                'permission' => 'social.edit',
            ],
        ];

        return [
            'socialprofile-menu' => [
                'name' => __('socialprofile::messages.admin.nav.menu'),
                'route' => 'socialprofile.admin.dashboard',
                'icon' => 'fas fa-users-cog',
                'type' => 'dropdown',
                'items' => $items,
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
            'profile' => [
                'name' => __('socialprofile::messages.profile.menu'),
                'route' => 'socialprofile.profile.show',
                'icon' => 'fas fa-id-card',
            ],
            'leaderboards' => [
                'name' => __('socialprofile::messages.leaderboards.menu'),
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
        ];

        Permission::registerPermissions($permissions);
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

    protected function initializeUserRecords(): void
    {
        User::created(function (User $user) {
            self::ensureDefaultMetrics($user);
        });

        if (Cache::get(self::BASELINE_CACHE_KEY) === self::BASELINE_VERSION) {
            return;
        }

        User::chunk(200, function ($users) {
            foreach ($users as $user) {
                self::ensureDefaultMetrics($user);
            }
        });

        Cache::forever(self::BASELINE_CACHE_KEY, self::BASELINE_VERSION);
    }

    protected static function ensureDefaultMetrics(User $user): void
    {
        SocialScore::firstOrCreate(['user_id' => $user->id]);
        ActivityPoint::firstOrCreate(['user_id' => $user->id]);
        CoinBalance::firstOrCreate(['user_id' => $user->id]);
        GameStatistic::firstOrCreate(['user_id' => $user->id]);
        TrustLevel::firstOrCreate(['user_id' => $user->id]);
    }

    protected function registerRoleListener(): void
    {
        Role::updated(function (Role $role) {
            // TODO:
            // - Сделать конфигурацию переходов ролей наподобие:
            //   [
            //     ['from' => ['X'], 'to' => ['Y'], 'action' => 'whitelist_add'],
            //     ['from' => ['*'], 'to' => ['Z'], 'action' => 'auto_ban'],
            //     ['from' => ['Y'], 'to' => ['X'], 'action' => 'whitelist_remove'],
            //   ]
            // - Поддержать перечисление ID через запятую и wildcard '*'.
            // - На основе конфигурации выполнять автоматизацию (вайтлист, бан и т. п.).
        });
    }
}
