# Automated Test Results

![Tests](https://img.shields.io/badge/tests-pending-lightgrey)

> Last updated: **Waiting for first CI run...**

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | - |
| Passed | - |
| Failed | - |
| Errors | - |
| Skipped | - |
| Duration | - |

---

> This file is automatically updated by the [GitHub Actions workflow](.github/workflows/tests.yml) on every push to `main`.
>
> To run tests locally:
> ```bash
> # Run all tests (unit + library tests only, no server needed)
> composer test
>
> # Run only API tests (requires running Apache)
> API_BASE_URL=http://localhost vendor/bin/phpunit --testsuite "API Tests"
>
> # Generate results markdown locally
> vendor/bin/phpunit --log-junit tests/results/junit-unit.xml
> php tests/generate_results.php > automated_tests.md
> ```
