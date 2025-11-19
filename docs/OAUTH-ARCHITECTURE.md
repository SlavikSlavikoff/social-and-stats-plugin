# OAuth модуль Inspirato Stats

Этот документ описывает устройство модуля OAuth в плагине, способы расширения новыми провайдерами и правила интеграции с лаунчером (на примере Gravit Launcher 5.7.0).

---

## 1. Структура и основные файлы

| Компонент | Расположение | Назначение |
|-----------|--------------|------------|
| Конфигурация провайдеров | `config/oauth.php` | Базовые настройки (TTL состояния, параметры лаунчера) + список провайдеров (ключи, эндпоинты, scopes). |
| Регистрация зависимостей | `src/Providers/SocialProfileServiceProvider.php` | Подключение конфига, биндинг `StateStoreInterface`, `OAuthManager`, `OAuthAccountService`, добавление карточки профиля. |
| DTO и ядро | `src/Support/OAuth/Dto/*`, `src/Support/OAuth/OAuthManager.php`, `src/Support/OAuth/OAuthAccountService.php` | Классы `AccessToken`, `OAuthUser`, `StoredState`, менеджер и сервис учётных записей, работа со state и launcher сессиями. |
| Провайдеры | `src/Support/OAuth/Providers/*.php` | Конкретные драйверы (Яндекс и VK). Реализуют интерфейс `OAuthProviderInterface`. |
| Состояние | `src/Support/OAuth/State/CacheStateStore.php` | Сохраняет `state` + контекст (flow, user_id, launcher session) в кеш. TTL задаётся в конфиге. |
| Модели и миграции | `src/Models/OAuthIdentity.php`, `src/Models/OAuthLoginSession.php`, `database/migrations/2025_11_20_041000_create_oauth_tables.php` | Таблицы для привязок и сессий лаунчера. |
| HTTP-слой | `routes/web.php`, `routes/api.php`, `src/Http/Controllers/Web/*`, `src/Http/Controllers/Api/OAuthSessionController.php` | Роуты/контроллеры для привязки, логина, лаунчера. |
| UI | `resources/views/partials/oauth/login-buttons.blade.php`, `resources/views/partials/profile/cards/security.blade.php`, `resources/views/oauth/launcher-result.blade.php` | Блок «Войти с помощью», карточка «Безопасность» в профиле, страница результата в браузере. |
| Документация лаунчера | `docs/LAUNCHER-OAUTH.md` | Подробный гайд по интеграции с Gravit Launcher. |

---

## 2. Как всё работает

1. **Получение ссылок на авторизацию**: контроллеры (для профиля, сайта или лаунчера) вызывают `OAuthManager::getAuthorizationUrl($provider, $flowType, $context)`. Менеджер сохраняет `state` в `CacheStateStore` (ключ `socialprofile:oauth:state:{state}`) и возвращает URL интерпретатора провайдера.
2. **Callback**: `OAuthCallbackController` получает `code`/`state`, вызывает `OAuthManager::handleCallback()`. Менеджер:
   - находит `StoredState` в кеше;
   - передаёт `code` адаптеру провайдера (`getToken`, `getUserInfo`);
   - возвращает `OAuthCallbackResult` с данными пользователя и контекстом (тип потока, `user_id`, `login_session_id` и т.п.).
3. **Привязка**: если `flow=link`, `OAuthAccountService::linkProviderToUser()` проверяет, что аккаунт не закреплён за другим пользователем, сохраняет токены и профиль в таблицу `socialprofile_oauth_identities`.
4. **Вход на сайте**: при `flow=web_login` сервис ищет `OAuthIdentity` и логинит пользователя через стандартный guard. Новые пользователи не создаются.
5. **Лаунчер**:
   - `POST /api/social/v1/oauth/sessions` создаёт `OAuthLoginSession` (поле `result_payload` позже заполняется сервисом).
   - `OAuthAccountService::handleLauncherCallback()` по контексту `login_session_id` находит сессию и записывает результат (`success`, `failed`, `expired`).
   - Статус доступен по `GET /api/social/v1/oauth/sessions/{id}`.

---

## 3. Добавление/редактирование провайдера

Процесс состоит из четырёх блоков: конфиг, драйвер, UI/локализации и тесты. Ниже — детальный чек-лист.

### 3.1. Конфигурация

1. **Добавьте запись в `config/oauth.php`**:
   ```php
   'github' => [
       'driver' => \Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers\GithubProvider::class,
       'client_id' => env('SOCIALPROFILE_GITHUB_CLIENT_ID'),
       'client_secret' => env('SOCIALPROFILE_GITHUB_CLIENT_SECRET'),
       'redirect_uri' => env('SOCIALPROFILE_GITHUB_REDIRECT_URI', env('APP_URL').'/oauth/callback/github'),
       'authorization_endpoint' => 'https://github.com/login/oauth/authorize',
       'token_endpoint' => 'https://github.com/login/oauth/access_token',
       'userinfo_endpoint' => 'https://api.github.com/user',
       'scopes' => ['read:user', 'user:email'],
       'extra' => [
           'accept_header' => 'application/vnd.github.v3+json',
       ],
   ],
   ```
2. **.env**: добавьте переменные `SOCIALPROFILE_GITHUB_CLIENT_ID/SECRET/REDIRECT_URI`.
3. **Параметры**: если провайдер поддерживает PKCE, device flow и т.д., закладывайте их в конфиг (например, `supports_pkce => true`) — это упростит доработки контроллеров.
4. **Перезагрузите кеш** (`php artisan config:clear`) перед тестами.

### 3.2. Драйвер (`src/Support/OAuth/Providers`)

1. **Шаблон класса**:
   ```php
   final class GithubProvider implements OAuthProviderInterface
   {
       public function __construct(private readonly array $config) {}
       public function getName(): string { return 'github'; }
       public function getAuthorizationUrl(string $state, array $context = []): string { /* ... */ }
       public function getToken(string $code): AccessToken { /* ... */ }
       public function getUserInfo(AccessToken $token): OAuthUser { /* ... */ }
   }
   ```
2. **Authorization URL**:
   - формируйте query через `http_build_query`;
   - обязательно включайте `state`;
   - поддерживайте доп. параметры (`prompt`, `nonce`, `code_challenge`), если они передаются в `$context`.
3. **Получение токена**:
   - используйте `Http::asForm()` или `Http::withBasicAuth()` — в зависимости от требований провайдера;
   - обрабатывайте ошибки (`$response->failed()`), выбрасывая `OAuthException` с текстом;
   - рассчитывайте `expires_at` через `now()->addSeconds(...)`.
4. **Профиль пользователя**:
   - строится из `AccessToken` (и `id_token`, если это OIDC);
   - если `id_token` — JWT, парсите payload (`json_decode(base64url_decode(...))`);
   - маппьте обязательные поля: `providerUserId`, `email?`, `name?`, `avatarUrl?`;
   - сохраняйте оригинальный ответ в `$user->raw`.
5. **Рекомендации**:
   - предусмотреть retry/backoff для нестабильных провайдеров;
   - если API требует версию (`v=5.131` у VK) — вынесите в конфиг;
   - поддерживайте `refresh_token`, если провайдер его отдаёт (можно использовать позже).

### 3.3. UI и локализации

1. **Страница логина**:
   - в `resources/views/partials/oauth/login-buttons.blade.php` добавьте описание нового провайдера в `$meta` (название, подпись, SVG-иконка, CSS-класс);
   - добавьте фирменный цвет/стили по гайдам провайдера (иначе могут заблокировать кнопку).
2. **Карточка «Безопасность»**:
   - в `SocialProfileServiceProvider::buildOAuthProviderList()` добавьте метаданные (иконка, label) для нового ключа;
   - при необходимости измените `security.blade.php`, если требуется особый UX (например, отображать дополнительные поля профиля).
3. **Переводы**:
   - `resources/lang/en/messages.php` и `resources/lang/ru/messages.php` — строки для кнопок/описания (`oauth.provider_description`, `profile.cards.security`).

### 3.4. Тесты

1. **Unit-тест драйвера** (`tests/Unit/OAuth/GithubProviderTest.php`):
   - `Http::fake()` ответы токен/профиль;
   - проверяйте генерацию URL, корректность `AccessToken`, маппинг `OAuthUser`.
2. **Feature-тесты UI**:
   - обновите `OAuthLoginButtonsTest`/`ProfileSecurityCardTest`, чтобы они учитывали новый провайдер (или создайте отдельный тест, если требуется кастомное поведение).
3. **Интеграционные тесты** (по желанию):
   - написать e2e тест, эмулирующий полный проход `link`/`web_login` для нового провайдера (полезно при сложных контекстах).
4. **Linters/CI**:
   - убедитесь, что `phpunit` проходит (61 тест и более);
   - при наличии статического анализа (PHPStan/Psalm) добавьте провайдер в соответствующие конфиги.

### 3.5. Отладка

- Используйте `ray()`/`logger()` в драйвере на время разработки (не забудьте убрать).
- Просматривайте логи в `storage/logs/laravel.log` — `OAuthException` содержит текст ответа провайдера.
- Если нужно посмотреть `state`/контекст — используйте `cache('socialprofile:oauth:state:{state}')`.

---

## 4. Изменение существующих провайдеров

- **Scopes/redirect URI**: редактируйте параметры в `config/oauth.php`. После изменения перезапустите кеш конфига (или `php artisan config:clear`).
- **UI-тексты/цвета**: обновляйте частичные шаблоны:
  - `resources/views/partials/oauth/login-buttons.blade.php` для страницы логина.
  - `resources/views/partials/profile/cards/security.blade.php` и `buildOAuthProviderList()` — для карточки профиля.
- **Логика**: при необходимости обновите провайдерный класс (например, если API VK ID добавил новые поля).
- **TTL/предельное время**:
  - `state_ttl` — время жизни `state`.
  - `launcher.session_ttl` — время жизни сессии лаунчера.

---

## 5. Интеграция в лаунчере (резюме)

Подробный гайд см. в `docs/LAUNCHER-OAUTH.md`. Ниже — ключевые моменты.

### 5.1. API эндпоинты

| Метод | URL | Назначение |
|-------|-----|------------|
| `POST` | `/api/social/v1/oauth/sessions` | Создать сессию (`provider`: `vk` или `yandex`). |
| `GET` | `/api/social/v1/oauth/sessions/{id}` | Проверить статус (`pending/success/failed/expired`). |

### 5.2. Пример запросов (Java, Gravit 5.7.0)

```java
HttpRequest create = HttpRequest.newBuilder()
    .uri(URI.create(API + "/oauth/sessions"))
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString("{\"provider\":\"vk\"}"))
    .build();

HttpResponse<String> createResponse = http.send(create, BodyHandlers.ofString());
JsonNode data = mapper.readTree(createResponse.body());
String sessionId = data.get("session_id").asText();
String authUrl = data.get("authorization_url").asText();

Desktop.getDesktop().browse(URI.create(authUrl));

while (true) {
    Thread.sleep(2000);

    HttpRequest status = HttpRequest.newBuilder()
        .uri(URI.create(API + "/oauth/sessions/" + sessionId))
        .build();

    JsonNode state = mapper.readTree(http.send(status, BodyHandlers.ofString()).body());
    String s = state.get("status").asText();

    if ("pending".equals(s)) {
        continue;
    }

    if ("success".equals(s)) {
        String token = state.get("payload").get("session_token").asText();
        authenticateWithToken(token);
        break;
    }

    throw new IllegalStateException("OAuth failed: " + state.get("error_code"));
}
```

### 5.3. Принципы

- Один `session_id` — одна попытка. При истечении времени создавайте новую.
- Показывайте пользователю статус (ожидание, успех, ошибка).
- Обрабатывайте `identity_not_linked` (предложите зайти на сайт и привязать аккаунт).
- В браузере пользователь видит страницу `resources/views/oauth/launcher-result.blade.php`.

---

## 6. Полезные команды

- Применение миграций: `php artisan migrate --path=plugins/inspiratostats/database/migrations`.
- Очистка кеша конфига: `php artisan config:clear`.
- Прогон тестов плагина: `php vendor/phpunit/phpunit/phpunit --configuration plugins/inspiratostats/phpunit.xml`.

---

## 7. Чек-лист

1. Активирован ли провайдер (заполнены `client_id`/`client_secret`)?
2. Добавлены ли UI-иконки/переводы?
3. Работают ли ручки `/oauth/link`, `/oauth/login`, `/api/oauth/sessions`?
4. Заполнены ли redirect URI на стороне провайдера?
5. Покрыты ли изменения тестами?
