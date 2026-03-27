# CI3 for PHP 8.3

> **Disclaimer**: This is **not an official CodeIgniter project**.
> It is an unofficial community port of CodeIgniter 3 patched for PHP 8.2 / 8.3 compatibility,
> maintained by **[Rhythm113](https://github.com/Rhythm113)**.
> It is not affiliated with, endorsed by, or supported by the CodeIgniter Foundation or EllisLab.
> The official CodeIgniter project can be found at [codeigniter.com](https://codeigniter.com).
>
> Issues and pull requests are welcome. Please open them on the
> [GitHub Issues](https://github.com/Rhythm113/CI3-PHP8.3/issues) page.

---

## What This Is

A maintained fork of CodeIgniter 3.1.13 with:

- PHP 8.2 and PHP 8.3 compatibility patches applied to the system core
- Fixes for removed functions (`mysqli_driver`, `get_magic_quotes_gpc`, `mbstring.internal_encoding`, etc.)
- `#[AllowDynamicProperties]` applied to all core classes that require it
- A built-in JWT auth system, rate limiter, Redis library, and cURL wrapper
- A Python migration script (`ci3_migrate.py`) for porting legacy CI3 apps with auto-patching

---

## Quick Start

1. Clone the repository into your web root:

```bash
git clone https://github.com/Rhythm113/CI3-PHP8.3.git CI3
```

2. Set the base URL in `application/config/config.php`:

```php
$config['base_url'] = 'http://localhost/CI3/';
```

3. Configure your database in `application/config/database.php`.

4. Visit `http://localhost/CI3/index.php` -- you should see the CI3 welcome page.

See [USERGUIDE.md](USERGUIDE.md) for detailed setup, database configuration, JWT auth,
rate limiting, Redis, and API building guides.

---

## Migration Script

`ci3_migrate.py` migrates controllers, models, helpers, libraries, views, and configs
from an existing CI3 project into this one -- and auto-patches deprecated PHP 8.x patterns
in the process.

### Usage

```powershell
# Preview what would be migrated (no files changed)
python ci3_migrate.py C:\path\to\old-project --target D:\path\to\this-project --dry-run

# Live migration (skip files that already exist)
python ci3_migrate.py C:\path\to\old-project --target D:\path\to\this-project

# Overwrite all existing files
python ci3_migrate.py C:\path\to\old-project --target D:\path\to\this-project --overwrite
```

### What it auto-patches

The script applies the following fixes to PHP files during migration:

- `get_magic_quotes_gpc()` -- `function_exists()` guard added
- `ini_set('mbstring.internal_encoding', ...)` -- replaced with `mb_internal_encoding(...)`
- `ini_set('iconv.internal_encoding', ...)` -- replaced with `iconv_set_encoding()` with PHP 8 version guard
- `ini_set('session.hash_function', ...)` -- replaced with explanatory comment
- `utf8_encode(...)` -- replaced with `mb_convert_encoding(...)`
- `utf8_decode(...)` -- replaced with `mb_convert_encoding(..., 'ISO-8859-1', 'UTF-8')`
- `FILTER_SANITIZE_STRING` -- replaced with `FILTER_DEFAULT`
- `new mysqli_driver()` -- replaced with `mysqli_report(MYSQLI_REPORT_OFF)`
- `create_function(...)`, `each(...)`, `ereg*()`, `mysql_*()` -- TODO comment added

A `migration_report.md` is generated in the target directory after every run.


## Reporting Issues

Found a bug or a PHP 8.x compatibility problem?

- Open an issue: [github.com/Rhythm113/CI3-PHP8.3/issues](https://github.com/Rhythm113/CI3-PHP8.3/issues)
- Please include your PHP version, error message, and the file/line where it occurs.

---

## License

CodeIgniter 3 is released under the [MIT License](license.txt).
Patches in this port are also MIT licensed.
This port is provided as-is with no warranty.
