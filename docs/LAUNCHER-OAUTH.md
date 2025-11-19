# OAuth авторизация в лаунчере (Gravit Launcher 5.7.0)

Этот документ описывает, как интегрировать быстрый вход через VK ID и Yandex ID в актуальную версию Gravit Launcher (5.7.0), используя OAuth API плагина Inspirato Stats.

## Обзор флоу

1. Лаунчер начинает OAuth-сессию через REST API плагина.
2. Плагин возвращает `session_id` и `authorization_url`.
3. Лаунчер открывает URL во внешнем браузере.
4. Пользователь проходит OAuth на сайте (VK ID или Yandex ID).
5. Плагин завершает сессию (успех/ошибка) и, при успехе, прикрепляет профиль пользователя и выданный сессионный токен.
6. Лаунчер опрашивает статус сессии и после успеха использует полученный токен для аутентификации в AuthAPI.

Важные особенности:

- Пользователь должен заранее привязать VK ID/Yandex ID в профиле на сайте (карточка «Безопасность»).
- Лаунчер не получает access token провайдеров, только результат от сайта.
- Каждая сессия действует ограниченное время (`socialprofile.oauth.launcher.session_ttl`, по умолчанию 10 минут).

## Требования

- Gravit Launcher 5.7.0 с возможностью менять экран авторизации.
- Доступ к HTTPS-версии вашего сайта (пример: `https://example.com`).
- Плагин Inspirato Stats обновлён до версии с OAuth-модулем.
- В `.env`/конфиге настроены ключи VK ID и Yandex ID (см. `config/oauth.php`).

## REST API

### Создание OAuth-сессии

```
POST https://example.com/api/social/v1/oauth/sessions
Content-Type: application/json

{
  "provider": "vk" // или "yandex"
}
```

Ответ `201 Created`:

```json
{
  "session_id": "8d418f25-7bd2-4f4a-bf11-16ce0864cc1d",
  "authorization_url": "https://example.com/oauth/login/vk?state=...",
  "provider": "vk",
  "status": "pending",
  "expires_at": "2025-11-20T12:34:56+00:00"
}
```

### Получение статуса

```
GET https://example.com/api/social/v1/oauth/sessions/{session_id}
```

Ответ:

```json
{
  "session_id": "8d41...",
  "provider": "vk",
  "status": "success",
  "error_code": null,
  "expires_at": "2025-11-20T12:34:56+00:00",
  "user": {
    "id": 42,
    "name": "Player",
    "email": "player@example.com"
  },
  "payload": {
    "session_token": "xJ0F2X...",
    "user": { "...": "..." }
  }
}
```

`status` может быть:

- `pending` — ожидание завершения.
- `success` — можно продолжать.
- `failed` — пользователь не привязан или OAuth отменён.
- `expired` — сессия истекла (необходимо создать новую).

`error_code` принимает значения:

- `identity_not_linked` — нужный провайдер ещё не привязан.
- `provider_error` — пользователь отменил авторизацию или произошла ошибка провайдера.

`payload.session_token` — одноразовый токен, который лаунчер может обменять на сессию AuthAPI (см. ниже).

## Изменения в Gravit Launcher

### UI

1. На экране входа добавьте блок «Войти с помощью».
2. Добавьте две кнопки:
   - VK ID (фон #0077FF, белый логотип VK ID согласно гайдам).
   - Yandex ID (чёрный фон/белый текст, логотип Яндекс ID).
3. При нажатии показывайте загрузку и инициируйте запрос `POST /oauth/sessions`.
4. После получения `authorization_url` открывайте внешнее окно:
   ```java
   Desktop.getDesktop().browse(URI.create(authorizationUrl));
   ```
5. Покажите таймер/индикатор ожидания (например, прогресс-бар) и начинайте опрос статуса.

### Логика (псевдокод Java)

```java
HttpClient http = HttpClient.newBuilder()
    .followRedirects(HttpClient.Redirect.NORMAL)
    .build();

String sessionId;
String authUrl;

HttpRequest createRequest = HttpRequest.newBuilder()
    .uri(URI.create(API_URL + "/oauth/sessions"))
    .header("Content-Type", "application/json")
    .POST(BodyPublishers.ofString("{\"provider\":\"vk\"}"))
    .build();

HttpResponse<String> response = http.send(createRequest, BodyHandlers.ofString());
JsonNode data = OBJECT_MAPPER.readTree(response.body());
sessionId = data.get("session_id").asText();
authUrl = data.get("authorization_url").asText();

Desktop.getDesktop().browse(URI.create(authUrl));

while (true) {
    Thread.sleep(2000);

    HttpRequest statusRequest = HttpRequest.newBuilder()
        .uri(URI.create(API_URL + "/oauth/sessions/" + sessionId))
        .build();

    JsonNode status = OBJECT_MAPPER.readTree(http.send(statusRequest, BodyHandlers.ofString()).body());

    switch (status.get("status").asText()) {
        case "pending" -> continue;
        case "success" -> {
            String sessionToken = status.get("payload").get("session_token").asText();
            handleSuccess(sessionToken, status.get("user"));
            return;
        }
        case "failed" -> throw new IllegalStateException("OAuth failed: " + status.get("error_code").asText());
        case "expired" -> throw new IllegalStateException("OAuth session expired");
    }
}
```

### Обмен `session_token` на авторизацию

1. Ваш AuthAPI или сайт должен принимать `session_token` и обменивать его на обычную авторизационную сессию (например, через отдельный endpoint `/api/auth/login-token`).
2. После успеха сохраните токен лаунчера (JWT/сессионный ключ) и продолжайте стандартный флоу (получение списка аккаунтов, запуск игры).

## Обработка ошибок

- Показать пользователю отдельные сообщения для:
  - «Привяжите VK ID/Yandex ID в профиле» (`identity_not_linked`).
  - «Авторизация отменена» (`provider_error`).
  - «Время ожидания истекло» (`expired`).
- Логируйте ответы API для упрощения отладки (например, `oauth-session.log`).
- Добавьте кнопку «Попробовать снова» для повторного создания сессии.

## Безопасность

- Используйте только HTTPS.
- Не пытайтесь применять `session_token` больше одного раза – он одноразовый.
- Следите за `expires_at`: если время близко к истечению, предупредите пользователя.
- Не храните access token провайдеров VK/Яндекс в лаунчере.

## Тестирование

1. Проверьте, что на сайте в профиле видно карточку «Безопасность» и провайдеры привязаны.
2. Создайте тестовую сессию через `curl` и убедитесь, что API корректно меняет статус.
3. В лаунчере: 
   - успешный заход;
   - сценарий «аккаунт не привязан»;
   - отмена авторизации в браузере;
   - истечение времени (не подтверждать вход).

## Полезные ссылки

- Документация VK ID: https://id.vk.com/about/business/go/docs/ru/vkid/latest/
- Документация OAuth Яндекс ID: https://yandex.ru/dev/id/
- Gravit Launcher: https://gravit-launcher.com/
