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
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Models\Violation;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationService;
use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->registerProfileViewExtensions();
        $this->registerAutomationListeners();
        $this->overrideRussianTranslations();
        $this->ensureDefaultTimelines();
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

            $stats = [
                'score' => SocialScore::firstOrCreate(['user_id' => $user->id]),
                'activity' => ActivityPoint::firstOrCreate(['user_id' => $user->id]),
                'coins' => CoinBalance::firstOrCreate(['user_id' => $user->id]),
                'stats' => GameStatistic::firstOrCreate(['user_id' => $user->id]),
                'trust' => TrustLevel::firstOrCreate(['user_id' => $user->id]),
                'violations' => Violation::where('user_id', $user->id)->latest()->limit(10)->get(),
            ];

            $view->with('socialProfileStats', $stats);

            $cards = $view->getData()['cards'] ?? [];
            $cards[] = [
                'name' => __('socialprofile::messages.profile.statistics'),
                'view' => 'socialprofile::partials.profile.cards',
                'data' => $stats,
            ];

            $cards[] = [
                'name' => __('socialprofile::messages.profile.recent_violations'),
                'view' => 'socialprofile::partials.profile.violations',
                'data' => ['violations' => $stats['violations']],
            ];

            $view->with('cards', $cards);
        });
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
}
