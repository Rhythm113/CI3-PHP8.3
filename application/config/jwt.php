<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| JWT Configuration
|--------------------------------------------------------------------------
*/

// Secret key for signing tokens - CHANGE THIS IN PRODUCTION!
$config['jwt_secret'] = 'your-secret-key-change-me-in-production';

// Algorithm for signing (HS256, HS384, HS512)
$config['jwt_algorithm'] = 'HS256';

// Token expiration time in seconds (default: 1 hour)
$config['jwt_expiration'] = 3600;

// Token issuer
$config['jwt_issuer'] = 'ci3-api';

// Token audience (optional)
$config['jwt_audience'] = '';

// Refresh token expiration in seconds (default: 7 days)  
$config['jwt_refresh_expiration'] = 604800;
