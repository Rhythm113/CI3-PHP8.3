# Release Notes

## v1.0.0 -- 2026-03-28

First public release of the CI3-PHP8.3 community port.
Maintained by [Rhythm113](https://github.com/Rhythm113).

> This is not an official CodeIgniter release.
> See [README.md](README.md) for the full disclaimer.

---

### PHP 8.x Compatibility Patches

The following breaking changes from PHP 8.0-8.3 were patched in the CI3 system core:

**mysqli driver**
- Replaced removed `new mysqli_driver()` instantiation with `mysqli_report(MYSQLI_REPORT_OFF)` in `system/database/drivers/mysqli/mysqli_driver.php`

**Encoding functions**
- Replaced `ini_set('mbstring.internal_encoding', ...)` with `mb_internal_encoding()` in `system/core/CodeIgniter.php`
- Replaced `ini_set('iconv.internal_encoding', ...)` with a PHP 8.0-version-guarded `iconv_set_encoding()` call

**Removed functions**
- Added `function_exists('get_magic_quotes_gpc')` guard in `system/core/Input.php` and `system/libraries/Email.php` -- this function was removed in PHP 8.0
- All `mbstring.func_overload` usages guarded with `! is_php('8.0')` in:
  - `system/core/Log.php`
  - `system/core/Output.php`
  - `system/libraries/Email.php`
  - `system/libraries/Zip.php`
  - `system/libraries/Session/drivers/Session_files_driver.php`

**Dynamic properties**
- Added `#[AllowDynamicProperties]` to all core classes that assign dynamic properties, suppressing PHP 8.2+ deprecation warnings

**Apache / php.ini**
- Corrected `httpd-xampp.conf` to use absolute paths for PHP extension directories, fixing failure to load `php_mysqli.dll` on XAMPP

---

### New Features

**JWT Authentication** (`application/core/API_Controller.php`, `application/libraries/Jwt_lib.php`)
- HMAC-SHA256 token generation and validation
- Access token + refresh token support
- Per-controller `$require_auth = TRUE` flag

**Rate Limiting** (`application/libraries/Rate_limiter.php`)
- File-based and Redis-backed rate limiting
- Configurable via `application/config/rate_limit.php`
- Standard `X-RateLimit-*` response headers

**Redis Library** (`application/libraries/Redis_lib.php`)
- Wraps phpredis for direct key-value access
- Used for rate limiting and CI3 session driver

**cURL Library** (`application/libraries/Curl_lib.php`)
- Simple GET, POST JSON, and custom-header wrappers

---

### Tooling

**`scripts/ci3_migrate.py`** -- Migration script
- Detects CI version in source and target projects
- Migrates `controllers`, `models`, `helpers`, `libraries`, `views`, `core`, `hooks`, `third_party`, `language`, `config`
- Merges config files (appends new keys, reports conflicts)
- Auto-patches 12 deprecated PHP 8.x patterns in copied files
- Runs `php -l` syntax check on all target PHP files
- Writes `migration_report.md` to the target directory

**`scripts/ci3_compat_test.py`** -- Automated compatibility test
- Scans system/ and application/ for PHP 8.x breaking patterns
- Runs `php -l` on all PHP files
- Exit code 0 = pass, 1 = fail (CI pipeline compatible)
- Optional Markdown report via `--report`

---

### Auto-Patches Applied by Migration Script

| Pattern | Fix |
|---|---|
| `get_magic_quotes_gpc()` | `function_exists()` guard |
| `ini_set('mbstring.internal_encoding', ...)` | `mb_internal_encoding(...)` |
| `ini_set('iconv.internal_encoding', ...)` | `iconv_set_encoding()` with PHP 8 guard |
| `ini_set('session.hash_function', ...)` | Comment replacement |
| `utf8_encode()` / `utf8_decode()` | `mb_convert_encoding()` |
| `FILTER_SANITIZE_STRING` | `FILTER_DEFAULT` |
| `new mysqli_driver()` | `mysqli_report(MYSQLI_REPORT_OFF)` |
| `create_function()`, `each()`, `ereg*()`, `mysql_*()` | TODO comment marker |

---

### Test Results

258 PHPUnit tests passing. See [automated_tests.md](automated_tests.md) for the full report.

Test suites covered:
- JWT library encode/decode
- JWT helper functions
- Framework directory and file structure
- PHP syntax check on all system and application files

---

### Known Limitations

- `mysqli::ping()` is deprecated in PHP 8.4. If upgrading beyond 8.3, replace it with a `SELECT 1` query.
- Application-level classes using dynamic properties must be manually annotated with `#[AllowDynamicProperties]` if the migration script does not detect them automatically.
- `create_function()`, `each()`, `ereg*()`, and `mysql_*()` calls are marked with TODO comments but require manual rewriting -- they cannot be safely auto-replaced.

---

### Upgrade Notes

If upgrading from a raw CI3 1.3.x project:

1. Run the migration script in dry-run mode first:
   ```powershell
   python scripts/ci3_migrate.py C:\old-project --dry-run
   ```
2. Review `migration_report.md` for any remaining TODO items.
3. Run the compatibility test to confirm no blocking issues:
   ```powershell
   python scripts/ci3_compat_test.py --app-only
   ```
4. Test all routes and database queries manually after migrating.
