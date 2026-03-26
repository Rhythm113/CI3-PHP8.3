# Automated Test Results

![Tests](https://img.shields.io/badge/tests-failing-red)

> Last updated: **2026-03-26 18:57:09 UTC**

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | 16 |
| Passed | 5 |
| Failed | 10 |
| Errors | 1 |
| Skipped | 0 |
| Duration | 0.043s |

---

## Test Suites

### [FAIL] Tests\Api\AuthApiTest

| Test | Status | Time |
|------|--------|------|
| test token endpoint returns token | [FAIL] | 0.011s |
| test token endpoint rejects empty credentials | [FAIL] | 0.004s |
| test token endpoint rejects missing fields | [FAIL] | 0.004s |
| test protected endpoint rejects no token | [FAIL] | 0.004s |
| test protected endpoint rejects invalid token | [FAIL] | 0.003s |
| test protected endpoint accepts valid token | [FAIL] | 0.003s |

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
| test health endpoint returns 200 | [FAIL] | 0.003s |
| test health endpoint returns json | [FAIL] | 0.003s |
| test health endpoint contains correct data | [ERROR] | 0.002s |
| test health endpoint has cors headers | [FAIL] | 0.003s |
| test health endpoint has rate limit headers | [FAIL] | 0.002s |

<details><summary>Failure Details</summary>

**test_health_endpoint_returns_200**
```
Tests\Api\HealthApiTest::test_health_endpoint_returns_200
Health endpoint should return 200. Error: 
<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">

<h4>A PHP Error was encountered</h4>

<p>Severity: Warning</p>
<p>Message:  mysqli::real_connect(): (HY000/2002): No such file or directory</p>
<p>Filename: mysqli/mysqli_driver.php</p>
<p>Line Number: 212</p>


	<p>Backtrace:</p>
	
		
	
		
	
		
	
		
	
		
	
		
	
		
	
		
	
		
	
		
	
		
			<p style="margin-left:10px">
			File: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/application/core/API_Controller.php<br />
			Line: 33<br />
			Function: __construct			</p>

		
	
		
	
		
			<p style="margin-left:10px">
			File: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/index.php<br />
			Line: 308<br />
			Function: require_once			</p>

		
	

</div><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Database Error</title>
<style type="text/css">

::selection { background-color: #E13300; color: white; }
::-moz-selection { background-color: #E13300; color: white; }

body {
	background-color: #fff;
	margin: 40px;
	font: 13px/20px normal Helvetica, Arial, sans-serif;
	color: #4F5155;
}

a {
	color: #003399;
	background-color: transparent;
	font-weight: normal;
}

h1 {
	color: #444;
	background-color: transparent;
	border-bottom: 1px solid #D0D0D0;
	font-size: 19px;
	font-weight: normal;
	margin: 0 0 14px 0;
	padding: 14px 15px 10px 15px;
}

code {
	font-family: Consolas, Monaco, Courier New, Courier, monospace;
	font-size: 12px;
	background-color: #f9f9f9;
	border: 1px solid #D0D0D0;
	color: #002166;
	display: block;
	margin: 14px 0 14px 0;
	padding: 12px 10px 12px 10px;
}

#container {
	margin: 10px;
	border: 1px solid #D0D0D0;
	box-shadow: 0 0 8px #D0D0D0;
}

p {
	margin: 12px 15px 12px 15px;
}
</style>
</head>
<body>
	<div id="container">
		<h1>A Database Error Occurred</h1>
		<p>Unable to connect to your database server using the provided settings.</p><p>Filename: core/API_Controller.php</p><p>Line Number: 33</p>	</div>
</body>
</html>
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
<h4>A PHP Error was encountered</h4>\n
\n
<p>Severity: Warning</p>\n
<p>Message:  mysqli::real_connect(): (HY000/2002): No such file or directory</p>\n
<p>Filename: mysqli/mysqli_driver.php</p>\n
<p>Line Number: 212</p>\n
\n
\n
	<p>Backtrace:</p>\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
	\n
		\n
			<p style="margin-left:10px">\n
			File: /home/runner/work/CI3-PHP8.3/CI3-PHP8.3/application/core/API_Controller.php<br />\n
			Line: 33<br />\n
			Function: __construct			</p>\n
\n
		\n
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
\n
</div><!DOCTYPE html>\n
<html lang="en">\n
<head>\n
<meta charset="utf-8">\n
<title>Database Error</title>\n
<style type="text/css">\n
\n
::selection { background-color: #E13300; color: white; }\n
::-moz-selection { background-color: #E13300; color: white; }\n
\n
body {\n
	background-color: #fff;\n
	margin: 40px;\n
	font: 13px/20px normal Helvetica, Arial, sans-serif;\n
	color: #4F5155;\n
}\n
\n
a {\n
	color: #003399;\n
	background-color: transparent;\n
	font-weight: normal;\n
}\n
\n
h1 {\n
	color: #444;\n
	background-color: transparent;\n
	border-bottom: 1px solid #D0D0D0;\n
	font-size: 19px;\n
	font-weight: normal;\n
	margin: 0 0 14px 0;\n
	padding: 14px 15px 10px 15px;\n
}\n
\n
code {\n
	font-family: Consolas, Monaco, Courier New, Courier, monospace;\n
	font-size: 12px;\n
	background-color: #f9f9f9;\n
	border: 1px solid #D0D0D0;\n
	color: #002166;\n
	display: block;\n
	margin: 14px 0 14px 0;\n
	padding: 12px 10px 12px 10px;\n
}\n
\n
#container {\n
	margin: 10px;\n
	border: 1px solid #D0D0D0;\n
	box-shadow: 0 0 8px #D0D0D0;\n
}\n
\n
p {\n
	margin: 12px 15px 12px 15px;\n
}\n
</style>\n
</head>\n
<body>\n
	<div id="container">\n
		<h1>A Database Error Occurred</h1>\n
		<p>Unable to connect to your database server using the provided settings.</p><p>Filename: core/API_Controller.php</p><p>Line Number: 33</p>	</div>\n
</body>\n
</html>' is of type array.

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

