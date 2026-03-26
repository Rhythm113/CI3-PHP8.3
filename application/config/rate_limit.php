<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
*/

// Maximum number of requests allowed in the time window
$config['rate_limit_requests'] = 60;

// Time window in seconds (default: 60 seconds = 1 minute)
$config['rate_limit_window'] = 60;

// Storage driver: 'file' or 'redis'
// 'file' uses the CI cache directory, 'redis' requires the Redis library
$config['rate_limit_driver'] = 'file';

// File cache directory (used when driver is 'file')
$config['rate_limit_cache_path'] = APPPATH . 'cache/rate_limit/';

// Whether to include rate limit headers in API responses
$config['rate_limit_headers'] = TRUE;

// Routes to exclude from rate limiting (array of URI patterns)
$config['rate_limit_exclude'] = array();
