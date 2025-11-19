# Модель безопасности Court

## Права доступа

| Право | Назначение |
|-------|-----------|
| `social.court.judge` | Доступ к странице Court, вынесение решений, внутренний API |
| `social.court.archive` | Просмотр архива (`/court` и страница в админке) |
| `social.court.manage_settings` | Настройка ролей, лимитов и шаблонов |
| `social.court.webhooks` | Управление вебхуками |

Навигация админки учитывает эти права; маршрут `/court` обёрнут в `auth + can:social.court.archive`.

## Лимиты

- Публичный API (`socialprofile-court-public`) — 60 запросов/мин./IP (`config/socialprofile.court.rate_limits`).
- Внутренний API (`socialprofile-court-internal`) — 120 RPM на пользователя/IP.
- Дополнительные ограничители:
  - `setting('socialprofile_court_judge_hour_limit')` — дел в час на судью (по умолчанию 30).
  - `setting('socialprofile_court_user_daily_limit')` — дел в сутки на игрока (по умолчанию 3).
  - Дельты метрик ограничены `config('socialprofile.court.limits.metric_delta_*')`.

## Видимость и архив

- Поле `visibility`: `private`, `judges`, `public`. По умолчанию берётся `setting('socialprofile_court_default_visibility', 'judges')`.
- `/api/social/v1/court/public` отдаёт только публичные дела.
- `/court` и архив админки показывают `public` + `judges`, если у пользователя есть `social.court.archive`.

## Доставка вебхуков

- Опциональный заголовок HMAC `X-Court-Signature` (SHA256), если секрет задан.
- Устойчивая очередь повторных попыток, число ретраев настраивается.
- Логи (статус + ответ) хранятся для аудита.

## Безопасность планировщика

- `socialprofile:court:tick` использует транзакции, отмечает задания статусами `pending → running → completed/failed`.
- Ошибки из-за отсутствующих пользователей/действий логируются и переносят задачу на +5 минут.
- Перед сменой ролей сохраняется снапшот (`socialprofile_court_state_snapshots`), что гарантирует обратимость.

## Валидация

- Комментарии ограничены 5000 символами.
- Длительности валидируются и нормализуются в минуты.
- Ролевые действия требуют настроенных ID ролей; иначе запрос отклоняется.
- Формы и API проверяют существование игрока и URL вложений.

## Аудит и логи

- `socialprofile_court_logs` фиксирует каждое событие с актором и набором данных.
- Очереди вебхуков и откатов дают трассировку.
- Во всех таблицах есть временные метки и индексы по `user_id`, `judge_id`, `status`, `created_at`, `expires_at` для ускоренного анализа.
