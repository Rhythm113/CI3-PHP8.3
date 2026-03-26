<?php
/**
 * PHPUnit Bootstrap for CodeIgniter 3
 *
 * Keeps this minimal — API tests use HTTP (no CI3 core needed),
 * and Library tests use Composer packages directly (firebase/php-jwt).
 *
 * CI3 constants are set here so any file that checks them won't error,
 * but we do NOT load CI3 core files (they have unresolvable interdependencies
 * outside the full CI3 bootstrap chain).
 */

// ENVIRONMENT is also declared in phpunit.xml as a <const>, so guard it.
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Minimal CI3 path constants (needed if any included file references them)
$dir = dirname(__DIR__);
if (!defined('FCPATH'))   define('FCPATH',   $dir . DIRECTORY_SEPARATOR);
if (!defined('BASEPATH')) define('BASEPATH', $dir . '/system/');
if (!defined('APPPATH'))  define('APPPATH',  $dir . '/application/');
if (!defined('VIEWPATH')) define('VIEWPATH', APPPATH . 'views/');
if (!defined('SELF'))     define('SELF',     'index.php');
if (!defined('SYSDIR'))   define('SYSDIR',   'system');

// Composer autoload (provides firebase/php-jwt and PHPUnit)
require_once FCPATH . 'vendor/autoload.php';

// Ensure test results directory exists
$results_dir = $dir . '/tests/results';
if (!is_dir($results_dir)) {
    mkdir($results_dir, 0755, true);
}
