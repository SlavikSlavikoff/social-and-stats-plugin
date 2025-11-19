# ADR 001 — Court lifecycle assumptions

Date: 2025-11-18

## Context

The functional specification for the «Суд» module references «жизненный цикл дела», статусы, роль возврата наказаний и режимы «исполнителей». The document does not prescribe exact value sets, duration units, or where configuration should live.

## Assumptions

1. **Case statuses** — the lifecycle will use the enum `draft`, `issued`, `active`, `awaiting_revert`, `completed`, `cancelled`, `revoked`. «Active» means punishments are currently taking effect; `awaiting_revert` marks items scheduled for scheduler rollback; `completed` and `revoked` are terminal states.
2. **Executors** — to reflect «исполнители (site/discord/minecraft)» every case stores `executor` from this closed set. UI defaults to `site`; Discord/Minecraft flows will later reuse API endpoints.
3. **Duration units** — ban/mute durations are stored in minutes to match existing Azuriom moderation helpers. For configurability the UI accepts minutes and human-readable shortcuts (e.g., `3h`, `6m`), normalized server-side to minutes.
4. **Template storage** — template definitions are persisted in DB (`socialprofile_court_templates`) with defaults seeded from config so admins can edit them without code deployment.
5. **Role mapping** — configuration exposes `ban_role`, `mute_role`, and `novice_role` setting keys that contain Azuriom role IDs. Applying punishments swaps these roles and scheduler restores previous assignments.
6. **Webhook retries** — webhook deliveries are persisted with status (`pending`,`sent`,`failed`) and retried via the same scheduler command to satisfy audit and observability requirements.

All downstream code honours these assumptions; any future change requires a new ADR.
