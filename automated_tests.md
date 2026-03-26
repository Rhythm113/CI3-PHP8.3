# Automated Test Results

![Tests](https://img.shields.io/badge/tests-failing-red)

> Last updated: **2026-03-26 18:18:21 UTC**

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | 16 |
| Passed | 5 |
| Failed | 10 |
| Errors | 1 |
| Skipped | 0 |
| Duration | 0.036s |

---

## Test Suites

### [FAIL] Tests\Api\AuthApiTest

| Test | Status | Time |
|------|--------|------|
| test token endpoint returns token | [FAIL] | 0.009s |
| test token endpoint rejects empty credentials | [FAIL] | 0.004s |
| test token endpoint rejects missing fields | [FAIL] | 0.004s |
| test protected endpoint rejects no token | [FAIL] | 0.003s |
| test protected endpoint rejects invalid token | [FAIL] | 0.002s |
| test protected endpoint accepts valid token | [FAIL] | 0.002s |

<details><summary>Failure Details</summary>

**test_token_endpoint_returns_token**
```
Tests\Api\AuthApiTest::test_token_endpoint_returns_token
Failed asserting that 500 matches expected 200.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:117
```

**test_token_endpoint_rejects_empty_credentials**
```
Tests\Api\AuthApiTest::test_token_endpoint_rejects_empty_credentials
Failed asserting that 500 matches expected 400.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:131
```

**test_token_endpoint_rejects_missing_fields**
```
Tests\Api\AuthApiTest::test_token_endpoint_rejects_missing_fields
Failed asserting that 500 matches expected 400.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:139
```

**test_protected_endpoint_rejects_no_token**
```
Tests\Api\AuthApiTest::test_protected_endpoint_rejects_no_token
Failed asserting that 500 matches expected 401.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/AuthApiTest.php:150
```

**test_protected_endpoint_rejects_invalid_token**
```
Tests\Api\AuthApiTest::test_protected_endpoint_rejects_invalid_token
Failed asserting that 500 matches expected 401.

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
| test health endpoint returns 200 | [FAIL] | 0.002s |
| test health endpoint returns json | [FAIL] | 0.002s |
| test health endpoint contains correct data | [ERROR] | 0.002s |
| test health endpoint has cors headers | [FAIL] | 0.002s |
| test health endpoint has rate limit headers | [FAIL] | 0.002s |

<details><summary>Failure Details</summary>

**test_health_endpoint_returns_200**
```
Tests\Api\HealthApiTest::test_health_endpoint_returns_200
Health endpoint should return 200. Error: 
<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">

<h4>An uncaught Exception was encountered</h4>

<p>Type: Error</p>
<p>Message: Class "API_Controller" not found</p>
<p>Filename: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/application/controllers/Api.php</p>
<p>Line Number: 8</p>


	<p>Backtrace:</p>
	
		
	
		
			<p style="margin-left:10px">
			File: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/index.php<br />
			Line: 308<br />
			Function: require_once			</p>
		
	

</div>
Failed asserting that 500 matches expected 200.

/home/runner/work/CI3-PHP8.3/CI3-PHP8.3/tests/Api/HealthApiTest.php:125
```

**test_health_endpoint_returns_json**
```
Tests\Api\HealthApiTest::test_health_endpoint_returns_json
Response body should be valid JSON
Failed asserting that '\n
<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">\n
\n
<h4>An uncaught Exception was encountered</h4>\n
\n
<p>Type: Error</p>\n
<p>Message: Class "API_Controller" not found</p>\n
<p>Filename: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/application/controllers/Api.php</p>\n
<p>Line Number: 8</p>\n
\n
\n
	<p>Backtrace:</p>\n
	\n
		\n
	\n
		\n
			<p style="margin-left:10px">\n
			File: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/index.php<br />\n
			Line: 308<br />\n
			Function: require_once			</p>\n
		\n
	\n
\n
</div>' is of type array.

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

### [PASS] Tests\Libraries\JwtLibTest

| Test | Status | Time |
|------|--------|------|
| test can encode and decode token | [PASS] | 0.002s |
| test expired token throws exception | [PASS] | 0.000s |
| test wrong secret throws exception | [PASS] | 0.000s |
| test token has three parts | [PASS] | 0.000s |
| test payload data is preserved | [PASS] | 0.000s |

