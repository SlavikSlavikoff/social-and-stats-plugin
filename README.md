# Social Profile (Inspirato)

Плагин Social Profile расширяет пользовательский профиль Azuriom социальными метриками Inspirato, добавляет административные панели и REST API v1 с тонкими правами доступа. Плагин совместим с Azuriom API `1.1.0` (см. `plugin.json`) и регистрируется под идентификатором `socialprofile`.

## Обзор возможностей

- Хранение социальных очков, активности, баланса монет, игровых статистик, уровня доверия, верификации и истории нарушений для каждого пользователя.
- Публичные страницы `/account/social` и `/leaderboards/social` с кастомными видами и стилями из `assets/css/style.css`.
- Админ-модули для дашборда, управления пользователями, нарушениями, API‑токенами и настройками.
- API v1 (`/api/social/v1/...`) с bearer-токенами, скоупами, белым списком IP, rate limit и (опционально) HMAC‑подписью.
- События (`CoinsChanged`, `TrustLevelChanged`, `VerificationChanged` и др.) для интеграции с внешними сервисами и логирование через `action()` при изменении данных.

## Неймспейсы и автозагрузка

Все классы плагина объявлены в пространстве имен `Azuriom\Plugin\InspiratoStats\...` и теперь сопоставлены в `composer.json` через PSR‑4‑префикс `Azuriom\\Plugin\\InspiratoStats\\`. После клонирования или обновления репозитория выполните:

```bash
composer dump-autoload
```

Это пересоберёт карту классов и гарантирует, что новые неймспейсы корректно подхватятся Azuriom.

## Требования и установка

1. Скопируйте папку плагина в `plugins/socialprofile` вашего Azuriom-проекта.
2. Убедитесь, что PHP-расширения и версия Azuriom соответствуют минимальным требованиям ядра (API 1.1.0+).
3. Выполните миграции плагина из корня проекта:

   ```bash
   php artisan azuriom:plugin:migrate socialprofile
   # или явным путем
   php artisan migrate --path=plugins/socialprofile/database/migrations
   ```

4. Активируйте плагин в админ-панели Azuriom и выполните `php artisan cache:clear`, чтобы подхватить новые разрешения и маршруты.
5. Настройте лимиты, отображение монет и HMAC в разделе **Admin → Social Profile → Settings**.

## Миграции и структура данных

Миграция `2024_01_01_000000_create_socialprofile_tables.php` создает набор таблиц с префиксом `socialprofile_` (все внешние ключи каскадно удаляются):

| Таблица | Содержимое | Особенности |
|---------|------------|-------------|
| `socialprofile_social_scores` | `user_id`, `score` | Уникальная запись на пользователя. |
| `socialprofile_activity_points` | `user_id`, `points` | Отслеживает активность. |
| `socialprofile_coin_balances` | `user_id`, `balance`, `hold` | Денежные значения `decimal(18,2)`, блокировки через `lockForUpdate()`. |
| `socialprofile_game_statistics` | `user_id`, `played_minutes`, `kills`, `deaths`, `extra_metrics` (JSON) | Гибкие метрики игры. |
| `socialprofile_trust_levels` | `user_id`, `level`, `granted_by`, `note` | Поддерживает `softDeletes`, enum уровней `newbie`→`staff`. |
| `socialprofile_violations` | `user_id`, `type`, `reason`, `points`, `issued_by`, `evidence_url` | `softDeletes`, индекс по `user_id/created_at`. |
| `socialprofile_api_tokens` | `name`, `token_hash(sha256)`, `scopes` (JSON), `allowed_ips`, `rate_limit`, `created_by` | Используется для защиты API. |

## Настройки панели управления

| Поле (UI) | Ключ в `setting()` | Значение по умолчанию | Описание |
|-----------|--------------------|------------------------|----------|
| Public rate limit (req/min) | `socialprofile_public_rate_limit` | `60` | Используется лимитером `socialprofile-public` для всех GET‑запросов. |
| Token rate limit (req/min) | `socialprofile_token_rate_limit` | `120` | Базовый лимит `socialprofile-token`, если у токена не задано индивидуальное значение. |
| Show coins publicly | `socialprofile_show_coins_public` | `true` | Разрешает отдавать баланс монет в публичных ответах для верифицированных аккаунтов. |
| Require HMAC for writes | `socialprofile_enable_hmac` | `false` | Если включено, все запись-запросы с токеном должны иметь подпись. |
| HMAC secret | `socialprofile_hmac_secret` | `null` | Секрет для подписи `X-Social-Signature`; храните отдельно от токенов. |

## Разрешения

| Разрешение | Назначение |
|------------|-----------|
| `social.view` | Просмотр админ-дашборда Social Profile. |
| `social.edit` | Редактирование метрик, настроек и просмотр пользователей. |
| `social.grant_trust` | Изменение уровней доверия. |
| `social.manage_tokens` | Управление API‑токенами. |
| `social.moderate_violations` | Создание/удаление нарушений. |

## Маршруты

### Публичные (web)

| Route name | Метод | URI | Middleware | Описание |
|------------|-------|-----|------------|----------|
| `socialprofile.profile.show` | GET | `/account/social` | `web`, `auth` | Персональный профиль текущего пользователя. |
| `socialprofile.leaderboards.index` | GET | `/leaderboards/social` | `web` | Таблицы лидеров по активности и social score. |

### Админ‑панель (`/admin/socialprofile`, middleware: `web`, `admin-access`)

| Route name | Метод | URI | Middleware `can:` | Назначение |
|------------|-------|-----|-------------------|------------|
| `socialprofile.admin.dashboard` | GET | `/` | `social.view` | Обзор ключевых метрик. |
| `socialprofile.admin.users.index` | GET | `/users` | `social.edit` | Поиск и список пользователей. |
| `socialprofile.admin.users.show` | GET | `/users/{user}` | `social.edit` | Карточка пользователя и метрики. |
| `socialprofile.admin.users.metrics.update` | POST | `/users/{user}/metrics` | `social.edit` | Обновление очков, активности, монет и статистики. |
| `socialprofile.admin.users.trust.update` | POST | `/users/{user}/trust` | `social.grant_trust` | Смена уровня доверия. |
| `socialprofile.admin.users.violations.store` | POST | `/users/{user}/violations` | `social.moderate_violations` | Добавление нарушения из профиля. |
| `socialprofile.admin.violations.index` | GET | `/violations` | `social.moderate_violations` | Список всех нарушений. |
| `socialprofile.admin.violations.store` | POST | `/violations` | `social.moderate_violations` | Создание нарушения по ID пользователя. |
| `socialprofile.admin.violations.destroy` | DELETE | `/violations/{violation}` | `social.moderate_violations` | Удаление записи о нарушении. |
| `socialprofile.admin.tokens.index` | GET | `/tokens` | `social.manage_tokens` | Просмотр токенов и выдача новых. |
| `socialprofile.admin.tokens.store` | POST | `/tokens` | `social.manage_tokens` | Создание токена и отображение исходного значения. |
| `socialprofile.admin.tokens.update` | PUT | `/tokens/{token}` | `social.manage_tokens` | Правка названия, IP и скоупов. |
| `socialprofile.admin.tokens.rotate` | POST | `/tokens/{token}/rotate` | `social.manage_tokens` | Вращение секретного значения токена. |
| `socialprofile.admin.tokens.destroy` | DELETE | `/tokens/{token}` | `social.manage_tokens` | Удаление токена. |
| `socialprofile.admin.settings.edit` | GET | `/settings` | `social.edit` | Форма глобальных настроек. |
| `socialprofile.admin.settings.update` | POST | `/settings` | `social.edit` | Сохранение настроек. |

### API v1 (`/api/social/v1`, middleware `api`)

| URI | Метод | Scope | Throttle | Описание |
|-----|-------|-------|----------|----------|
| `/user/{nickname}/stats` | GET | `stats:read` | `socialprofile-public` | Возвращает `played_minutes` и (при полном доступе) kills/deaths/extra_metrics. |
| `/user/{nickname}/stats` | PUT | `stats:write` | `socialprofile-token` | Обновляет игровые метрики и триггерит `SocialStatsUpdated`. |
| `/user/{nickname}/activity` | GET | `activity:read` | `socialprofile-public` | Отдает очки активности. |
| `/user/{nickname}/activity` | PUT | `activity:write` | `socialprofile-token` | Изменяет активность и шлет `ActivityChanged`. |
| `/user/{nickname}/coins` | GET | `coins:read` | `socialprofile-public` | Баланс виден, только если `show_coins_public` и пользователь verified; полный доступ видит `hold`. |
| `/user/{nickname}/coins` | PUT | `coins:write` | `socialprofile-token` | Обновляет баланс с блокировкой строки и событием `CoinsChanged`. |
| `/user/{nickname}/social-score` | GET | `score:read` | `socialprofile-public` | Возвращает текущее social score. |
| `/user/{nickname}/social-score` | PUT | `score:write` | `socialprofile-token` | Устанавливает новое значение. |
| `/user/{nickname}/trust-level` | GET | `trust:read` | `socialprofile-public` | Уровень доверия и локализованный label; полный доступ видит note/granted_by. |
| `/user/{nickname}/trust-level` | PUT | `trust:write` | `socialprofile-token` | Изменяет уровень (требует разрешения `social.grant_trust`). |
| `/user/{nickname}/violations` | GET | `violations:read` | `socialprofile-public` | Требует полного доступа; возвращает коллекцию нарушений. |
| `/user/{nickname}/violations` | POST | `violations:write` | `socialprofile-token` | Создает нарушение (разрешение `social.moderate_violations`). |
| `/user/{nickname}/bundle` | GET | `bundle:read` | `socialprofile-public` | Консолидированный payload для профиля (монеты и расширенные поля только при доступе). |

> Ник (`{nickname}`) ищется по `config('auth.providers.users.field', 'name')`, поэтому можно переключить поиск на `uuid`, установив соответствующую опцию в ядре.

Все write‑методы требуют заголовка `Authorization: Bearer <token>`, проверки IP (если заданы) и, при включенном флажке **Require HMAC**, заголовка `X-Social-Signature`.

## API‑токены и scopes

### Создание токена

1. Перейдите в **Admin → Social Profile → Tokens**.
2. Задайте имя, выберите один или несколько скоупов. Доступны как точные значения (`coins:write`), так и шаблоны (`coins:*`, `*`).
3. (Опционально) Укажите список разрешенных IP через запятую или перевод строки. Поле может содержать IPv4/IPv6.
4. (Опционально) Задайте лимит запросов в минуту; число попадет в JSON `rate_limit.per_minute`.
5. После сохранения исходный токен отображается один раз и сохраняется в виде `sha256`‑хэша в базе.

### Доступные scopes

| Scope | Тип | Назначение |
|-------|-----|------------|
| `stats:read` / `stats:write` | GET / PUT `/user/{nickname}/stats` | Игровая статистика. |
| `activity:read` / `activity:write` | GET / PUT `/user/{nickname}/activity` | Очки активности. |
| `coins:read` / `coins:write` | GET / PUT `/user/{nickname}/coins` | Баланс монет и холда. |
| `score:read` / `score:write` | GET / PUT `/user/{nickname}/social-score` | Социальный рейтинг. |
| `trust:read` / `trust:write` | GET / PUT `/user/{nickname}/trust-level` | Уровни доверия. |
| `violations:read` / `violations:write` | GET / POST `/user/{nickname}/violations` | История и создание нарушений. |
| `bundle:read` | GET `/user/{nickname}/bundle` | Компактный профиль для сторонних сервисов. |

Метод `ApiToken::allowsScope()` понимает `*` для полного доступа или форму `<домен>:*` (например, `coins:*`). Используйте их для упрощения конфигурации сервисов.

## Ограничения скорости

| Лимитер | Управляется настройкой | Ключ квотирования | Значение по умолчанию | Где используется |
|---------|-----------------------|-------------------|-----------------------|------------------|
| `throttle:socialprofile-public` | `socialprofile_public_rate_limit` | IP адрес | `60 req/min` | Все GET API‑маршруты. |
| `throttle:socialprofile-token` | `rate_limit.per_minute` токена → иначе `socialprofile_token_rate_limit` | `token-{id}` при валидном токене, иначе `ip-{ip}` | `120 req/min` | Все PUT/POST API‑маршруты. |

Изменяйте значения через UI настроек. Персональный лимит токена имеет приоритет над глобальным значением.

## Пример HMAC‑подписи

При включенном флаге **Require HMAC** сервер проверяет заголовок `X-Social-Signature`, который вычисляется как `hash_hmac('sha256', $rawBody, $secret)` в шестнадцатеричном виде. Пример вызова обновления баланса:

```bash
SECRET="super-secret-string"
BODY='{"balance":150.25,"hold":0}'
SIGNATURE=$(printf %s "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

curl -X PUT https://example.com/api/social/v1/user/Aurora/coins \
  -H "Authorization: Bearer eyJ..." \
  -H "Content-Type: application/json" \
  -H "X-Social-Signature: ${SIGNATURE}" \
  -d "$BODY"
```

Если секрет не задан или подпись не совпадает, `ApiController` завершает запрос статусом `401`. Подпись требуется только для write‑методов и только когда запрос аутентифицируется по токену.

## События и интеграции

- `SocialStatsUpdated(User $user, GameStatistic $stats)` — обновлена игровая статистика.
- `ActivityChanged(User $user, ActivityPoint $activity)` — изменены очки активности.
- `CoinsChanged(User $user, CoinBalance $coins)` — изменен баланс монет.
- `TrustLevelChanged(User $user, TrustLevel $trust, ?User $actor)` — обновлен уровень доверия.
- `ViolationAdded(User $user, Violation $violation)` — создано нарушение.

Подписывайтесь на события для публикации изменений в очереди, вебхуки и т.п.

## Поведение ответов API

- Публичные ответы `GameStatisticResource`, `TrustLevelResource`, `CoinResource`, `VerificationResource` и `BundleResource` автоматически скрывают чувствительные поля, если `hasFullAccess=false`.
- Баланс монет в ответах GET/Bundle доступен только при полном доступе или когда включен `Show coins publicly` и пользователь успешно верифицирован.
- Эндпоинт `/user/{nickname}/violations` в режиме GET всегда требует полного доступа (токен/пермишен/владелец).
- `resolveUser()` ищет пользователя по полю, заданному в `config('auth.providers.users.field')`, поэтому можно использовать ник, UUID или email в зависимости от конфигурации Azuriom.

Эти особенности позволяют безопасно кешировать публичные запросы и при этом отдавать максимум данных доверенным интеграциям.
