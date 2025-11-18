#!/usr/bin/env bash
#
# Quick HTTPie demo script for the Social Profile API.
# Requirements: httpie, openssl
#
# Usage:
#   BASE_URL=http://127.0.0.1:8000 \
#   TOKEN=yourtoken \
#   NICKNAME=demo \
#   HMAC_SECRET=secret \
#   bash scripts/socialprofile_api_demo.sh

set -euo pipefail

: "${BASE_URL:?Missing BASE_URL}"
: "${TOKEN:?Missing TOKEN}"
: "${NICKNAME:?Missing NICKNAME}"

step() {
    echo ""
    echo "==> $*"
}

http_cmd() {
    http --check-status --print b "$@"
}

step "Public coins call (should hide the balance)"
http_cmd GET "$BASE_URL/api/social/v1/user/$NICKNAME/coins"

step "Authenticated coins call"
http_cmd GET "$BASE_URL/api/social/v1/user/$NICKNAME/coins" "Authorization:Bearer $TOKEN"

step "Bundle payload"
http_cmd GET "$BASE_URL/api/social/v1/user/$NICKNAME/bundle" "Authorization:Bearer $TOKEN"

if [[ -n "${HMAC_SECRET:-}" ]]; then
    PAYLOAD='{"balance":10,"hold":0}'
    SIG=$(printf '%s' "$PAYLOAD" | openssl dgst -sha256 -hmac "$HMAC_SECRET" -binary | xxd -p -c 256)

    step "Attempting to update coins with missing signature (should fail)"
    if http PUT "$BASE_URL/api/social/v1/user/$NICKNAME/coins" "Authorization:Bearer $TOKEN" <<<"$PAYLOAD"; then
        echo "WARNING: request succeeded without signature (is HMAC disabled?)"
    fi

    step "Updating coins with proper signature"
    printf '%s' "$PAYLOAD" | \
        http PUT "$BASE_URL/api/social/v1/user/$NICKNAME/coins" \
            "Authorization:Bearer $TOKEN" \
            "X-Social-Signature:$SIG"
else
    echo "HMAC_SECRET not provided, skipping signed call."
fi

step "Fetching violations (requires full access token)"
if ! http_cmd GET "$BASE_URL/api/social/v1/user/$NICKNAME/violations" "Authorization:Bearer $TOKEN"; then
    echo "Violations endpoint rejected the request (missing scope?)"
fi

echo ""
echo "Demo finished."
