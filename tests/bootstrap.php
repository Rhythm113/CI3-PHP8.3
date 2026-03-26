<?php
/**
 * PHPUnit Bootstrap for CodeIgniter 3
 *
 * Sets up the CI3 environment so tests can use CI libraries.
 */


define('ENVIRONMENT', 'testing');

$dir = dirname(__DIR__);
define('FCPATH', $dir . DIRECTORY_SEPARATOR);
define('SELF', 'index.php');
define('BASEPATH', $dir . '/system/');
define('APPPATH', $dir . '/application/');
define('VIEWPATH', APPPATH . 'views/');
define('SYSDIR', 'system');


require_once FCPATH . 'vendor/autoload.php';


require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/compat/mbstring.php';
require_once BASEPATH . 'core/compat/hash.php';
require_once BASEPATH . 'core/compat/password.php';
require_once BASEPATH . 'core/compat/standard.php';

$results_dir = $dir . '/tests/results';
if (!is_dir($results_dir)) {
    mkdir($results_dir, 0755, true);
}
