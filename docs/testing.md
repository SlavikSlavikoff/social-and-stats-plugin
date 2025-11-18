# Social Profile Plugin – Testing Guide

This plugin ships with an isolated PHPUnit test suite, integration smoke scripts, and manual QA steps. Follow the checklist below before releasing a new build.

## 1. PHP Unit/Feature tests

```
# From the Azuriom root
cd plugins/inspiratostats
composer dump-autoload
E:\laragon\bin\php\php-8.4.5-nts-Win32-vs17-x64\php.exe ../../vendor/bin/phpunit
```

What the suite covers:

- Database schema integrity for every `socialprofile_*` table.
- `ApiToken` behaviours (scope wildcards, IP restrictions, bearer parsing).
- API middleware: scope/IP validation, optional HMAC signature, rate limiters, bundle/coins visibility rules.
- Admin UI flows (metrics edit, trust management, violation logging, token CRUD, settings form).

## 2. Python smoke test

The script `scripts/socialprofile_api_smoke.py` runs end-to-end requests against a running Azuriom instance.

```
python scripts/socialprofile_api_smoke.py \
  --base-url http://127.0.0.1:8000 \
  --nickname demo \
  --token YOUR_FULL_TOKEN \
  --hmac-secret OPTIONAL_SECRET
```

Checks performed:

1. Public `/coins` hides balances.
2. Authenticated `/coins` and `/bundle` expose extended payload.
3. If `--hmac-secret` is set, unsigned `PUT /coins` is rejected and signed update succeeds.
4. `/violations` access reports data or a permission error.

Exit code is non-zero when an expectation fails, so it can be wired into CI.

## 3. HTTPie / shell demo

Use `scripts/socialprofile_api_demo.sh` for a repeatable HTTPie scenario:

```
BASE_URL=http://127.0.0.1:8000 \
TOKEN=YOUR_FULL_TOKEN \
NICKNAME=demo \
HMAC_SECRET=secret \
bash scripts/socialprofile_api_demo.sh
```

The script performs the same checks as the Python smoke test but prints raw HTTP responses for manual inspection.

## 4. Postman collection

Import `docs/socialprofile.postman_collection.json` (uses variables `base_url`, `token`, `nickname`, `hmac_secret`). Collection order:

1. `Public Coins (No Auth)` – expect `balance` `null`.
2. `Authenticated Coins`.
3. `Bundle (Full Access)`.
4. `Coins Update (HMAC)` – includes a pre-request script that calculates `X-Social-Signature`.
5. `Violations List`.

## 5. Manual UI checklist

1. **Admin → Social Dashboard** – cards show top scores/activity и блок последних нарушений.
2. **Admin → Users** – search, metric edit form, trust block, violation log creation.
3. **Admin → Tokens** – creation form generates plaintext token once, rotate/delete actions.
4. **Admin → Settings** – rate limit inputs, visibility checkboxes, HMAC toggle with secret; toggles immediately apply to the API.
5. **User profile (/profile)** – cards from the plugin appear with empty states instead of disappearing.
6. **Public leaderboard (/leaderboards/social)** – shows ranking for scores and activity.

Document any findings directly in issues before merging.
