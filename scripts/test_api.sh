#!/usr/bin/env bash
# =============================================================================
# test_api.sh  -  Manual API test script for CI3
#
# Run this after deploying to a live server to verify all API endpoints.
#
# Usage:
#   bash scripts/test_api.sh                      # defaults to http://localhost
#   bash scripts/test_api.sh http://your-server   # custom base URL
#   bash scripts/test_api.sh http://srv JWT_TOKEN # skip token generation
#
# Requirements: curl, python3 (for JSON pretty-print, optional)
# =============================================================================

BASE_URL="${1:-http://localhost}"
EXISTING_TOKEN="${2:-}"

PASS=0
FAIL=0
ERRORS=()

# Colors (ASCII-safe fallback if term doesn't support them)
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

http_request() {
    local method="$1"
    local path="$2"
    local body="$3"
    local auth_header="$4"

    local args=(-s -w "\n%{http_code}" -X "$method" "$BASE_URL$path")
    args+=(-H "Accept: application/json" -H "Content-Type: application/json")

    if [ -n "$auth_header" ]; then
        args+=(-H "Authorization: Bearer $auth_header")
    fi

    if [ -n "$body" ]; then
        args+=(-d "$body")
    fi

    curl "${args[@]}"
}

assert_status() {
    local test_name="$1"
    local expected="$2"
    local actual="$3"
    local body="$4"

    if [ "$actual" -eq "$expected" ]; then
        echo -e "${GREEN}[PASS]${NC} $test_name (HTTP $actual)"
        PASS=$((PASS + 1))
    else
        echo -e "${RED}[FAIL]${NC} $test_name"
        echo "       Expected HTTP $expected, got HTTP $actual"
        if [ -n "$body" ]; then
            echo "       Response: $(echo "$body" | head -c 300)"
        fi
        FAIL=$((FAIL + 1))
        ERRORS+=("$test_name: expected $expected got $actual")
    fi
}

assert_json_key() {
    local test_name="$1"
    local key="$2"
    local body="$3"

    if echo "$body" | grep -q "\"$key\""; then
        echo -e "${GREEN}[PASS]${NC} $test_name (key '$key' present)"
        PASS=$((PASS + 1))
    else
        echo -e "${RED}[FAIL]${NC} $test_name (key '$key' missing)"
        FAIL=$((FAIL + 1))
        ERRORS+=("$test_name: key '$key' not found in response")
    fi
}

assert_header() {
    local test_name="$1"
    local header="$2"
    local headers="$3"

    if echo "$headers" | grep -qi "$header"; then
        echo -e "${GREEN}[PASS]${NC} $test_name (header '$header' present)"
        PASS=$((PASS + 1))
    else
        echo -e "${RED}[FAIL]${NC} $test_name (header '$header' missing)"
        FAIL=$((FAIL + 1))
        ERRORS+=("$test_name: header '$header' not found")
    fi
}

# ---------------------------------------------------------------------------
# Tests
# ---------------------------------------------------------------------------

echo ""
echo "============================================="
echo " CI3 API Test Suite"
echo " Target: $BASE_URL"
echo "============================================="
echo ""

# -- Health Endpoint --
echo "${YELLOW}--- Health Endpoint ---${NC}"

RESPONSE=$(http_request GET /api/health "" "")
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

assert_status "GET /api/health returns 200" 200 "$STATUS" "$BODY"
assert_json_key "Health response has 'status' key" "status" "$BODY"
assert_json_key "Health response has 'message' key" "message" "$BODY"
assert_json_key "Health response has 'data' key" "data" "$BODY"

# Check CORS and rate-limit headers
HEADERS=$(curl -s -I "$BASE_URL/api/health")
assert_header "Health has CORS header" "Access-Control-Allow-Origin" "$HEADERS"
assert_header "Health has X-RateLimit-Limit" "X-RateLimit-Limit" "$HEADERS"
assert_header "Health has X-RateLimit-Remaining" "X-RateLimit-Remaining" "$HEADERS"

echo ""

# -- Token Endpoint --
echo "${YELLOW}--- Token Endpoint ---${NC}"

# Reject empty credentials
RESPONSE=$(http_request POST /api/token '{"username":"","password":""}' "")
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
assert_status "POST /api/token rejects empty credentials (400)" 400 "$STATUS" "$BODY"

# Reject missing fields
RESPONSE=$(http_request POST /api/token '{}' "")
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
assert_status "POST /api/token rejects missing fields (400)" 400 "$STATUS" "$BODY"

# Valid credentials
if [ -n "$EXISTING_TOKEN" ]; then
    TOKEN="$EXISTING_TOKEN"
    echo -e "${YELLOW}[SKIP]${NC} Token generation (using provided token)"
else
    RESPONSE=$(http_request POST /api/token '{"username":"testuser","password":"testpass"}' "")
    STATUS=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    assert_status "POST /api/token with valid credentials (200)" 200 "$STATUS" "$BODY"
    assert_json_key "Token response has 'access_token'" "access_token" "$BODY"
    assert_json_key "Token response has 'refresh_token'" "refresh_token" "$BODY"
    TOKEN=$(echo "$BODY" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
fi

echo ""

# -- Protected Endpoint --
echo "${YELLOW}--- Protected Endpoint ---${NC}"

# No token
RESPONSE=$(http_request GET /api/protected "" "")
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
assert_status "GET /api/protected without token (401)" 401 "$STATUS" "$BODY"

# Invalid token
RESPONSE=$(http_request GET /api/protected "" "invalid.token.here")
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
assert_status "GET /api/protected with invalid token (401)" 401 "$STATUS" "$BODY"

# Valid token
if [ -n "$TOKEN" ]; then
    RESPONSE=$(http_request GET /api/protected "" "$TOKEN")
    STATUS=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    assert_status "GET /api/protected with valid token (200)" 200 "$STATUS" "$BODY"
    assert_json_key "Protected response has 'auth_data'" "auth_data" "$BODY"
else
    echo -e "${YELLOW}[SKIP]${NC} Authenticated protected endpoint test (no token generated)"
fi

echo ""

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

TOTAL=$((PASS + FAIL))
echo "============================================="
echo " Results: $PASS/$TOTAL passed, $FAIL failed"
echo "============================================="

if [ ${#ERRORS[@]} -gt 0 ]; then
    echo ""
    echo "Failed tests:"
    for e in "${ERRORS[@]}"; do
        echo "  - $e"
    done
    echo ""
    exit 1
fi

echo ""
exit 0
