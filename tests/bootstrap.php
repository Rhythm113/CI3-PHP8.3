<?php
/**
 * PHPUnit bootstrap for CI3-PHP8.3
 *
 * Defines the minimum constants required so test files can include
 * application source files without triggering the CI "No direct
 * script access allowed" guard.
 */

define('BASEPATH', realpath(__DIR__ . '/../system') . DIRECTORY_SEPARATOR);
define('APPPATH',  realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
define('FCPATH',   realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'testing');

require_once FCPATH . 'vendor/autoload.php';
