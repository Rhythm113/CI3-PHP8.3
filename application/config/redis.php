<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Redis Configuration
|--------------------------------------------------------------------------
*/

// Redis server host
$config['redis_host'] = '127.0.0.1';

// Redis server port
$config['redis_port'] = 6379;

// Redis password (leave empty if no auth)
$config['redis_password'] = '';

// Redis database index (0-15)
$config['redis_database'] = 0;

// Connection timeout in seconds
$config['redis_timeout'] = 5;

// Key prefix for namespacing
$config['redis_prefix'] = 'ci3:';
