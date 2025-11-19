# Автоматизация Inspirato Stats

Этот документ поясняет, как работает модуль автоматизации и как конфигурировать все сценарии.

## События (Triggers)

1. **`role_changed` — смена роли пользователя**
   - Срабатывает при любом обновлении роли в Azuriom.
   - Payload: `user_id`, `username`, `old_role_id`, `new_role_id`, `actor_id`.
   - Условия в правилах: списки ролей «от» и «к» (можно `*`).

2. **`trust_level_changed` — смена уровня доверия**
   - Использует кастомную модель `TrustLevel` плагина.
   - Payload: `user_id`, `username`, `old_level`, `new_level`, `actor_id`, `note`.
   - Условия: списки уровней «от»/«к» и числовые пороги (>= / <=).

3. **`activity_changed` — изменение активности**
   - Запускается, когда меняются очки активности (`ActivityPoint`).
   - Payload: `user_id`, `username`, `activity_points`.
   - Условия: минимальный/максимальный порог активности.

4. **`coins_changed` — изменение монет**
   - Любое обновление баланса монет или холда (`CoinBalance`).
   - Payload: `user_id`, `username`, `coin_balance`, `coin_hold`, `coin_context`.
   - Условия: диапазоны для balances/hold.

5. **`social_stats_updated` — обновление игровых метрик**
   - Передаёт сыгранные минуты, убийства, смерти и extra-метрики.
   - Payload: `user_id`, `username`, `played_minutes`, `kills`, `deaths`, `extra_metrics`.
   - Условия: числовые диапазоны для основных метрик.

6. **`violation_added` — добавлено нарушение**
   - Срабатывает при создании модели `Violation` (через API или админку).
   - Payload: `user_id`, `username`, `violation_id`, `violation_type`, `violation_points`, `violation_reason`.
   - Условия: типы нарушений и диапазон штрафных баллов.

7. **`court_decision_changed` — решение суда**
   - CourtService вынес или обновил дело (`CourtCase`).
   - Payload: `case_id`, `case_number`, `case_status`, `case_mode`, `case_action`, `case_executor`, `metrics_applied`, `user_id`.
   - Условия: нужные статусы/режимы/события и фильтр по исполнителю.

8. **`monthly_top` — ежемесячная выдача наград**
   - Запускается планировщиком `socialprofile:automation:tick`.
   - На вход получает массив победителей с полями `user_id`, `username`, `position`, `source_metric`.
   - Условия: тип топа (`source_metric`) и диапазон позиций.


## Действия (Actions)

### 1. `minecraft_rcon_command`

- Ожидает ссылку на интеграцию типа `minecraft_rcon` и строку команды.
- Поддерживаются плейсхолдеры `{username}`, `{user_id}` и т. д.
- Требует открытый RCON в `server.properties`.

### 2. `minecraft_db_query`

- Интеграция типа `minecraft_db` (обычно MySQL/PostgreSQL).
- Подходит для whitelist/blacklist таблиц, выдач привилегий.
- SQL выполняется через отдельное подключение без транзакций.

### 3. `social_bot_request`

- Интеграция `social_bot`: HTTP-метод, путь/URL, заголовки, тело.
- Полезно для Discord/Telegram ботов или внутренних вебхуков.
- Тело (JSON) поддерживает плейсхолдеры.

### 4. `internal_reward`

- Без интеграции; изменяет данные плагина напрямую.
- Поля: `social_score`, `coins`, `activity`, `note`, `direction` (increase/decrease).
- Позволяет начислять или списывать очки.

### 5. `assign_role`

- Назначает пользователю роль Azuriom, используя ID роли из формы.
- Требует, чтобы rule-пейлоад содержал `user_id` (обычно так и есть).
- Полезно для автопонижений/повышений после событий (например, нарушение → роль «banned»).


## Интеграции

Типы интеграций и обязательные поля:

- `minecraft_rcon`: `host`, `port`, `password`, `timeout` (опц.).
- `minecraft_db`: `driver` (`mysql`/`pgsql`), `host`, `database`, `username`, `password`, `port`.
- `social_bot`: `base_url`, `token`, `default_headers` (json), `channel` (опц.).

Каждую интеграцию можно пометить «по умолчанию» — тогда она подставляется в новых действиях.

## Планировщик

- Настраивается во вкладке «Планировщик».
- Поля: включение, день/час запуска (по времени сервера), число победителей, список метрик для топа, величина наград отдельно для соц. рейтинга, монет и активности.
- После успешной выдачи поле `last_run` обновляется и повторный запуск за месяц не произойдет.

## Логи и отладка

- Каждый запуск правила пишется в таблицу `socialprofile_automation_logs` с полем `status` (`success`, `error`, `skipped`).
- Из админки можно отфильтровать по событию или пользователю и повторно запустить действия (`Re-run`). Это повторяет все действия, связанные с записью.

## Расширение через код

- Добавление нового триггера: реализовать метод в `AutomationService::dispatch` и зарегистрировать значение в `config/automation.php`.
- Новый action добавляется в `AutomationActionExecutor` с уникальным `type` и описанием в конфиге.
- В тестах рекомендуется использовать фабрики `AutomationRuleFactory` и `AutomationIntegrationFactory`.

## Безопасность

- Токены и пароли интеграций шифруются storage'ом Laravel (если `APP_KEY` задан). При логировании значения маскируются.
- RCON/DB запросы имеют таймаут и ограничение по количеству попыток.
- Для вебхуков рекомендуется ограничить IP на стороне ботов.
