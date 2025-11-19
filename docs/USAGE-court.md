# Court usage guide

## Issuing a decision

1. Open **/court/judge** (user must have the `social.court.judge` permission).
2. Pick one of the forms:
   - **Auto punishment** — choose a template, optionally add a comment/continued case and click *Issue auto punishment*.
   - **Manual punishment** — fill the deltas (metrics, coins, money), ban/mute durations, role swap and evidence links.
3. Submit the form. The service validates limits, stores the case, applies actions and triggers webhooks.

> The old form inside the admin panel is now read-only and simply links to this workspace.

## Revoking or extending a punishment

- Set the desired metric delta or duration to `0` to cancel a timer.
- Issue a follow-up case referencing the previous ID in the **Continued case** field if you need to extend the same punishment.

## Managing templates

1. Go to **Admin -> Social Profile -> Court -> Templates** (permission `social.court.manage_settings`).
2. Edit or create templates (name, key, default executor, payload JSON).
3. Use **Refresh from config** to re-import defaults from `config/court.php`.

## Webhooks

- Configure under **Admin -> Court -> Settings -> Webhooks**.
- Each webhook stores URL, optional secret and events (`issued`, `updated`, `reverted`).
- Full form/payload/queue breakdown: [`docs/WEBHOOKS-court.md`](WEBHOOKS-court.md).

## Rate limits & safety

- Judges have hourly and per-subject daily limits (see court settings).
- The public archive requires `social.court.archive`; the judge workspace requires `social.court.judge`.
- API tokens expose dedicated scopes (see the Tokens page descriptions).
