# ADR 002 — API payload conventions

Date: 2025-11-18

## Context

The specification mentions «Ник нарушителя вводится вручную» for the UI, while earlier requirements for bots/server integrations discussed an API/webhook without defining identifier formats. To keep API calls deterministic we needed to choose identifiers and duration formats.

## Assumptions

1. **Internal API uses user IDs** — `/api/social/v1/court/cases` expects `subject_id` (numeric). UI keeps the nickname input for judges, but programmatic integrations send stable IDs retrieved from other API endpoints.
2. **Duration strings** — API and forms accept integers (minutes) as well as `3h`, `30d`, `6m` shortcuts (hours/days/months). `0` is reserved for cancellation and negative numbers are rejected.
3. **Executor declaration** — bots must set `executor` to one of `site|discord|minecraft` so downstream listeners know which channel produced the decision.

Any change to identifiers or duration grammar must update this ADR plus the validation layer.
