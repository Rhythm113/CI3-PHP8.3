<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
|
| Active Group: Set to 'mysql' or 'postgre' to switch databases.
|
| Supported drivers: mysqli, postgre, pdo (pdo/mysql, pdo/pgsql)
|
*/

$active_group = 'mysql';
$query_builder = TRUE;

/*
|--------------------------------------------------------------------------
| MySQL Configuration
|--------------------------------------------------------------------------
*/
$db['mysql'] = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => 'root',
	'password' => 'root',
	'database' => 'ci3_db',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_general_ci',
	'swap_pre' => '',
	'encrypt'  => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE,
	'port'     => 3306
);

/*
|--------------------------------------------------------------------------
| PostgreSQL Configuration
|--------------------------------------------------------------------------
*/
$db['postgre'] = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => 'postgres',
	'password' => '',
	'database' => 'ci3_db',
	'dbdriver' => 'postgre',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => '',
	'swap_pre' => '',
	'encrypt'  => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE,
	'port'     => 5432,
	'schema'   => 'public'
);

/*
|--------------------------------------------------------------------------
| Redis Configuration
|--------------------------------------------------------------------------
| Redis is NOT a traditional database driver in CI3. It is used via:
|   - Session driver:  Set $config['sess_driver'] = 'redis' in config.php
|   - Cache driver:    $this->load->driver('cache', array('adapter' => 'redis'))
|   - Custom library:  $this->load->library('redis_lib') (see application/libraries/Redis_lib.php)
|
| Session save_path format:  tcp://host:port?auth=password&database=0
| Cache redis config is set below.
|
| For the Redis_lib library, see application/config/redis.php
|--------------------------------------------------------------------------
*/

// Redis config for CI3 cache driver ($this->cache->redis)
$config['redis'] = array(
	'host'     => '127.0.0.1',
	'password' => NULL,
	'port'     => 6379,
	'timeout'  => 0,
	'database' => 0
);

// To use Redis for sessions, set these in application/config/config.php:
// $config['sess_driver']    = 'redis';
// $config['sess_save_path'] = 'tcp://127.0.0.1:6379';
//
// With password and database:
// $config['sess_save_path'] = 'tcp://127.0.0.1:6379?auth=your_password&database=2';
