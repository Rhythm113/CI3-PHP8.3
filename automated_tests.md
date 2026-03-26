# Automated Tests

This document describes the automated testing and security-check pipeline for
the CI3-PHP8.3 project. Every commit pushed to any branch (and every pull
request) automatically triggers the workflow defined in
`.github/workflows/ci.yml`.

---

## Table of Contents

1. [Workflow Overview](#workflow-overview)
2. [Jobs](#jobs)
   - [PHP Syntax Lint](#1-php-syntax-lint)
   - [Security Audit](#2-security-audit)
   - [PHPUnit Tests](#3-phpunit-tests)
3. [Test Files](#test-files)
4. [Running Tests Locally](#running-tests-locally)
5. [Adding New Tests](#adding-new-tests)
6. [Interpreting Results](#interpreting-results)

---

## Workflow Overview

| Trigger         | Description                                      |
|-----------------|--------------------------------------------------|
| `push`          | Any push to any branch                           |
| `pull_request`  | Any PR opened, updated, or re-opened             |

The pipeline runs three independent jobs in parallel:

```
push / pull_request
        |
        +---> lint     (PHP syntax check)
        |
        +---> security (Composer audit)
        |
        +---> tests    (PHPUnit)
```

All three jobs must pass for the commit to be considered clean.

---

## Jobs

### 1. PHP Syntax Lint

**Job name:** `PHP Syntax Lint`

Scans every `.php` file under `application/` and `system/` with `php -l` to
catch parse errors before any code is executed.

- Fast: no dependencies need to be installed.
- Fails immediately on the first syntax error found.

### 2. Security Audit

**Job name:** `Security Audit`

Runs `composer audit` after a fresh `composer install`. This command queries
the [Packagist security-advisories database](https://packagist.org/security-advisories/)
and fails the job if any installed package has a known vulnerability.

Dependencies checked:

| Package             | Minimum safe version |
|---------------------|----------------------|
| `firebase/php-jwt`  | `^7.0`               |
| `phpunit/phpunit`   | `^11.5.50`           |

### 3. PHPUnit Tests

**Job name:** `PHPUnit Tests`

Runs the full PHPUnit test suite (see [Test Files](#test-files) below).

PHPUnit is configured via `phpunit.xml` at the project root.

---

## Test Files

All test files live in the `tests/` directory.

| File                         | What it tests                                               |
|------------------------------|-------------------------------------------------------------|
| `tests/bootstrap.php`        | Bootstraps constants (`BASEPATH`, `APPPATH`, `FCPATH`) and the Composer autoloader so tests can reference framework files without the full CI bootstrap. |
| `tests/PhpSyntaxTest.php`    | Uses `php -l` to verify that every `.php` file under `application/` and `system/` parses without errors. |
| `tests/FrameworkStructureTest.php` | Asserts that all required directories and files exist, and that `composer.json` is valid JSON with the expected keys. |
| `tests/JwtHelperTest.php`    | Exercises the `firebase/php-jwt` encode/decode workflow used by `Jwt_lib`: correct claims, signature validation, expiry handling, and the inline base64-decode helper. |

---

## Running Tests Locally

**Prerequisites:** PHP 8.3, Composer 2.

```bash
# 1. Install dependencies (including dev packages)
composer install

# 2. Run the full test suite
./vendor/bin/phpunit --no-coverage

# 3. Run a single test file
./vendor/bin/phpunit --no-coverage tests/JwtHelperTest.php

# 4. Run the PHP syntax lint manually
find application system -name "*.php" | xargs -n1 php -l

# 5. Run the security audit manually
composer audit
```

---

## Adding New Tests

1. Create a new file in `tests/` following the naming convention
   `<Subject>Test.php`.
2. Declare the namespace `Tests` at the top of the file.
3. Extend `PHPUnit\Framework\TestCase`.
4. Use the `#[DataProvider('methodName')]` attribute (not the `@dataProvider`
   doc-comment) for data-driven tests.
5. Run `./vendor/bin/phpunit --no-coverage` locally to confirm the new tests
   pass before pushing.

Example skeleton:

```php
<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }
}
```

---

## Interpreting Results

After each push you can view the workflow results in the
**Actions** tab of the GitHub repository.

| Status  | Meaning                                                         |
|---------|-----------------------------------------------------------------|
| Passed  | All syntax, security, and unit tests are green.                 |
| Failed  | At least one job failed. Click the job to read the error log.   |
| Skipped | Job was not triggered (uncommon with the current configuration). |

A failed **Security Audit** means a dependency has a known vulnerability.
Update the affected package with `composer update <package>` and push again.

A failed **PHPUnit Tests** job will print the failing test name, the assertion
that failed, and a stack trace to help you locate the problem quickly.
