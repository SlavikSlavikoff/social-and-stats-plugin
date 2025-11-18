#!/usr/bin/env python3
"""
Quick smoke test for the Social Profile plugin API.

Example:
    python scripts/socialprofile_api_smoke.py \
        --base-url http://127.0.0.1:8000 \
        --nickname demo \
        --token YOUR_FULL_TOKEN \
        --hmac-secret supersecret
"""

from __future__ import annotations

import argparse
import hmac
import json
import sys
from hashlib import sha256
from typing import Any, Dict, Optional

import requests


class SmokeError(RuntimeError):
    """Raised when a smoke check fails."""


def request(
    method: str,
    base_url: str,
    path: str,
    token: Optional[str] = None,
    payload: Optional[Dict[str, Any]] = None,
    hmac_secret: Optional[str] = None,
) -> requests.Response:
    url = f"{base_url.rstrip('/')}{path}"
    headers = {"Accept": "application/json"}

    data = None
    if payload is not None:
        data = json.dumps(payload)
        headers["Content-Type"] = "application/json"
        if hmac_secret:
            headers["X-Social-Signature"] = hmac.new(
                hmac_secret.encode(),
                data.encode(),
                sha256,
            ).hexdigest()

    if token:
        headers["Authorization"] = f"Bearer {token}"

    response = requests.request(method, url, data=data, headers=headers, timeout=10)
    return response


def ensure(condition: bool, message: str) -> None:
    if not condition:
        raise SmokeError(message)


def main() -> int:
    parser = argparse.ArgumentParser(description="Social Profile API smoke tests")
    parser.add_argument("--base-url", required=True, help="Azuriom base URL, e.g. http://127.0.0.1:8000")
    parser.add_argument("--nickname", required=True, help="Target user nickname")
    parser.add_argument("--token", required=True, help="Bearer token with full access (stats/coins/bundle)")
    parser.add_argument("--hmac-secret", help="HMAC secret (only needed if HMAC is enabled)")

    args = parser.parse_args()
    base = args.base_url.rstrip("/")
    nick = args.nickname
    token = args.token

    print("1) GET /coins without token should hide the balance...")
    resp = request("GET", base, f"/api/social/v1/user/{nick}/coins")
    ensure(resp.status_code == 200, f"Unexpected status: {resp.status_code} -> {resp.text}")
    data = resp.json()
    ensure(data.get("balance") in (None, 0) or data.get("can_view_balance") is False, "Balance should be hidden for public calls.")

    print("2) GET /coins with token should expose balance/hold...")
    resp = request("GET", base, f"/api/social/v1/user/{nick}/coins", token=token)
    ensure(resp.status_code == 200, f"Authorized request failed: {resp.text}")
    data = resp.json()
    ensure("balance" in data and data["balance"] is not None, "Balance is missing for authenticated call.")

    print("3) GET /bundle exposes extended payload...")
    resp = request("GET", base, f"/api/social/v1/user/{nick}/bundle", token=token)
    ensure(resp.status_code == 200, f"Bundle call failed: {resp.text}")
    ensure("statistics" in resp.json(), "Bundle payload missing statistics block.")

    if args.hmac_secret:
        print("4) PUT /coins without signature should be rejected...")
        payload = {"balance": 5, "hold": 0}
        resp = request("PUT", base, f"/api/social/v1/user/{nick}/coins", token=token, payload=payload)
        ensure(resp.status_code in (401, 403), f"Expected rejection, got {resp.status_code}: {resp.text}")

        print("5) PUT /coins with signature should be accepted...")
        resp = request(
            "PUT",
            base,
            f"/api/social/v1/user/{nick}/coins",
            token=token,
            payload=payload,
            hmac_secret=args.hmac_secret,
        )
        ensure(resp.status_code == 200, f"HMAC protected call failed: {resp.status_code} -> {resp.text}")

    print("6) GET /violations using token...")
    resp = request("GET", base, f"/api/social/v1/user/{nick}/violations", token=token)
    ensure(resp.status_code in (200, 403), f"Unexpected response for violations: {resp.status_code}")
    if resp.status_code == 200:
        ensure(isinstance(resp.json(), list), "Violations payload should be a list.")
    else:
        print("   (violations API restricted for this token, skipping payload check)")

    print("Smoke tests finished successfully.")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except SmokeError as exc:
        print(f"[FAIL] {exc}", file=sys.stderr)
        sys.exit(1)
    except requests.RequestException as exc:
        print(f"[HTTP ERROR] {exc}", file=sys.stderr)
        sys.exit(2)
