# Точки интеграции модуля Court

## События и слушатели

- `Azuriom\Plugin\InspiratoStats\Events\CourtDecisionChanged`
  - Триггерится после любых `issue/update/revert`.
  - Передаёт экземпляр `CourtCase` и действие (`issued`, `reverted`, `updated`).
- Слушатели:
  - `DispatchCourtWebhooks` — сохраняет доставку вебхуков в `socialprofile_court_webhook_deliveries`. Можно расширять фильтрами или обогащать полезную нагрузку перед записью.
  - `ForwardCourtDecisionToIntegrations` — заглушка (см. `docs/TODO.md#listeners`). Здесь подключаются мосты в Minecraft/ботов: берите `$event->case` и обращайтесь к внешним API.

## Контракт планировщика

Команда `php artisan socialprofile:court:tick` должна выполняться каждые 5 минут (в плагине уже зарегистрирована). Она обрабатывает:

1. **Очередь откатов.** Строки `socialprofile_court_revert_jobs` с `run_at <= now()`.
   - Вызывает `CourtService::revertRoleAction`.
   - Переводит дело в `completed`, когда все действия возвращены.
2. **Очередь вебхуков.** Записи `socialprofile_court_webhook_deliveries` со статусом `pending`, у которых наступил `next_attempt_at`.
   - Отправляет POST с JSON и опциональной HMAC-подписью.
   - Повторяет доставку с настраиваемым бэкофом (`config('socialprofile.court.webhook.*')`).

Если используете внешний планировщик (systemd, Azure WebJobs и т. п.) — просто вызывайте artisan-команду, она идемпотентна.

## Мосты Minecraft / Discord

1. Используйте внутренний API (`POST /api/social/v1/court/cases`) от имени авторизованных пользователей или будущих бот-токенов, чтобы фиксировать решения из игры.
2. Подписывайтесь на вебхуки `issued`, `reverted`, `updated`. Укажите конечную точку в админке — тело запроса содержит полное описание дела и действий.
3. Для онлайн-уведомлений внутри плагина декорируйте `ForwardCourtDecisionToIntegrations`:
   ```php
   Event::listen(CourtDecisionChanged::class, function ($event) {
       app(MinecraftBridge::class)->notify($event->case);
       app(DiscordBridge::class)->sendEmbed($event->case);
   });
   ```

## Вложения и доказательства

Интерфейс сейчас хранит до трёх URL на дело. Если нужны загрузки файлов, переопределите `CourtDecisionsController::storeAttachments`, чтобы отправлять их в S3/MinIO, а ссылки отдавать через API.

## Расширение наказаний

- Добавляйте новые типы действий, расширяя константы `CourtAction` и `match` внутри `CourtService::applyAction`.
- Сообщайте планировщику, нужно ли планировать откат (`shouldScheduleRevert`).
- Расширяйте Интерфейс (Blade), валидацию (контроллеры + `CourtService::normalizePayload`).

## Обновление шаблонов

- Кнопка «Refresh from config» вызывает `CourtTemplateSeeder`, перезаписывая шаблоны с совпадающими ключами данными из `config/court.php`.
- Пользовательские ключи (которых нет в конфиге) не трогаются, поэтому можно держать корпоративные шаблоны рядом с дефолтами.
- Эффект можно воспроизвести программно: очистите `socialprofile_court_templates_seeded` и вызовите сидер, если нужно синхронизировать шаблоны при деплое.
