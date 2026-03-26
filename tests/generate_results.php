<?php
/**
 * Parse PHPUnit JUnit XML results and generate automated_tests.md
 *
 * Called by the GitHub Actions workflow after tests run.
 * Reads JUnit XML files from tests/results/ and outputs markdown.
 * ASCII only - no emoji or non-ASCII characters.
 */

$results_dir = __DIR__ . '/results';
$xml_files = glob($results_dir . '/junit*.xml');

$now = date('Y-m-d H:i:s T');
$total_tests = 0;
$total_passed = 0;
$total_failed = 0;
$total_errors = 0;
$total_skipped = 0;
$total_time = 0.0;
$suites = [];

foreach ($xml_files as $file) {
    if (!file_exists($file)) continue;
    $xml = @simplexml_load_file($file);
    if (!$xml) continue;

    $testsuites = $xml->getName() === 'testsuites' ? $xml->testsuite : [$xml];

    foreach ($testsuites as $suite) {
        parseSuite($suite, $suites, $total_tests, $total_passed, $total_failed, $total_errors, $total_skipped, $total_time);
    }
}

function parseSuite($suite, &$suites, &$total_tests, &$total_passed, &$total_failed, &$total_errors, &$total_skipped, &$total_time) {
    $attrs = $suite->attributes();
    $tests    = (int)($attrs['tests']    ?? 0);
    $failures = (int)($attrs['failures'] ?? 0);
    $errors   = (int)($attrs['errors']   ?? 0);
    $skipped  = (int)($attrs['skipped']  ?? 0);
    $time     = (float)($attrs['time']   ?? 0);

    if (isset($suite->testsuite)) {
        foreach ($suite->testsuite as $child) {
            parseSuite($child, $suites, $total_tests, $total_passed, $total_failed, $total_errors, $total_skipped, $total_time);
        }
        return;
    }

    if (!isset($suite->testcase)) return;

    $name = (string)($attrs['name'] ?? 'Unknown');
    $cases = [];

    foreach ($suite->testcase as $tc) {
        $tc_attrs = $tc->attributes();
        $tc_name  = (string)($tc_attrs['name'] ?? 'unknown');
        $tc_time  = (float)($tc_attrs['time']  ?? 0);
        $status   = 'passed';
        $message  = '';

        if (isset($tc->failure)) {
            $status  = 'failed';
            $message = (string)$tc->failure;
        } elseif (isset($tc->error)) {
            $status  = 'error';
            $message = (string)$tc->error;
        } elseif (isset($tc->skipped)) {
            $status = 'skipped';
        }

        $cases[] = ['name' => $tc_name, 'time' => $tc_time, 'status' => $status, 'message' => $message];
    }

    $passed = count(array_filter($cases, fn($c) => $c['status'] === 'passed'));

    $suites[] = [
        'name'     => $name,
        'tests'    => $tests,
        'passed'   => $passed,
        'failures' => $failures,
        'errors'   => $errors,
        'skipped'  => $skipped,
        'time'     => $time,
        'cases'    => $cases
    ];

    $total_tests   += $tests;
    $total_passed  += $passed;
    $total_failed  += $failures;
    $total_errors  += $errors;
    $total_skipped += $skipped;
    $total_time    += $time;
}

// Output Markdown (ASCII only)

$all_pass = ($total_failed === 0 && $total_errors === 0 && $total_tests > 0);
$badge = $all_pass
    ? '![Tests](https://img.shields.io/badge/tests-passing-brightgreen)'
    : '![Tests](https://img.shields.io/badge/tests-failing-red)';

echo "# Automated Test Results\n\n";
echo "$badge\n\n";
echo "> Last updated: **$now**\n\n";
echo "## Summary\n\n";
echo "| Metric | Value |\n";
echo "|--------|-------|\n";
echo "| Total Tests | $total_tests |\n";
echo "| Passed | $total_passed |\n";
echo "| Failed | $total_failed |\n";
echo "| Errors | $total_errors |\n";
echo "| Skipped | $total_skipped |\n";
echo sprintf("| Duration | %.3fs |\n", $total_time);
echo "\n---\n\n";
echo "## Test Suites\n\n";

foreach ($suites as $suite) {
    $suite_status = ($suite['failures'] === 0 && $suite['errors'] === 0) ? '[PASS]' : '[FAIL]';
    echo "### $suite_status {$suite['name']}\n\n";
    echo "| Test | Status | Time |\n";
    echo "|------|--------|------|\n";

    foreach ($suite['cases'] as $case) {
        $icon = match($case['status']) {
            'passed'  => '[PASS]',
            'failed'  => '[FAIL]',
            'error'   => '[ERROR]',
            'skipped' => '[SKIP]',
            default   => '[?]'
        };
        $time_str  = sprintf('%.3fs', $case['time']);
        $test_name = str_replace('_', ' ', $case['name']);
        echo "| $test_name | $icon | $time_str |\n";
    }

    $failures = array_filter($suite['cases'], fn($c) => in_array($c['status'], ['failed', 'error']));
    if (!empty($failures)) {
        echo "\n<details><summary>Failure Details</summary>\n\n";
        foreach ($failures as $f) {
            echo "**{$f['name']}**\n```\n" . trim($f['message']) . "\n```\n\n";
        }
        echo "</details>\n";
    }

    echo "\n";
}

if (empty($suites)) {
    echo "> No test results found. Run `composer test` to generate results.\n";
}
