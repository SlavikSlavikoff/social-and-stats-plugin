# Плагин Social Profile — руководство по тестированию

Плагин содержит изолированный набор тестов PHPUnit, smoke-сценарии для интеграций и чек-лист для ручного тестирования. Перед релизом очередной версии пройдите все пункты ниже.

## 1. PHP Unit/Feature tests

```
# Из корня Azuriom
cd plugins/inspiratostats
composer dump-autoload
E:\laragon\bin\php\php-8.4.5-nts-Win32-vs17-x64\php.exe ../../vendor/bin/phpunit
```

Набор тестов проверяет:

- Целостность схемы БД для всех таблиц `socialprofile_*`.
- Поведение `ApiToken` (подстановки в scope, ограничения по IP, парсинг bearer-токена).
- Middleware API: проверку scope/IP, HMAC-подпись (опция), лимиты, правила видимости bundle/coins.
- Основные сценарии админки (правка метрик, доверие, фиксация нарушений, CRUD токенов, настройки).

## 2. Python smoke test

Скрипт `scripts/socialprofile_api_smoke.py` выполняет end-to-end запросы к запущенному Azuriom.

```
python scripts/socialprofile_api_smoke.py \
  --base-url http://127.0.0.1:8000 \
  --nickname demo \
  --token YOUR_FULL_TOKEN \
  --hmac-secret OPTIONAL_SECRET
```

Проверяется следующее:

1. Публичный `/coins` скрывает балансы.
2. Авторизованные `/coins` и `/bundle` возвращают расширенный ответ.
3. Если задан `--hmac-secret`, неподписанный `PUT /coins` отклоняется, а подписанный проходит.
4. Доступ к `/violations` либо отдаёт данные, либо корректно сообщает об отсутствии прав.

Код выхода будет ненулевым при первом несоответствии, поэтому скрипт удобно встраивать в CI.

## 3. Демо на HTTPie / shell

`scripts/socialprofile_api_demo.sh` повторяет smoke-сценарий на HTTPie:

```
BASE_URL=http://127.0.0.1:8000 \
TOKEN=YOUR_FULL_TOKEN \
NICKNAME=demo \
HMAC_SECRET=secret \
bash scripts/socialprofile_api_demo.sh
```

Сценарий выполняет те же проверки, что и Python-скрипт, но выводит «сырые» HTTP-ответы для ручного анализа.

## 4. Коллекция Postman

Импортируйте `docs/socialprofile.postman_collection.json` (используются переменные `base_url`, `token`, `nickname`, `hmac_secret`). Порядок запросов:

1. `Public Coins (No Auth)` — публичный запрос (без авторизации), ожидаем `balance = null`.
2. `Authenticated Coins` — авторизованный запрос к монетам.
3. `Bundle (Full Access)` — полный агрегированный ответ.
4. `Coins Update (HMAC)` — содержит pre-request-скрипт, рассчитывающий `X-Social-Signature`.
5. `Violations List` — список нарушений.

## 5. Чек-лист ручной проверки UI

1. **Admin → Social Dashboard** — карточки показывают лидеров по рейтингу/активности и блок последних нарушений.
2. **Admin → Users** — поиск, форма правки метрик, блок доверия, создание записи о нарушении.
3. **Admin → Tokens** — форма создаёт токен (plaintext показывается один раз), доступны действия rotate/delete.
4. **Admin → Settings** — ввод лимитов, чекбоксы видимости, переключатель HMAC и секрет; всё сразу влияет на API.
5. **Профиль пользователя (/profile)** — карточки плагина появляются даже при пустых данных (отображаются заглушки).
6. **Публичный рейтинг (/leaderboards/social)** — выводит таблицы по соц. рейтингу и активности.

Любые результаты тестов фиксируйте задачами до мержа.
