# Court integration points

## Events & listeners

- `Azuriom\Plugin\InspiratoStats\Events\CourtDecisionChanged`
  - Fired after every issue/update/revert.
  - Carries the `CourtCase` instance and action name (`issued`, `reverted`, `updated`).
- Listeners:
  - `DispatchCourtWebhooks` — persists webhook deliveries (`socialprofile_court_webhook_deliveries`). Extend by adding more filters or enrich payload before storing.
  - `ForwardCourtDecisionToIntegrations` — placeholder (see `docs/TODO.md#listeners`). Wire your Minecraft/Bot adapters here by pulling `event->case` and calling external APIs.

## Scheduler contract

Run `php artisan socialprofile:court:tick` every 5 minutes (registered automatically via plugin schedule). It processes:

1. **Revert queue** — jobs in `socialprofile_court_revert_jobs` with `run_at <= now()`.
   - Executes `CourtService::revertRoleAction`.
   - Marks case `completed` when all actions are reverted.
2. **Webhook queue** — `socialprofile_court_webhook_deliveries` with `status=pending` and due `next_attempt_at`.
   - Sends POST with JSON payload + optional HMAC header.
   - Retries with configurable backoff (`config('socialprofile.court.webhook.*')`).

To integrate with external schedulers (e.g., systemd, Azure WebJobs) call the artisan command directly; it is idempotent.

## Minecraft / Discord bridges

1. Use the internal API (`POST /api/social/v1/court/cases`) with authenticated users or future bot tokens to register actions coming from game moderators.
2. Listen to webhooks with events `issued`, `reverted`, `updated`. Provide the endpoint in the admin UI; payload includes case metadata plus condensed action info.
3. For near-real-time updates inside the plugin, decorate `ForwardCourtDecisionToIntegrations`:
   ```php
   Event::listen(CourtDecisionChanged::class, function ($event) {
       app(MinecraftBridge::class)->notify($event->case);
       app(DiscordBridge::class)->sendEmbed($event->case);
   });
   ```

## Attachments & evidence

Currently the UI stores up to 3 URL attachments per case. Replace the storage logic in `CourtDecisionsController::storeAttachments` to push files to S3 or embed minio links, then expose them via the case API.

## Extending punishments

- Add new action types by extending `CourtAction` constants and the `match` in `CourtService::applyAction`.
- Ensure the scheduler knows whether it must revert (update `shouldScheduleRevert`).
- Add UI fields (Blade templates) and validation (controllers + `CourtService::normalizePayload`).

## Template refresh hook

- The admin "Refresh from config" button triggers `CourtTemplateSeeder`, overwriting templates with matching keys using the payloads declared in `config/court.php`.
- Custom keys (those not present in config) remain untouched, so you can safely keep bespoke templates alongside the defaults.
- Clearing the `socialprofile_court_templates_seeded` setting and calling the seeder programmatically yields the same effect if you need to automate template syncs from deployments.
