# Automated Test Results

![Tests](https://img.shields.io/badge/tests-failing-red)

> Last updated: **2026-03-26 18:02:18 UTC**

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | 16 |
| Passed | 5 |
| Failed | 10 |
| Errors | 1 |
| Skipped | 0 |
| Duration | 0.020s |

---

## Test Suites

### [FAIL] Tests\Api\AuthApiTest

| Test | Status | Time |
|------|--------|------|
| test token endpoint returns token | [FAIL] | 0.007s |
| test token endpoint rejects empty credentials | [FAIL] | 0.001s |
| test token endpoint rejects missing fields | [FAIL] | 0.001s |
| test protected endpoint rejects no token | [FAIL] | 0.001s |
| test protected endpoint rejects invalid token | [FAIL] | 0.001s |
| test protected endpoint accepts valid token | [FAIL] | 0.001s |

<details><summary>Failure Details</summary>

**test_token_endpoint_returns_token**
```
Tests\Api\AuthApiTest::test_token_endpoint_returns_token
Failed asserting that 403 matches expected 200.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:117
```

**test_token_endpoint_rejects_empty_credentials**
```
Tests\Api\AuthApiTest::test_token_endpoint_rejects_empty_credentials
Failed asserting that 403 matches expected 400.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:131
```

**test_token_endpoint_rejects_missing_fields**
```
Tests\Api\AuthApiTest::test_token_endpoint_rejects_missing_fields
Failed asserting that 403 matches expected 400.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:139
```

**test_protected_endpoint_rejects_no_token**
```
Tests\Api\AuthApiTest::test_protected_endpoint_rejects_no_token
Failed asserting that 403 matches expected 401.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:150
```

**test_protected_endpoint_rejects_invalid_token**
```
Tests\Api\AuthApiTest::test_protected_endpoint_rejects_invalid_token
Failed asserting that 403 matches expected 401.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:160
```

**test_protected_endpoint_accepts_valid_token**
```
Tests\Api\AuthApiTest::test_protected_endpoint_accepts_valid_token
Token generation should succeed
Failed asserting that null is not null.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:167
```

</details>

### [FAIL] Tests\Api\HealthApiTest

| Test | Status | Time |
|------|--------|------|
| test health endpoint returns 200 | [FAIL] | 0.001s |
| test health endpoint returns json | [FAIL] | 0.001s |
| test health endpoint contains correct data | [ERROR] | 0.001s |
| test health endpoint has cors headers | [FAIL] | 0.001s |
| test health endpoint has rate limit headers | [FAIL] | 0.001s |

<details><summary>Failure Details</summary>

**test_health_endpoint_returns_200**
```
Tests\Api\HealthApiTest::test_health_endpoint_returns_200
Health endpoint should return 200. Error: <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>403 Forbidden</title>
</head><body>
<h1>Forbidden</h1>
<p>You don't have permission to access this resource.</p>
<hr>
<address>Apache/2.4.58 (Ubuntu) Server at localhost Port 80</address>
</body></html>

Failed asserting that 403 matches expected 200.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/HealthApiTest.php:125
```

**test_health_endpoint_returns_json**
```
Tests\Api\HealthApiTest::test_health_endpoint_returns_json
Response body should be valid JSON
Failed asserting that '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">\n
<html><head>\n
<title>403 Forbidden</title>\n
</head><body>\n
<h1>Forbidden</h1>\n
<p>You don't have permission to access this resource.</p>\n
<hr>\n
<address>Apache/2.4.58 (Ubuntu) Server at localhost Port 80</address>\n
</body></html>\n
' is of type array.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/HealthApiTest.php:133
```

**test_health_endpoint_contains_correct_data**
```
Tests\Api\HealthApiTest::test_health_endpoint_contains_correct_data
TypeError: Cannot access offset of type string on string

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/HealthApiTest.php:142
```

**test_health_endpoint_has_cors_headers**
```
Tests\Api\HealthApiTest::test_health_endpoint_has_cors_headers
CORS header Access-Control-Allow-Origin should be present
Failed asserting that an array has the key 'access-control-allow-origin'.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/HealthApiTest.php:153
```

**test_health_endpoint_has_rate_limit_headers**
```
Tests\Api\HealthApiTest::test_health_endpoint_has_rate_limit_headers
Rate limit header X-RateLimit-Limit should be present
Failed asserting that an array has the key 'x-ratelimit-limit'.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/HealthApiTest.php:161
```

</details>


### [FAIL] Tests\Libraries\JwtLibTest

| Test | Status | Time |
|------|--------|------|
| test can encode and decode token | [ERROR] | 0.005s |
| test expired token throws exception | [ERROR] | 0.000s |
| test wrong secret throws exception | [ERROR] | 0.000s |
| test token has three parts | [ERROR] | 0.000s |
| test payload data is preserved | [ERROR] | 0.000s |

<details><summary>Failure Details</summary>

**test_can_encode_and_decode_token**
```
Tests\Libraries\JwtLibTest::test_can_encode_and_decode_token
DomainException: Provided key is too short

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:709
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:264
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:232
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Libraries/JwtLibTest.php:51
```

**test_expired_token_throws_exception**
```
Tests\Libraries\JwtLibTest::test_expired_token_throws_exception
DomainException: Provided key is too short

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:709
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:264
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:232
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Libraries/JwtLibTest.php:68
```

**test_wrong_secret_throws_exception**
```
Tests\Libraries\JwtLibTest::test_wrong_secret_throws_exception
DomainException: Provided key is too short

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:709
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:264
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:232
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Libraries/JwtLibTest.php:83
```

**test_token_has_three_parts**
```
Tests\Libraries\JwtLibTest::test_token_has_three_parts
DomainException: Provided key is too short

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:709
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:264
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:232
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Libraries/JwtLibTest.php:98
```

**test_payload_data_is_preserved**
```
Tests\Libraries\JwtLibTest::test_payload_data_is_preserved
DomainException: Provided key is too short

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:709
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:264
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/vendor/firebase/php-jwt/src/JWT.php:232
/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Libraries/JwtLibTest.php:120
```

</details>
=======
### [PASS] Tests\Libraries\JwtLibTest

| Test | Status | Time |
|------|--------|------|
| test can encode and decode token | [PASS] | 0.002s |
| test expired token throws exception | [PASS] | 0.000s |
| test wrong secret throws exception | [PASS] | 0.000s |
| test token has three parts | [PASS] | 0.000s |
| test payload data is preserved | [PASS] | 0.000s |


