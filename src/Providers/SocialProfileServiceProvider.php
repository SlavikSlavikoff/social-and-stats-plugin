<?php

namespace Azuriom\Plugin\InspiratoStats\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Console\Commands\RunAutomationScheduler;
use Azuriom\Plugin\InspiratoStats\Console\Commands\RunCourtScheduler;
use Azuriom\Plugin\InspiratoStats\Database\Seeders\CourtTemplateSeeder;
use Azuriom\Plugin\InspiratoStats\Events\ActivityChanged;
use Azuriom\Plugin\InspiratoStats\Events\CoinsChanged;
use Azuriom\Plugin\InspiratoStats\Events\CourtDecisionChanged;
use Azuriom\Plugin\InspiratoStats\Events\SocialScoreChanged;
use Azuriom\Plugin\InspiratoStats\Events\SocialStatsUpdated;
use Azuriom\Plugin\InspiratoStats\Events\TrustLevelChanged;
use Azuriom\Plugin\InspiratoStats\Events\ViolationAdded;
use Azuriom\Plugin\InspiratoStats\Listeners\DispatchCourtWebhooks;
use Azuriom\Plugin\InspiratoStats\Listeners\ForwardCourtDecisionToIntegrations;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Models\OAuthIdentity;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThresholdAction;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionUserThreshold;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthManager;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\State\CacheStateStore;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\State\StateStoreInterface;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionGate;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionRuleEngine;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionService;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ThresholdActionExecutor;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Models\UserRatingValue;
use Azuriom\Plugin\InspiratoStats\Models\Violation;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationService;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationActionExecutor;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class SocialProfileServiceProvider extends BasePluginServiceProvider
{
    private const BASELINE_CACHE_KEY = 'socialprofile.seed.version';
    private const BASELINE_VERSION = 1;

    /**
     * Register any plugin services.
     */
    public function register(): void
    {
        $this->registerSeederAutoload();
        $this->mergeConfigFrom(__DIR__.'/../../config/court.php', 'socialprofile.court');
        $this->mergeConfigFrom(__DIR__.'/../../config/automation.php', 'socialprofile.automation');
        $this->mergeConfigFrom(__DIR__.'/../../config/oauth.php', 'socialprofile.oauth');

        $this->app->singleton(ThresholdActionExecutor::class, function ($app) {
            return new ThresholdActionExecutor($app->make(AutomationActionExecutor::class));
        });
        $this->app->singleton(ProgressionGate::class, fn () => new ProgressionGate(Cache::store()));
        $this->app->singleton(ProgressionService::class, function ($app) {
            return new ProgressionService(
                $app->make(ThresholdActionExecutor::class),
                $app->make(ProgressionGate::class)
            );
        });
        $this->app->singleton(ProgressionRuleEngine::class, fn ($app) => new ProgressionRuleEngine($app->make(ProgressionService::class)));

        $this->app->singleton(StateStoreInterface::class, function ($app) {
            $ttl = (int) config('socialprofile.oauth.state_ttl', 300);

            return new CacheStateStore($app->make('cache.store'), $ttl);
        });

        $this->app->singleton(OAuthManager::class, function ($app) {
            $providersConfig = config('socialprofile.oauth.providers', []);
            $providers = [];

            foreach ($providersConfig as $name => $providerConfig) {
                $driver = $providerConfig['driver'] ?? null;

                if ($driver === null) {
                    continue;
                }

                $providers[$name] = new $driver($providerConfig);
            }

            return new OAuthManager($app->make(StateStoreInterface::class), $providers);
        });

        $this->app->singleton(OAuthAccountService::class, function () {
            $ttl = (int) config('socialprofile.oauth.launcher.session_ttl', 600);

            return new OAuthAccountService($ttl);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunCourtScheduler::class,
                RunAutomationScheduler::class,
            ]);
        }
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->loadMigrations();
        $this->seedCourtTemplates();
        $this->registerPermissions();
        $this->registerRouteDescriptions();
        $this->registerAdminNavigation();
        $this->registerUserNavigation();
        $this->registerRateLimiters();
        $this->registerCourtListeners();
        $this->registerCourtScheduler();
        $this->registerAutomationScheduler();
        $this->initializeUserRecords();
        $this->registerUserRelations();
        $this->registerProfileViewExtensions();
        $this->registerAutomationListeners();
        $this->registerProgressionDocsTranslations();
        $this->ensureDefaultTimelines();
        $this->registerProgressionGate();
        $this->registerProgressionListeners();
        $this->ensureDefaultRatings();
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
            'socialprofile.court.index' => __('socialprofile::messages.court.title'),
            'socialprofile.timelines.season_history' => __('socialprofile::messages.timelines.season_history.title'),
            'socialprofile.timelines.road_map' => __('socialprofile::messages.timelines.road_map.title'),
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
            'socialprofile.admin.automation.index' => [
                'name' => __('socialprofile::messages.admin.nav.automation'),
                'permission' => 'social.automation.manage',
            ],
            'socialprofile.admin.progression.index' => [
                'name' => __('socialprofile::messages.admin.nav.progression'),
                'permission' => 'social.progression.manage',
            ],
            'socialprofile.admin.timelines.season_history' => [
                'name' => __('socialprofile::messages.admin.nav.season_history'),
                'permission' => 'social.timelines.manage',
            ],
            'socialprofile.admin.timelines.road_map' => [
                'name' => __('socialprofile::messages.admin.nav.road_map'),
                'permission' => 'social.timelines.manage',
            ],
            'socialprofile.admin.court.archive' => [
                'name' => __('socialprofile::messages.admin.nav.court_archive'),
                'permission' => 'social.court.archive',
            ],
            'socialprofile.admin.court.settings' => [
                'name' => __('socialprofile::messages.admin.nav.court_settings'),
                'permission' => 'social.court.manage_settings',
            ],
            'socialprofile.admin.court.templates.index' => [
                'name' => __('socialprofile::messages.admin.nav.court_templates'),
                'permission' => 'social.court.manage_settings',
            ],
            'socialprofile.admin.settings.edit' => [
                'name' => __('socialprofile::messages.admin.nav.settings'),
                'permission' => 'social.edit',
            ],
        ];

        return [
            'socialprofile-menu' => [
                'name' => __('socialprofile::messages.admin.nav.menu'),
                'route' => 'socialprofile.admin.*',
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
        return [];
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
            'social.automation.manage' => __('socialprofile::messages.permissions.automation_manage'),
            'social.progression.manage' => __('socialprofile::messages.permissions.progression_manage'),
            'social.court.judge' => __('socialprofile::messages.permissions.court_judge'),
            'social.court.archive' => __('socialprofile::messages.permissions.court_archive'),
            'social.court.manage_settings' => __('socialprofile::messages.permissions.court_settings'),
            'social.court.webhooks' => __('socialprofile::messages.permissions.court_webhooks'),
            'social.timelines.manage' => __('socialprofile::messages.permissions.timelines_manage'),
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

        RateLimiter::for('socialprofile-court-public', function (Request $request) {
            $perMinute = (int) config('socialprofile.court.rate_limits.public_api_per_minute', 60);

            return Limit::perMinute($perMinute)->by($request->ip());
        });

        RateLimiter::for('socialprofile-court-internal', function (Request $request) {
            $perMinute = (int) config('socialprofile.court.rate_limits.internal_api_per_minute', 120);
            $user = $request->user();
            $key = $user?->id ? 'user-'.$user->id : 'ip-'.$request->ip();

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

    protected function registerUserRelations(): void
    {
        User::resolveRelationUsing('socialScore', static fn (User $user) => $user->hasOne(SocialScore::class, 'user_id'));
        User::resolveRelationUsing('activityPoint', static fn (User $user) => $user->hasOne(ActivityPoint::class, 'user_id'));
        User::resolveRelationUsing('coinBalance', static fn (User $user) => $user->hasOne(CoinBalance::class, 'user_id'));
        User::resolveRelationUsing('gameStatistic', static fn (User $user) => $user->hasOne(GameStatistic::class, 'user_id'));
        User::resolveRelationUsing('trustLevel', static fn (User $user) => $user->hasOne(TrustLevel::class, 'user_id'));
        User::resolveRelationUsing('oauthIdentities', static fn (User $user) => $user->hasMany(OAuthIdentity::class, 'user_id'));
    }

    protected static function ensureDefaultMetrics(User $user): void
    {
        SocialScore::firstOrCreate(['user_id' => $user->id]);
        ActivityPoint::firstOrCreate(['user_id' => $user->id]);
        CoinBalance::firstOrCreate(['user_id' => $user->id]);
        GameStatistic::firstOrCreate(['user_id' => $user->id]);
        TrustLevel::firstOrCreate(['user_id' => $user->id]);
    }

    protected function ensureDefaultTimelines(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        if (! Schema::hasTable('socialprofile_timelines')) {
            return;
        }

        $defaults = [
            Timeline::TYPE_SEASON_HISTORY => [
                'title' => __('socialprofile::messages.timelines.season_history.title'),
                'subtitle' => __('socialprofile::messages.timelines.season_history.subtitle'),
            ],
            Timeline::TYPE_ROAD_MAP => [
                'title' => __('socialprofile::messages.timelines.road_map.title'),
                'subtitle' => __('socialprofile::messages.timelines.road_map.subtitle'),
            ],
        ];

        foreach ($defaults as $type => $fields) {
            Timeline::firstOrCreate(
                ['type' => $type],
                [
                    'slug' => $type,
                    'title' => $fields['title'],
                    'subtitle' => $fields['subtitle'],
                    'intro_text' => $fields['subtitle'],
                    'is_active' => true,
                    'show_period_labels' => true,
                    'meta_title' => $fields['title'],
                    'meta_description' => $fields['subtitle'],
                ]
            );
        }
    }

    protected function registerProgressionGate(): void
    {
        Gate::before(function (User $user, string $ability) {
            return app(ProgressionGate::class)->hasPermission($user, $ability);
        });
    }

    protected function registerProgressionListeners(): void
    {
        Event::listen(ActivityChanged::class, function (ActivityChanged $event): void {
            app(ProgressionRuleEngine::class)->handle(
                ProgressionRuleEngine::TRIGGER_ACTIVITY_UPDATED,
                $event->user,
                [
                    'value' => $event->activity->points,
                    'delta' => $event->context['delta'] ?? null,
                    'source' => $event->context['source'] ?? null,
                ]
            );
        });

        Event::listen(CoinsChanged::class, function (CoinsChanged $event): void {
            app(ProgressionRuleEngine::class)->handle(
                ProgressionRuleEngine::TRIGGER_COINS_UPDATED,
                $event->user,
                [
                    'value' => $event->coins->balance,
                    'delta' => $event->context['delta'] ?? null,
                    'source' => $event->context['source'] ?? null,
                ]
            );
        });

        Event::listen(SocialStatsUpdated::class, function (SocialStatsUpdated $event): void {
            app(ProgressionRuleEngine::class)->handle(
                ProgressionRuleEngine::TRIGGER_STATS_UPDATED,
                $event->user,
                [
                    'values' => $event->context['values'] ?? $event->statistics->toArray(),
                    'delta' => $event->context['delta'] ?? null,
                ]
            );
        });

        Event::listen(SocialScoreChanged::class, function (SocialScoreChanged $event): void {
            app(ProgressionRuleEngine::class)->handle(
                ProgressionRuleEngine::TRIGGER_SCORE_UPDATED,
                $event->user,
                [
                    'value' => $event->score->score,
                    'delta' => $event->context['delta'] ?? null,
                    'source' => $event->context['source'] ?? null,
                ]
            );
        });

        Event::listen(TrustLevelChanged::class, function (TrustLevelChanged $event): void {
            app(ProgressionRuleEngine::class)->handle(
                ProgressionRuleEngine::TRIGGER_TRUST_UPDATED,
                $event->user,
                [
                    'old_level' => $event->oldLevel,
                    'new_level' => $event->newLevel,
                    'actor_id' => $event->actor?->id,
                ]
            );
        });

        Event::listen(ViolationAdded::class, function (ViolationAdded $event): void {
            app(ProgressionRuleEngine::class)->handle(
                ProgressionRuleEngine::TRIGGER_VIOLATION_ADDED,
                $event->user,
                [
                    'violation' => $event->violation->toArray(),
                    'points' => $event->violation->points,
                ]
            );
        });
    }

    protected function ensureDefaultRatings(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        if (! Schema::hasTable('socialprofile_ratings')) {
            return;
        }

        $defaults = [
            [
                'slug' => 'social',
                'name' => __('socialprofile::messages.progression.defaults.social'),
                'type' => ProgressionRating::TYPE_SOCIAL,
                'scale_min' => -500,
                'scale_max' => 1200,
                'description' => 'Social trust indicator.',
                'settings' => [
                    'visual_min' => -100,
                    'visual_max' => 1000,
                ],
            ],
            [
                'slug' => 'activity',
                'name' => __('socialprofile::messages.progression.defaults.activity'),
                'type' => ProgressionRating::TYPE_ACTIVITY,
                'scale_min' => 0,
                'scale_max' => 1000,
                'description' => 'Engagement and in-game activity.',
                'settings' => [
                    'visual_min' => 0,
                    'visual_max' => 100000,
                ],
            ],
        ];

        foreach ($defaults as $default) {
            $baseSettings = [
                'color' => '#38b2ac',
                'unit' => null,
                'display_zero' => 0,
                'support_threshold' => $default['slug'] === 'social' ? 1000 : null,
                'support_meta_key' => 'support_points',
                'visual_min' => $default['settings']['visual_min'] ?? null,
                'visual_max' => $default['settings']['visual_max'] ?? null,
            ];

            $rating = ProgressionRating::firstOrCreate(['slug' => $default['slug']], [
                'name' => $default['name'],
                'description' => $default['description'],
                'type' => $default['type'],
                'scale_min' => $default['scale_min'],
                'scale_max' => $default['scale_max'],
                'is_enabled' => true,
                'settings' => $baseSettings,
            ]);

            $this->ensureRatingSettings($rating, $baseSettings);

            if ($rating->slug === 'social') {
                $this->seedSocialProgression($rating);
            }
        }
    }

    protected function ensureRatingSettings(ProgressionRating $rating, array $desired): void
    {
        $settings = $rating->settings ?? [];
        $dirty = false;

        foreach ($desired as $key => $value) {
            if (! array_key_exists($key, $settings) || $settings[$key] === null) {
                $settings[$key] = $value;
                $dirty = true;
            }
        }

        if ($dirty) {
            $rating->settings = $settings;
            $rating->save();
        }
    }

    protected function seedSocialProgression(ProgressionRating $rating): void
    {
        if ($rating->thresholds()->exists()) {
            return;
        }

        $defaultRcon = $this->defaultIntegrationId(AutomationIntegration::TYPE_RCON);
        $defaultBot = $this->defaultIntegrationId(AutomationIntegration::TYPE_SOCIAL_BOT);

        $definitions = [
            [
                'value' => 0,
                'direction' => ProgressionThreshold::DIRECTION_DESCEND,
                'label' => 'Санкция: блокировка',
                'description' => 'Если рейтинг ниже нуля — игрок блокируется до восстановления.',
                'is_punishment' => true,
                'band_min' => null,
                'band_max' => -1,
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_RCON,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultRcon,
                            'command' => 'ban {username} Social rating < 0',
                            'revert_command' => 'pardon {username}',
                        ],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_BOT,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultBot,
                            'endpoint' => '/status',
                            'method' => 'POST',
                            'payload' => ['status' => 'banned', 'reason' => 'rating<0'],
                            'revert_payload' => ['status' => 'active'],
                        ],
                    ],
                ],
            ],
            [
                'value' => 1,
                'direction' => ProgressionThreshold::DIRECTION_ASCEND,
                'label' => '0-100: ненадёжный',
                'description' => 'Минимальные права, статус «ненадёжный» и мут в соц. каналах.',
                'is_punishment' => true,
                'band_min' => 0,
                'band_max' => 100,
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
                        'auto_revert' => true,
                        'config' => ['feature' => 'progression.status.untrusted'],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_RCON,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultRcon,
                            'command' => 'lp user {username} parent add untrusted',
                            'revert_command' => 'lp user {username} parent remove untrusted',
                        ],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_BOT,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultBot,
                            'endpoint' => '/status',
                            'method' => 'POST',
                            'payload' => ['status' => 'untrusted', 'mute' => true],
                            'revert_payload' => ['status' => 'neutral', 'mute' => false],
                        ],
                    ],
                ],
            ],
            [
                'value' => 101,
                'direction' => ProgressionThreshold::DIRECTION_ASCEND,
                'label' => '101-300: участник',
                'description' => 'Снимается мут, появляется доступ к участию в судах.',
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
                        'auto_revert' => true,
                        'config' => ['feature' => 'progression.feature.court_access'],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_RCON,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultRcon,
                            'command' => 'lp user {username} permission set court.participant true',
                            'revert_command' => 'lp user {username} permission unset court.participant',
                        ],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_BOT,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultBot,
                            'endpoint' => '/status',
                            'method' => 'POST',
                            'payload' => ['mute' => false],
                            'revert_payload' => ['mute' => true],
                        ],
                    ],
                ],
            ],
            [
                'value' => 301,
                'direction' => ProgressionThreshold::DIRECTION_ASCEND,
                'label' => '301-500: расширенные привилегии',
                'description' => 'Дополнительные приваты на сервере и доступ к взаимодействию в чужих регионах.',
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
                        'auto_revert' => true,
                        'config' => ['feature' => 'progression.feature.extra_regions'],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_RCON,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultRcon,
                            'command' => 'regions grant {username} extra 2',
                            'revert_command' => 'regions revoke {username} extra 2',
                        ],
                    ],
                ],
            ],
            [
                'value' => 501,
                'direction' => ProgressionThreshold::DIRECTION_ASCEND,
                'label' => '501-800: надёжный',
                'description' => 'Выдаётся статус «Надёжный» и доступ к расширенным функциям.',
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
                        'auto_revert' => true,
                        'config' => ['feature' => 'progression.feature.reliable'],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_RCON,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultRcon,
                            'command' => 'lp user {username} parent add reliable',
                            'revert_command' => 'lp user {username} parent remove reliable',
                        ],
                    ],
                ],
            ],
            [
                'value' => 801,
                'direction' => ProgressionThreshold::DIRECTION_ASCEND,
                'label' => '801-1000: приоритет',
                'description' => 'Приоритетный вход и дополнительные привилегии.',
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
                        'auto_revert' => true,
                        'config' => ['feature' => 'progression.feature.priority_login'],
                    ],
                    [
                        'action' => ProgressionThresholdAction::ACTION_AUTOMATION_RCON,
                        'auto_revert' => true,
                        'config' => [
                            'integration_id' => $defaultRcon,
                            'command' => 'queue priority add {username}',
                            'revert_command' => 'queue priority remove {username}',
                        ],
                    ],
                ],
            ],
            [
                'value' => 1000,
                'direction' => ProgressionThreshold::DIRECTION_ASCEND,
                'label' => '1000+: легенда',
                'description' => 'Каждое очко сверх 1000 даёт баллы поддержки.',
                'actions' => [
                    [
                        'action' => ProgressionThresholdAction::ACTION_FEATURE_ENABLE,
                        'auto_revert' => true,
                        'config' => ['feature' => 'progression.feature.legend'],
                    ],
                ],
            ],
        ];

        foreach ($definitions as $position => $definition) {
            $threshold = $rating->thresholds()->create([
                'value' => $definition['value'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'direction' => $definition['direction'],
                'position' => $position,
                'is_major' => $definition['is_major'] ?? true,
                'is_punishment' => $definition['is_punishment'] ?? false,
                'band_min' => $definition['band_min'] ?? null,
                'band_max' => $definition['band_max'] ?? null,
            ]);

            foreach ($definition['actions'] as $action) {
                $threshold->actions()->create($action);
            }
        }
    }

    protected function defaultIntegrationId(string $type): ?int
    {
        if (! Schema::hasTable('socialprofile_automation_integrations')) {
            return null;
        }

        return AutomationIntegration::query()
            ->where('type', $type)
            ->where('is_default', true)
            ->value('id');
    }

    protected function registerAutomationListeners(): void
    {
        User::updated(function (User $user) {
            if (! $user->wasChanged('role_id')) {
                return;
            }

            $originalRole = $user->getOriginal('role_id');
            $newRole = $user->role_id;

            app(AutomationService::class)->handleRoleChanged($user, $originalRole, $newRole);
        });

        Event::listen(TrustLevelChanged::class, function (TrustLevelChanged $event): void {
            app(AutomationService::class)->handleTrustLevelChanged(
                $event->user,
                $event->trustLevel,
                $event->oldLevel,
                $event->newLevel,
                $event->actor
            );
        });

        Event::listen(ActivityChanged::class, function (ActivityChanged $event): void {
            app(AutomationService::class)->handleActivityChanged($event->user, $event->activity);
        });

        Event::listen(CoinsChanged::class, function (CoinsChanged $event): void {
            app(AutomationService::class)->handleCoinsChanged($event->user, $event->coins, $event->context);
        });

        Event::listen(SocialStatsUpdated::class, function (SocialStatsUpdated $event): void {
            app(AutomationService::class)->handleSocialStatsUpdated($event->user, $event->statistics);
        });

        Event::listen(ViolationAdded::class, function (ViolationAdded $event): void {
            app(AutomationService::class)->handleViolationAdded($event->user, $event->violation);
        });

        Event::listen(CourtDecisionChanged::class, function (CourtDecisionChanged $event): void {
            app(AutomationService::class)->handleCourtDecisionChanged($event->case, $event->action);
        });
    }

    protected function registerProfileViewExtensions(): void
    {
        View::composer('profile.index', function ($view) {
            $user = $view->getData()['user'] ?? null;

            if ($user === null) {
                return;
            }

            $user->loadMissing(['socialScore', 'activityPoint', 'coinBalance', 'gameStatistic', 'trustLevel', 'oauthIdentities']);

            $stats = [
                'score' => $user->socialScore ?? SocialScore::make(['score' => 0]),
                'activity' => $user->activityPoint ?? ActivityPoint::make(['points' => 0]),
                'coins' => $user->coinBalance ?? CoinBalance::make(['balance' => 0, 'hold' => 0]),
                'stats' => $user->gameStatistic ?? GameStatistic::make([
                    'played_minutes' => 0,
                    'kills' => 0,
                    'deaths' => 0,
                    'extra_metrics' => [],
                ]),
                'trust' => $user->trustLevel ?? TrustLevel::make(['level' => TrustLevel::LEVELS[0]]),
                'violations' => Violation::where('user_id', $user->id)->latest()->limit(10)->get(),
            ];

            $progressionRatings = $this->buildProfileProgressionData($user);
            $progressionMap = collect($progressionRatings)->keyBy('slug');

            $view->with('socialProfileStats', $stats);
            $view->with('progressionRatings', $progressionRatings);

            $cards = $view->getData()['cards'] ?? [];
            $cards[] = [
                'name' => __('socialprofile::messages.profile.cards.activity.title'),
                'view' => 'socialprofile::partials.profile.cards.activity',
                'data' => [
                    'activity' => $stats['activity'],
                    'stats' => $stats['stats'],
                    'rating' => $progressionMap->get('activity'),
                    'user_id' => $user->id,
                ],
            ];

            $cards[] = [
                'name' => __('socialprofile::messages.profile.cards.social.title'),
                'view' => 'socialprofile::partials.profile.cards.social',
                'data' => [
                    'score' => $stats['score'],
                    'trust' => $stats['trust'],
                    'rating' => $progressionMap->get('social'),
                    'violations' => $stats['violations'],
                    'user_id' => $user->id,
                ],
            ];

            $cards[] = [
                'name' => __('socialprofile::messages.profile.cards.wallet.title'),
                'view' => 'socialprofile::partials.profile.cards.wallet',
                'data' => [
                    'coins' => $stats['coins'],
                ],
            ];

            $oauthProviders = $this->buildOAuthProviderList($user);

            if ($oauthProviders->isNotEmpty()) {
                $cards[] = [
                    'name' => __('socialprofile::messages.profile.cards.security.title'),
                    'view' => 'socialprofile::partials.profile.cards.security',
                    'data' => [
                        'providers' => $oauthProviders->all(),
                    ],
                ];
            }

            $view->with('cards', $cards);
        });
    }

    protected function buildOAuthProviderList(User $user)
    {
        $meta = [
            'vk' => [
                'label' => 'VK ID',
                'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.79 7.65c-.12-.34-.26-.46-.57-.46h-1.9c-.44 0-.65.15-.65.48 0 .45.67.57.75 1.84 0 .17-.04.35-.09.52-.04.17-.14.31-.28.42-.15.11-.34.17-.56.17-.97 0-1.73-1.1-2.28-2.2-.47-.97-.84-1.62-.84-1.62-.18-.29-.28-.41-.59-.41h-1.92c-.38 0-.57.18-.57.46 0 .43.64.55.93 1.36.94 2.55 2.26 4.28 3.84 4.85.46.16.82.21 1.12.21.37 0 .65-.08.74-.13l-.04 1.23c0 .41.15.49.37.49h1.6c.37 0 .55-.18.62-.52.09-.47.12-1.24.12-1.24.02-.54.23-.62.48-.62h1.68c.4 0 .52-.2.52-.5 0-.2-.08-.42-.35-.71-.17-.19-.48-.46-.86-.79-.47-.41-.49-.47-.14-.95.41-.55.81-1.19 1-1.6l.09-.24c.23-.63.02-.77-.37-.77h-1.72c-.4 0-.57.28-.69.58l-2.41 6.03V7.13c0-.28-.17-.48-.47-.48h-.24z"/></svg>',
            ],
            'yandex' => [
                'label' => 'Yandex ID',
                'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.67 4h-2.22c-.3 0-.48.16-.48.45v12.5c0 .28.18.45.48.45h2.13c.32 0 .49-.17.49-.45v-5.16l2.48 4.89c.14.25.26.36.59.36h2.32c.39 0 .5-.22.33-.52l-3.06-5.27 3-6.97c.14-.35.02-.62-.37-.62h-2.08c-.3 0-.44.13-.52.37l-2.46 6.07V4.45c0-.29-.18-.45-.49-.45z"/></svg>',
            ],
        ];

        return collect(config('socialprofile.oauth.providers', []))
            ->filter(fn ($provider, $key) => filled($provider['client_id'] ?? null) && filled($provider['client_secret'] ?? null) && isset($meta[$key]))
            ->map(fn ($provider, $key) => [
                'key' => $key,
                'label' => $meta[$key]['label'],
                'icon' => $meta[$key]['icon'],
                'linked' => $user->oauthIdentities->contains(fn ($identity) => $identity->provider === $key),
                'link_url' => route('socialprofile.oauth.link', $key),
                'unlink_url' => route('socialprofile.oauth.link.destroy', $key),
            ])
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildProfileProgressionData(User $user): array
    {
        $ratings = ProgressionRating::query()
            ->whereIn('type', [ProgressionRating::TYPE_SOCIAL, ProgressionRating::TYPE_ACTIVITY])
            ->enabled()
            ->with('thresholds')
            ->orderBy('sort_order')
            ->get();

        if ($ratings->isEmpty()) {
            return [];
        }

        $values = UserRatingValue::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('rating_id');

        $states = ProgressionUserThreshold::query()
            ->where('user_id', $user->id)
            ->where('action_state', 'applied')
            ->get()
            ->groupBy('threshold_id');

        return $ratings->map(function (ProgressionRating $rating) use ($values, $states) {
            $valueRecord = $values[$rating->id] ?? null;
            $value = (int) ($valueRecord?->value ?? 0);
            $span = max(1, $rating->scale_max - $rating->scale_min);
            $percent = max(0, min(100, (($value - $rating->scale_min) / $span) * 100));
            [$visualMin, $visualMax] = $rating->visualBounds();
            $visualSpan = max(1, $visualMax - $visualMin);
            $visualPercent = max(0, min(100, (($value - $visualMin) / $visualSpan) * 100));
            $clampedValue = max($visualMin, min($visualMax, $value));

            $thresholds = $rating->thresholds->map(function ($threshold) use ($rating, $states, $span, $visualMin, $visualSpan, $value) {
                $position = max(0, min(100, (($threshold->value - $rating->scale_min) / $span) * 100));
                $visualPosition = max(0, min(100, (($threshold->value - $visualMin) / $visualSpan) * 100));
                $stateEntries = collect($states[$threshold->id] ?? []);
                $isApplied = $stateEntries->contains(fn ($entry) => ($entry->action_state ?? null) === 'applied');

                return [
                    'label' => $threshold->label,
                    'value' => $threshold->value,
                    'description' => $threshold->description,
                    'position' => $position,
                    'visual_position' => $visualPosition,
                    'reached' => $isApplied,
                    'is_punishment' => $threshold->isPunishment(),
                    'band' => $threshold->band(),
                    'band_configured' => $threshold->band_min !== null || $threshold->band_max !== null,
                    'active' => $threshold->isPunishment() ? $threshold->valueWithinBand($value) : $isApplied,
                ];
            })->values()->all();

            $supportKey = $rating->settings['support_meta_key'] ?? 'support_points';
            $supportPoints = (int) ($valueRecord?->meta[$supportKey] ?? 0);

            return [
                'name' => $rating->name,
                'slug' => $rating->slug,
                'value' => $value,
                'percent' => $percent,
                'scale' => [
                    'min' => $rating->scale_min,
                    'max' => $rating->scale_max,
                ],
                'visual' => [
                    'min' => $visualMin,
                    'max' => $visualMax,
                    'percent' => $visualPercent,
                    'value' => $clampedValue,
                    'is_below' => $value < $visualMin,
                    'is_above' => $value > $visualMax,
                ],
                'support_points' => $supportPoints,
                'meta' => $valueRecord?->meta ?? [],
                'thresholds' => $thresholds,
            ];
        })->values()->all();
    }

    protected function registerCourtListeners(): void
    {
        Event::listen(CourtDecisionChanged::class, [DispatchCourtWebhooks::class, 'handle']);
        Event::listen(CourtDecisionChanged::class, [ForwardCourtDecisionToIntegrations::class, 'handle']);
    }

    protected function registerCourtScheduler(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('socialprofile:court:tick')->everyFiveMinutes()->withoutOverlapping();
        });
    }

    protected function registerAutomationScheduler(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('socialprofile:automation:tick')->hourly()->withoutOverlapping();
        });
    }

    protected function registerSeederAutoload(): void
    {
        spl_autoload_register(function (string $class): void {
            $prefix = 'Azuriom\\Plugin\\InspiratoStats\\Database\\Seeders\\';

            if (! str_starts_with($class, $prefix) || class_exists($class, false)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative).'.php';
            $basePath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'.DIRECTORY_SEPARATOR;
            $path = $basePath.$relativePath;

            if (file_exists($path)) {
                require_once $path;
            }
        });
    }

    protected function seedCourtTemplates(): void
    {
        if (! Schema::hasTable('socialprofile_court_templates')) {
            return;
        }

        $flagKey = 'socialprofile_court_templates_seeded';
        $currentVersion = 1;
        $storedVersion = (int) setting($flagKey, 0);

        if ($storedVersion === $currentVersion && CourtTemplate::count() > 0) {
            return;
        }

        $this->app->call(CourtTemplateSeeder::class);
        setting()->set($flagKey, $currentVersion);
    }

    protected function overrideRussianTranslations(): void
    {
        Lang::addLines([
            'socialprofile::messages.court.title' => 'Суд',
            'socialprofile::messages.court.menu' => 'Суд',
            'socialprofile::messages.court.auto.title' => 'Автонаказание',
            'socialprofile::messages.court.auto.subtitle' => 'Используйте готовые шаблоны для типовых нарушений.',
            'socialprofile::messages.court.manual.title' => 'Ручное наказание',
            'socialprofile::messages.court.manual.subtitle' => 'Полный контроль и ручные корректировки.',
            'socialprofile::messages.court.judge.title' => 'Рабочее место судьи',
            'socialprofile::messages.court.judge.heading' => 'Вынесение решения',
            'socialprofile::messages.court.judge.description' => 'Выберите шаблон или заполните форму вручную, чтобы назначить наказание.',
            'socialprofile::messages.court.judge.permission_badge' => 'Доступ судьи',
            'socialprofile::messages.court.judge.recent_cases' => 'Недавние дела',
            'socialprofile::messages.court.judge.cta' => 'Рабочее место судьи',
            'socialprofile::messages.court.fields.subject' => 'Ник игрока',
            'socialprofile::messages.court.fields.template' => 'Шаблон',
            'socialprofile::messages.court.fields.template_placeholder' => 'Выберите шаблон',
            'socialprofile::messages.court.fields.comment' => 'Комментарий',
            'socialprofile::messages.court.fields.continued_case' => 'ID продолженного дела',
            'socialprofile::messages.court.fields.attachments' => 'Ссылки на доказательства',
            'socialprofile::messages.court.fields.socialrating' => 'Изменение соц. рейтинга',
            'socialprofile::messages.court.fields.activity' => 'Изменение активности',
            'socialprofile::messages.court.fields.coins' => 'Изменение монет',
            'socialprofile::messages.court.fields.money' => 'Изменение очков',
            'socialprofile::messages.court.fields.ban_duration' => 'Срок бана',
            'socialprofile::messages.court.fields.mute_duration' => 'Срок мута',
            'socialprofile::messages.court.fields.unverify' => 'Сбросить верификацию (роль новичка)',
            'socialprofile::messages.court.fields.role' => 'Назначаемая роль',
            'socialprofile::messages.court.fields.role_placeholder' => 'ID роли',
            'socialprofile::messages.court.fields.role_duration' => 'Срок роли',
            'socialprofile::messages.admin.nav.court_settings' => 'Настройки суда',
            'socialprofile::messages.court.placeholders.subject' => 'Например, PlayerNickname',
            'socialprofile::messages.court.placeholders.comment' => 'Опишите суть нарушения и доказательства',
            'socialprofile::messages.court.placeholders.continued_case' => 'ID предыдущего дела (при наличии)',
            'socialprofile::messages.court.placeholders.attachment' => 'https://evidence/:number',
            'socialprofile::messages.court.placeholders.delta' => 'Например, -50',
            'socialprofile::messages.court.placeholders.duration' => 'Примеры: 3h, 30d, 0',
            'socialprofile::messages.court.actions.issue_auto' => 'Вынести автонаказание',
            'socialprofile::messages.court.actions.issue_manual' => 'Вынести ручное наказание',
            'socialprofile::messages.court.history.title' => 'Архив дел',
            'socialprofile::messages.court.history.subtitle' => 'Последние решения и их статусы.',
            'socialprofile::messages.court.history.open' => 'Открыть архив',
            'socialprofile::messages.court.history.search_placeholder' => 'Искать по номеру дела или нику',
            'socialprofile::messages.court.history.search' => 'Поиск',
            'socialprofile::messages.court.history.empty' => 'Решений пока нет.',
            'socialprofile::messages.court.badges.status' => 'Статус',
            'socialprofile::messages.court.badges.judge' => 'Судья',
            'socialprofile::messages.court.flash.issued' => 'Решение сохранено и применено.',
            'socialprofile::messages.court.settings.templates_description' => 'Управление шаблонами находится на отдельной странице.',
            'socialprofile::messages.court.visibility.private' => 'Только судьи',
            'socialprofile::messages.court.visibility.judges' => 'Судьи и модераторы',
            'socialprofile::messages.court.visibility.public' => 'Публично',
            'socialprofile::messages.court.visibility_label' => 'Видимость по умолчанию',
            'socialprofile::messages.admin.tokens.scope_descriptions.stats_read' => 'Чтение статистики игрока.',
            'socialprofile::messages.admin.tokens.scope_descriptions.stats_write' => 'Изменение статистики игрока.',
            'socialprofile::messages.admin.tokens.scope_descriptions.activity_read' => 'Чтение очков активности.',
            'socialprofile::messages.admin.tokens.scope_descriptions.activity_write' => 'Изменение очков активности.',
            'socialprofile::messages.admin.tokens.scope_descriptions.coins_read' => 'Чтение баланса монет.',
            'socialprofile::messages.admin.tokens.scope_descriptions.coins_write' => 'Изменение баланса монет.',
            'socialprofile::messages.admin.tokens.scope_descriptions.score_read' => 'Чтение социального рейтинга.',
            'socialprofile::messages.admin.tokens.scope_descriptions.score_write' => 'Изменение социального рейтинга.',
            'socialprofile::messages.admin.tokens.scope_descriptions.trust_read' => 'Чтение уровня доверия.',
            'socialprofile::messages.admin.tokens.scope_descriptions.trust_write' => 'Изменение уровня доверия.',
            'socialprofile::messages.admin.tokens.scope_descriptions.violations_read' => 'Чтение списка нарушений.',
            'socialprofile::messages.admin.tokens.scope_descriptions.violations_write' => 'Создание нарушений через API.',
            'socialprofile::messages.admin.tokens.scope_descriptions.verify_read' => 'Чтение статуса верификации.',
            'socialprofile::messages.admin.tokens.scope_descriptions.verify_write' => 'Обновление статуса верификации.',
            'socialprofile::messages.admin.tokens.scope_descriptions.bundle_read' => 'Чтение агрегированного API.',
            'socialprofile::messages.admin.tokens.scope_descriptions.unknown' => 'Пользовательский скоуп',
            'socialprofile::messages.admin.nav.progression' => 'Уровни прогрессии',
            'socialprofile::messages.permissions.progression_manage' => 'Управление прогрессией',
            'socialprofile::messages.progression_support_points' => 'Баллы поддержки: :value',
            'socialprofile::messages.progression.ratings.support_threshold' => 'Порог бонуса',
            'socialprofile::messages.progression.ratings.support_threshold_hint' => 'Очки выше порога добавляют баллы поддержки.',
            'socialprofile::messages.progression.ratings.support_meta_key' => 'Ключ счётчика поддержки',
            'socialprofile::messages.progression.ratings.disabled' => 'Этот рейтинг отключен.',
            'socialprofile::messages.progression.actions.integration' => 'Интеграция автоматизации',
            'socialprofile::messages.progression.actions.integration_placeholder' => 'Выберите интеграцию (опционально)',
            'socialprofile::messages.progression.actions.command' => 'Команда',
            'socialprofile::messages.progression.actions.revert_command' => 'Команда отката',
            'socialprofile::messages.progression.actions.endpoint' => 'Endpoint',
            'socialprofile::messages.progression.actions.payload' => 'Payload',
            'socialprofile::messages.progression.actions.payload_hint' => 'JSON-тело (поддерживаются плейсхолдеры).',
            'socialprofile::messages.progression.actions.revert_payload' => 'Payload для отката',
            'socialprofile::messages.progression.actions.invalid_json' => 'Неверный JSON.',
            'socialprofile::messages.admin.settings.descriptions.public_rate_limit' => 'Применяется к публичным API без авторизации.',
            'socialprofile::messages.admin.settings.descriptions.token_rate_limit' => 'Применяется к API с токенами.',
            'socialprofile::messages.admin.settings.descriptions.show_coins_public' => 'Если включено, баланс монет виден публично.',
            'socialprofile::messages.admin.settings.descriptions.enable_hmac' => 'Добавляет проверку подписи для записывающих запросов.',
            'socialprofile::messages.admin.settings.descriptions.hmac_secret' => 'Используйте сильный общий секрет для клиентов.',
            'socialprofile::messages.admin.violations.redirect_notice' => 'Новые нарушения фиксируются только через суд.',
            'socialprofile::messages.admin.users.violations_redirect_notice' => 'Используйте рабочее место судьи для выдачи нарушений.',
            'socialprofile::messages.admin.timelines.placeholders.title' => 'Например: История сезонов',
            'socialprofile::messages.admin.timelines.placeholders.subtitle' => 'Короткий подзаголовок',
            'socialprofile::messages.admin.timelines.placeholders.intro' => 'Текст перед слайдером',
            'socialprofile::messages.admin.timelines.placeholders.meta_title' => 'SEO-заголовок (по желанию)',
            'socialprofile::messages.admin.timelines.placeholders.meta_description' => 'SEO-описание (по желанию)',
            'socialprofile::messages.admin.timelines.placeholders.period_title' => 'Название периода',
            'socialprofile::messages.admin.timelines.placeholders.period_description' => 'Опишите ключевые события',
            'socialprofile::messages.admin.timelines.placeholders.card_title' => 'Название карточки',
            'socialprofile::messages.admin.timelines.placeholders.card_subtitle' => 'Короткое описание',
            'socialprofile::messages.admin.timelines.placeholders.button_label' => 'Текст кнопки',
            'socialprofile::messages.admin.timelines.placeholders.button_url' => 'https://example.com/news',
        ], 'ru');
    }

    protected function registerProgressionDocsTranslations(): void
    {
        Lang::addLines([
            'socialprofile::messages.progression.tabs.configuration' => 'Configuration',
            'socialprofile::messages.progression.tabs.documentation' => 'Documentation',
            'socialprofile::messages.progression.placeholders.name' => 'e.g. Social rating',
            'socialprofile::messages.progression.placeholders.slug' => 'unique-slug',
            'socialprofile::messages.progression.placeholders.description' => 'Internal note about this rating',
            'socialprofile::messages.progression.placeholders.scale_min' => '-500',
            'socialprofile::messages.progression.placeholders.scale_max' => '1200',
            'socialprofile::messages.progression.placeholders.color' => '#38b2ac or var(--bs-primary)',
            'socialprofile::messages.progression.placeholders.unit' => 'pts',
            'socialprofile::messages.progression.placeholders.display_zero' => '0',
            'socialprofile::messages.progression.placeholders.sort_order' => '10',
            'socialprofile::messages.progression.placeholders.support_threshold' => '1000',
            'socialprofile::messages.progression.placeholders.support_meta_key' => 'support_points',
            'socialprofile::messages.progression.thresholds.placeholders.label' => 'e.g. Reliable',
            'socialprofile::messages.progression.thresholds.placeholders.value' => '500',
            'socialprofile::messages.progression.thresholds.placeholders.description' => 'What happens at this milestone',
            'socialprofile::messages.progression.thresholds.placeholders.color' => '#4ade80',
            'socialprofile::messages.progression.thresholds.placeholders.icon' => 'heroicon-o-shield-check',
            'socialprofile::messages.progression.thresholds.placeholders.metadata' => '{"tooltip_alignment":"center"}',
            'socialprofile::messages.progression.actions.placeholders.role_id' => 'Role id from /admin/users',
            'socialprofile::messages.progression.actions.placeholders.fallback_role_id' => 'Fallback role id',
            'socialprofile::messages.progression.actions.placeholders.permission' => 'social.court.participate',
            'socialprofile::messages.progression.actions.placeholders.feature' => 'progression.feature.reliable',
            'socialprofile::messages.progression.actions.placeholders.url' => 'https://bot/api/event',
            'socialprofile::messages.progression.actions.placeholders.revert_url' => 'https://bot/api/event',
            'socialprofile::messages.progression.actions.placeholders.method' => 'POST',
            'socialprofile::messages.progression.actions.placeholders.headers' => "X-Token: secret\nX-Source: socialprofile",
            'socialprofile::messages.progression.actions.placeholders.command' => 'trust set {username} reliable',
            'socialprofile::messages.progression.actions.placeholders.revert_command' => 'trust clear {username}',
            'socialprofile::messages.progression.actions.placeholders.endpoint' => '/status',
            'socialprofile::messages.progression.actions.placeholders.payload' => "{\n    \"user\": \"{username}\",\n    \"status\": \"trusted\"\n}",
            'socialprofile::messages.progression.actions.types.automation_rcon' => 'Execute RCON command',
            'socialprofile::messages.progression.actions.types.automation_bot' => 'Send request to social bot',
            'socialprofile::messages.progression.docs.overview.title' => 'How the progression system works',
            'socialprofile::messages.progression.docs.overview.body' => 'Ratings accumulate points from rules or API calls and trigger milestone actions as the value changes.',
            'socialprofile::messages.progression.docs.overview.items' => [
                'ratings' => 'Ratings describe the scale (min/max, unit, color, support bonuses).',
                'thresholds' => 'Thresholds are milestones with descriptions and actions.',
                'rules' => 'Rules listen to engine events (`activity.updated`, `score.updated`, `coins.updated`, `trust.updated`, `violation.added`).',
                'support' => 'Everything above the bonus threshold becomes support points and is never removed.',
            ],
            'socialprofile::messages.progression.docs.bands.title' => 'Default social rating bands',
            'socialprofile::messages.progression.docs.bands.columns.range' => 'Range',
            'socialprofile::messages.progression.docs.bands.columns.description' => 'Behaviour',
            'socialprofile::messages.progression.docs.bands.rows' => [
                'negative' => ['range' => '< 0 — sanction', 'description' => 'Automatic ban via RCON plus a “banned” status in bots.'],
                'low' => ['range' => '0 — 100', 'description' => '“Unreliable” badge, minimal permissions, mute in chat/bots.'],
                'court' => ['range' => '101 — 300', 'description' => 'Court participation and mute removal.'],
                'regions' => ['range' => '301 — 500', 'description' => 'Extra protected regions and interaction in allied regions.'],
                'reliable' => ['range' => '501 — 800', 'description' => 'Reliable players receive extra features/roles.'],
                'priority' => ['range' => '801 — 1000', 'description' => 'Priority login plus additional perks.'],
                'legend' => ['range' => '> 1000', 'description' => 'Every extra point grants support points.'],
            ],
            'socialprofile::messages.progression.docs.actions.title' => 'Editing workflow',
            'socialprofile::messages.progression.docs.actions.steps' => [
                'Use the “Thresholds” tab to add/remove milestones or edit descriptions.',
                'Attach actions (roles, permissions, plugin features, RCON/social bot calls).',
                'Configure automation integrations and mark default ones for seeding.',
                'Use the `/progression/events` API to sync external systems.',
            ],
            'socialprofile::messages.progression.docs.automation.title' => 'Automations',
            'socialprofile::messages.progression.docs.automation.items' => [
                'RCON integrations reuse the saved credentials and can run any server command.',
                'Social-bot integrations post JSON payloads to the configured endpoint.',
                'Threshold webhooks fire on ascent and descent—provide both apply and revert URLs.',
            ],
            'socialprofile::messages.progression.docs.automation.note' => 'Placeholders such as {username} or {rating_value} are replaced automatically.',
            'socialprofile::messages.progression.docs.placeholders.title' => 'Command placeholders',
            'socialprofile::messages.progression.docs.placeholders.body' => 'Available tokens:',
            'socialprofile::messages.progression.docs.placeholders.tokens' => [
                '{username}' => 'Player nickname',
                '{user_id}' => 'Azuriom user id',
                '{rating_slug}' => 'Rating slug',
                '{rating_value}' => 'New rating value',
                '{old_role_id}' => 'Previous role id',
                '{new_role_id}' => 'New role id',
            ],
            'socialprofile::messages.progression.docs.tips.title' => 'Tips',
            'socialprofile::messages.progression.docs.tips.items' => [
                'Keep at least one RCON/bot integration marked as default so the seeded setup works immediately.',
                'Use metadata JSON to store UI hints or additional options.',
                'Validate payload JSON before saving to avoid syntax errors.',
                'Grant the `social.progression.manage` permission to trusted staff.',
            ],
        ], 'en');

    }
}
