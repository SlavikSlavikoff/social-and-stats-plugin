# Court module overview

## Purpose

The «Суд» module formalises the complete lifecycle of moderation decisions inside the Inspirato Azuriom plugin. It brings configurable punishment templates, manual decisions, audit history, listeners, webhook notifications, and a recoverable scheduler that can undo time-based role swaps (ban/mute).

## Core concepts

- **Case** — a judicial record that references the subject (player), judge, executor channel (currently always `site`), template (optional) and current status.
- **Action** — a concrete effect linked to a case (metric delta, ban, mute, role switch, note). Actions can schedule automatic reverts.
- **Template** — predefined payload with deltas, ban/mute durations and base comments. Stored in DB (`socialprofile_court_templates`) and editable from the admin UI.
- **Revert ticket** — queued job describing what to restore when a timed action expires.
- **Log** — append-only audit trail for each case; backed by DB + webhook deliveries for external consumers.

## Lifecycle & statuses

1. Judge opens the Court page (permission `social.court.judge`).
2. They pick **Auto** (template) or **Manual** mode.
3. Service validates limits, ensures ban/mute roles configured, and persists the case with `status=active`.
4. Each action is applied immediately; role changes capture a snapshot and optionally queue reverts (ban/mute/role).
5. Scheduler (`socialprofile:court:tick`) watches `socialprofile_court_revert_jobs` and `socialprofile_court_webhook_deliveries` to undo roles and flush webhooks.
6. Case status transitions:
   - `issued` → `active` once actions were stored
   - `awaiting_revert` when timers exist
   - `completed` after all actions reverted
   - `cancelled`/`revoked` via future cancellation flows

## Database summary

| Table | Purpose |
|-------|---------|
| `socialprofile_court_templates` | Configurable templates with payload JSON and limits |
| `socialprofile_court_cases` | Case metadata, statuses, relationships, indices on `user_id`, `judge_id`, `status`, `created_at`, `expires_at` |
| `socialprofile_court_actions` | Punishment actions (ban/mute/metrics/role) and timers |
| `socialprofile_court_state_snapshots` | Stored previous roles for safe revert |
| `socialprofile_court_revert_jobs` | Scheduler queue for expirations |
| `socialprofile_court_logs` | Fine-grained audit history |
| `socialprofile_court_attachments` | Evidence links |
| `socialprofile_court_webhooks` / `_deliveries` | Outbound notifications and retry queue |

## UI & permissions

- **/court/judge** – рабочее место судьи. Доступно авторизованным пользователям с правом `social.court.judge`, работает вне админки и содержит оба режима (авто/ручной) + быстрый список дел.
- **Admin > Court** – стартовая точка с поиском и ссылкой в архив.
- **Admin > Court > Archive** – пагинация дел (право `social.court.archive`).
- **Admin > Court > Settings** – роли, лимиты, вебхуки (права `social.court.manage_settings` / `social.court.webhooks`).
- **Admin > Court > Templates** – отдельная страница редактирования шаблонов + “Refresh from config”.
- **/court** – публичный список решений (чтение при праве `social.court.archive`).

## Template management

- Templates live in DB (`socialprofile_court_templates`) but can be edited from the new admin screen.
- Each template exposes editable fields (name, key, base comment, payload JSON, activity flag).
- The refresh button wipes cached defaults (`setting('socialprofile_court_templates_seeded')`) and re-seeds from `config/court.php`.
- Deleting a template only prevents future auto cases; existing cases keep their template_id for audit.

## Scheduling & listeners

- `RunCourtScheduler` command registered + scheduled every 5 minutes.
- Event `CourtDecisionChanged` notifies:
  - `DispatchCourtWebhooks` — enqueues webhook deliveries and retries with exponential fallback.
  - `ForwardCourtDecisionToIntegrations` — placeholder listener (see `docs/TODO.md#listeners`) for Minecraft/bot bridges.

## Testing strategy

- Unit — `CourtServiceTest` validates metric deltas + timers.
- Feature — Admin form submission, API JSON, web visibility, scheduler revert.
- Policy — see `tests/Feature/Http/Web/CourtControllerTest.php`.
- Scheduler — `CourtSchedulerTest` covers cron-safe idempotency.

## Next steps

- Wire integrations into the placeholder listener once Minecraft/bot endpoints are approved.
- Expand attachments to handle uploads instead of URLs.
