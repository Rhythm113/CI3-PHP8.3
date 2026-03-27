#!/usr/bin/env python3
"""
test_api.py - CI3 API Test Script

Tests all API endpoints after deployment.

Usage:
    python scripts/test_api.py
    python scripts/test_api.py http://your-server
    python scripts/test_api.py http://your-server --token YOUR_JWT

Requirements: Python 3.6+, requests library
    pip install requests
"""

import sys
import json
import argparse
import requests
from requests.exceptions import ConnectionError, Timeout

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------

DEFAULT_URL = "http://localhost/CI3/index.php"
TIMEOUT = 10

GREEN = "\033[92m"
RED   = "\033[91m"
YELLOW = "\033[93m"
RESET = "\033[0m"

passed = 0
failed = 0
errors = []


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def log_pass(name):
    global passed
    passed += 1
    print(f"  {GREEN}[PASS]{RESET} {name}")


def log_fail(name, reason=""):
    global failed
    failed += 1
    errors.append(f"{name}: {reason}")
    print(f"  {RED}[FAIL]{RESET} {name}")
    if reason:
        print(f"         {reason}")


def log_skip(name, reason=""):
    print(f"  {YELLOW}[SKIP]{RESET} {name}" + (f" ({reason})" if reason else ""))


def get(base, path, token=None, headers=None):
    h = {"Accept": "application/json"}
    if token:
        h["Authorization"] = f"Bearer {token}"
    if headers:
        h.update(headers)
    return requests.get(base + path, headers=h, timeout=TIMEOUT)


def post(base, path, body=None, token=None):
    h = {"Accept": "application/json", "Content-Type": "application/json"}
    if token:
        h["Authorization"] = f"Bearer {token}"
    return requests.post(base + path, json=body or {}, headers=h, timeout=TIMEOUT)


def assert_status(name, resp, expected):
    if resp.status_code == expected:
        log_pass(f"{name} (HTTP {resp.status_code})")
        return True
    else:
        body = ""
        try:
            body = json.dumps(resp.json(), indent=2)[:300]
        except Exception:
            body = resp.text[:300]
        log_fail(name, f"expected HTTP {expected}, got {resp.status_code}\n         {body}")
        return False


def assert_json_key(name, resp, key):
    try:
        data = resp.json()
        if key in data:
            log_pass(f"{name} (key '{key}' present)")
            return True
        else:
            log_fail(name, f"key '{key}' not found in: {list(data.keys())}")
            return False
    except Exception as e:
        log_fail(name, f"response is not JSON: {e}")
        return False


def assert_header(name, resp, header):
    header_lower = header.lower()
    if header_lower in {k.lower() for k in resp.headers}:
        log_pass(f"{name} (header '{header}' present)")
        return True
    else:
        log_fail(name, f"header '{header}' not in response headers")
        return False


# ---------------------------------------------------------------------------
# Test Suites
# ---------------------------------------------------------------------------

def test_health(base):
    print(f"\n{YELLOW}--- Health Endpoint ---{RESET}")
    try:
        resp = get(base, "/api/health")
    except (ConnectionError, Timeout) as e:
        log_fail("GET /api/health - connection", str(e))
        return

    assert_status("GET /api/health returns 200", resp, 200)
    assert_json_key("Health has 'status' key", resp, "status")
    assert_json_key("Health has 'message' key", resp, "message")
    assert_json_key("Health has 'data' key", resp, "data")

    try:
        data = resp.json().get("data", {})
        if data.get("status") == "healthy":
            log_pass("Health data.status is 'healthy'")
        else:
            log_fail("Health data.status is 'healthy'", f"got: {data.get('status')}")

        if data.get("framework") == "CodeIgniter 3.1.13":
            log_pass("Health data.framework is 'CodeIgniter 3.1.13'")
        else:
            log_fail("Health data.framework", f"got: {data.get('framework')}")
    except Exception:
        pass

    assert_header("Health has CORS header", resp, "Access-Control-Allow-Origin")
    assert_header("Health has X-RateLimit-Limit", resp, "X-RateLimit-Limit")
    assert_header("Health has X-RateLimit-Remaining", resp, "X-RateLimit-Remaining")


def test_token(base):
    print(f"\n{YELLOW}--- Token Endpoint ---{RESET}")

    # Empty credentials
    try:
        resp = post(base, "/api/token", {"username": "", "password": ""})
        assert_status("POST /api/token rejects empty credentials (400)", resp, 400)
        try:
            if resp.json().get("error") == "validation_error":
                log_pass("Error code is 'validation_error'")
            else:
                log_fail("Error code", f"expected 'validation_error', got '{resp.json().get('error')}'")
        except Exception:
            pass
    except (ConnectionError, Timeout) as e:
        log_fail("POST /api/token empty credentials", str(e))

    # Missing fields
    try:
        resp = post(base, "/api/token", {})
        assert_status("POST /api/token rejects missing fields (400)", resp, 400)
    except (ConnectionError, Timeout) as e:
        log_fail("POST /api/token missing fields", str(e))

    # Valid credentials - return token for downstream tests
    try:
        resp = post(base, "/api/token", {"username": "testuser", "password": "testpass"})
        ok = assert_status("POST /api/token with valid credentials (200)", resp, 200)
        if ok:
            assert_json_key("Token response has 'data'", resp, "data")
            data = resp.json().get("data", {})
            token = data.get("access_token")
            if token:
                log_pass("access_token is present and non-empty")
            else:
                log_fail("access_token", "empty or missing")
            if data.get("token_type") == "Bearer":
                log_pass("token_type is 'Bearer'")
            else:
                log_fail("token_type", f"expected 'Bearer', got '{data.get('token_type')}'")
            return token
    except (ConnectionError, Timeout) as e:
        log_fail("POST /api/token valid credentials", str(e))

    return None


def test_protected(base, token):
    print(f"\n{YELLOW}--- Protected Endpoint ---{RESET}")

    # No token
    try:
        resp = get(base, "/api/protected")
        assert_status("GET /api/protected without token (401)", resp, 401)
        try:
            if resp.json().get("error") == "unauthorized":
                log_pass("Error code is 'unauthorized'")
            else:
                log_fail("Error code", f"expected 'unauthorized', got '{resp.json().get('error')}'")
        except Exception:
            pass
    except (ConnectionError, Timeout) as e:
        log_fail("GET /api/protected no token", str(e))

    # Invalid token
    try:
        resp = get(base, "/api/protected", token="invalid.token.here")
        assert_status("GET /api/protected with invalid token (401)", resp, 401)
        try:
            if resp.json().get("error") == "invalid_token":
                log_pass("Error code is 'invalid_token'")
            else:
                log_fail("Error code", f"expected 'invalid_token', got '{resp.json().get('error')}'")
        except Exception:
            pass
    except (ConnectionError, Timeout) as e:
        log_fail("GET /api/protected invalid token", str(e))

    # Valid token
    if token:
        try:
            resp = get(base, "/api/protected", token=token)
            ok = assert_status("GET /api/protected with valid token (200)", resp, 200)
            if ok:
                assert_json_key("Protected response has 'data'", resp, "data")
                try:
                    data = resp.json().get("data", {})
                    if "auth_data" in data:
                        log_pass("Protected data has 'auth_data'")
                    else:
                        log_fail("Protected data.auth_data", f"keys found: {list(data.keys())}")
                except Exception:
                    pass
        except (ConnectionError, Timeout) as e:
            log_fail("GET /api/protected valid token", str(e))
    else:
        log_skip("GET /api/protected with valid token", "no token available")


def test_rate_limiter(base):
    print(f"\n{YELLOW}--- Rate Limiter ---{RESET}")

    # First get the limit from the header
    try:
        resp = get(base, "/api/health")
    except (ConnectionError, Timeout) as e:
        log_fail("Rate limit header check", str(e))
        return

    limit_header = resp.headers.get("X-RateLimit-Limit") or resp.headers.get("x-ratelimit-limit")
    if not limit_header:
        log_fail("X-RateLimit-Limit header present", "header missing, cannot run rate limit tests")
        return

    limit = int(limit_header)
    log_pass(f"Rate limit is {limit} req/min (from X-RateLimit-Limit header)")

    # Check X-RateLimit-Remaining decrements
    try:
        r1 = get(base, "/api/health")
        r2 = get(base, "/api/health")
        rem1 = int(r1.headers.get("x-ratelimit-remaining", -1))
        rem2 = int(r2.headers.get("x-ratelimit-remaining", -1))
        if rem2 < rem1:
            log_pass(f"X-RateLimit-Remaining decrements ({rem1} -> {rem2})")
        else:
            log_fail("X-RateLimit-Remaining decrements", f"got {rem1} then {rem2}")
    except (ConnectionError, Timeout) as e:
        log_fail("Rate limit decrement check", str(e))
        return

    # Exhaust the limit then expect 429
    # Use a small limit for testing; skip if limit is very large
    if limit > 120:
        log_skip(
            f"Rate limit exhaustion test",
            f"limit is {limit} (too many requests for a test run)"
        )
        return

    print(f"  Sending {limit} requests to exhaust limit...")
    hit_429 = False
    last_status = None
    try:
        for i in range(limit + 5):
            r = get(base, "/api/health")
            last_status = r.status_code
            if r.status_code == 429:
                hit_429 = True
                retry_after = r.headers.get("Retry-After")
                log_pass(
                    f"Rate limiter returns 429 after {i + 1} requests"
                    + (f" (Retry-After: {retry_after}s)" if retry_after else "")
                )
                # Verify 429 response body
                try:
                    body = r.json()
                    if body.get("error") == "rate_limit_exceeded":
                        log_pass("429 body has error 'rate_limit_exceeded'")
                    else:
                        log_fail("429 body error key", f"got '{body.get('error')}'")
                except Exception:
                    log_fail("429 body is valid JSON", "could not parse response")
                break
    except (ConnectionError, Timeout) as e:
        log_fail("Rate limit exhaustion", str(e))
        return

    if not hit_429:
        log_fail(
            f"Rate limiter returns 429",
            f"sent {limit + 5} requests, last status was {last_status}"
        )


# ---------------------------------------------------------------------------
# Entry Point
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(description="CI3 API Test Script")
    parser.add_argument("base_url", nargs="?", default=DEFAULT_URL,
                        help=f"Base URL of the API (default: {DEFAULT_URL})")
    parser.add_argument("--token", default=None,
                        help="Skip token generation and use this JWT instead")
    args = parser.parse_args()

    base = args.base_url.rstrip("/")

    print("=" * 50)
    print(f" CI3 API Tests")
    print(f" Target: {base}")
    print("=" * 50)

    test_health(base)
    token = args.token or test_token(base)
    test_protected(base, token)
    test_rate_limiter(base)

    total = passed + failed
    print()
    print("=" * 50)
    print(f" Results: {passed}/{total} passed, {failed} failed")
    print("=" * 50)

    if errors:
        print("\nFailed tests:")
        for e in errors:
            print(f"  - {e}")
        print()
        sys.exit(1)

    print()
    sys.exit(0)


if __name__ == "__main__":
    main()
