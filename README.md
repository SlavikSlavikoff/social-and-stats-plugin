# Social Profile (Inspirato)

The **Social Profile** plugin extends Azuriom user profiles with community metrics used by Inspirato services. It exposes read/write APIs, adds dashboards for staff, and enriches the `/account` and `/leaderboards` pages with new blocks.

## Features

- Community metrics: social score, activity points, virtual coins, trust levels, verification status, gameplay stats, and violations history.
- Secure API v1 with bearer tokens, scopes, IP allow-lists, and rate limiting.
- Admin dashboard for quick insights, token management, verification reviews, and trust mapping.
- Public profile widgets and leaderboard views styled with Inspirato glass aesthetics.
- Event hooks (`CoinsChanged`, `TrustLevelChanged`, etc.) for further automation.

## Installation

1. Copy the plugin directory to `plugins/socialprofile` in your Azuriom installation.
2. Run the plugin migrations:

   ```bash
   php artisan migrate --path=plugins/socialprofile/database/migrations
   ```
3. Activate the plugin from the Azuriom admin panel.

## Database Schema

All tables use the `socialprofile_` prefix:

| Table | Purpose |
|-------|---------|
| `socialprofile_social_scores` | Aggregate reputation for each user. |
| `socialprofile_activity_points` | Accumulated activity metric. |
| `socialprofile_coin_balances` | Virtual currency balance and holds. |
| `socialprofile_game_statistics` | Playtime and expandable KPI fields. |
| `socialprofile_trust_levels` | Trust stage with staff note/history. |
| `socialprofile_violations` | Moderation history with soft deletes. |
| `socialprofile_verifications` | Verification status and metadata. |
| `socialprofile_api_tokens` | Bearer tokens, scopes, IP/rate limits. |

## Permissions

| Permission | Description |
|------------|-------------|
| `social.view` | Access the social dashboard. |
| `social.edit` | Edit metrics and settings. |
| `social.grant_trust` | Promote/demote trust levels. |
| `social.manage_tokens` | Manage API tokens. |
| `social.moderate_violations` | Record or delete violations. |
| `social.verify_accounts` | Approve or reject verifications. |

## API v1

Base path: `/api/social/v1`

All write operations require a bearer token with the relevant scope and may be rate limited by `throttle:socialprofile-token`. Public GETs inherit `throttle:socialprofile-public` but automatically restrict the payload for unverified users or when public visibility is disabled.

| Endpoint | Method(s) | Scope(s) | Description |
|----------|-----------|----------|-------------|
| `/user/{nickname}/stats` | `GET`, `PUT` | `stats:read`, `stats:write` | Gameplay statistics. |
| `/user/{nickname}/activity` | `GET`, `PUT` | `activity:read`, `activity:write` | Activity points. |
| `/user/{nickname}/coins` | `GET`, `PUT` | `coins:read`, `coins:write` | Virtual currency balances. |
| `/user/{nickname}/social-score` | `GET`, `PUT` | `score:read`, `score:write` | Social reputation score. |
| `/user/{nickname}/trust-level` | `GET`, `PUT` | `trust:read`, `trust:write` | Trust level management. |
| `/user/{nickname}/violations` | `GET`, `POST` | `violations:read`, `violations:write` | Moderation history and creation. |
| `/user/{nickname}/bundle` | `GET` | `bundle:read` | Aggregated safe payload for profile display. |
| `/user/{nickname}/verification` | `GET`, `PUT` | `verify:read`, `verify:write` | Verification workflow. |

### Token scopes

Supported scope values:

```
stats:read, stats:write
activity:read, activity:write
coins:read, coins:write
score:read, score:write
trust:read, trust:write
violations:read, violations:write
verify:read, verify:write
bundle:read
```

Tokens are hashed with SHA-256 and can be restricted to IP lists and per-minute rate limits. Use the admin panel under **API tokens** to manage them.

### Rate limiting

- `socialprofile-public`: default 60 req/min per IP (configurable).
- `socialprofile-token`: default 120 req/min per token or IP (configurable, overridable per token).

### HMAC signatures

Optionally enable HMAC protection in **Settings**. When active, clients must send `X-Social-Signature` with the SHA-256 hash of the raw request body using the configured secret.

## Events

| Event | Trigger |
|-------|---------|
| `SocialStatsUpdated` | Statistics updated. |
| `ActivityChanged` | Activity points adjusted. |
| `CoinsChanged` | Coin balance changed. |
| `TrustLevelChanged` | Trust level updated. |
| `ViolationAdded` | New violation recorded. |
| `VerificationChanged` | Verification status changed. |

Subscribe to these events to propagate data to other services or message queues.

## UI Blocks

- `/account/social` (user menu: “My Progress”).
- `/leaderboards/social` (leaderboard menu).
- Admin dashboard with dedicated pages for users, violations, tokens, and settings.

All views load `assets/css/style.css` for glassmorphism-inspired styling compatible with Inspirato’s theme.

## Logging

Sensitive changes call the Azuriom `action()` helper when available so that coin operations, trust adjustments, verification changes, and token management are recorded in the action log.

## Testing & Build

Run plugin checks through your Azuriom instance (migrations + tests) before deployment. Ensure rate-limiter settings align with your infrastructure.
