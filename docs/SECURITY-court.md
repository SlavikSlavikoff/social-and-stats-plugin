# Court security model

## Permissions

| Permission | Purpose |
|------------|---------|
| `social.court.judge` | Access Court page, issue decisions, internal API |
| `social.court.archive` | View card archive (`/court`, admin archive) |
| `social.court.manage_settings` | Edit Court settings (roles, limits, templates) |
| `social.court.webhooks` | Manage webhook endpoints |

Admin navigation respects these roles; the `/court` front-end route is wrapped in `auth + can:social.court.archive`.

## Rate limiting

- Public API throttle (`socialprofile-court-public`): default 60 requests/min/IP (`config/socialprofile.court.rate_limits`).
- Internal API throttle (`socialprofile-court-internal`): default 120 RPM per user or IP.
- Service-level guard rails:
  - `setting('socialprofile_court_judge_hour_limit')` (# cases per judge per hour, default 30).
  - `setting('socialprofile_court_user_daily_limit')` (# cases per player per day, default 3).
  - Metric deltas clamped to `config('socialprofile.court.limits.metric_delta_*')`.

## Visibility & archive

- Case `visibility`: `private`, `judges`, `public`. Admin UI defaults to `setting('socialprofile_court_default_visibility', 'judges')`.
- `/api/social/v1/court/public` exposes only `public`.
- `/court` + admin archive show `public` + `judges` entries if viewer has `social.court.archive`.

## Webhook delivery

- Optional HMAC header `X-Court-Signature` (SHA256) when secret is set.
- Persistent retry queue, max attempts configurable.
- Delivery log (status + response) kept for auditing.

## Scheduler safety

- `socialprofile:court:tick` uses DB transactions, marks jobs `pending → running → completed/failed`.
- Jobs failing due to missing users/actions log the error and reschedule (5 min).
- Role snapshots stored before each change (`socialprofile_court_state_snapshots`), guaranteeing reversible transitions.

## Validation

- Comments capped (5000 chars).
- Durations validated + normalised to minutes.
- Role-based actions require configured role IDs; otherwise request rejected.
- API & forms enforce valid player nickname and enforce attachments (URLs only).

## Audit/logging

- `socialprofile_court_logs` records each event with actor + payload.
- Webhook deliveries and revert jobs provide traceability.
- All database tables include timestamps for forensic analysis; indexes on `user_id`, `judge_id`, `status`, `created_at`, `expires_at` accelerate audits.
