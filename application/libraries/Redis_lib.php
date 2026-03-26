<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Redis Library for CodeIgniter 3
 * Wrapper around the phpredis extension
 */
#[AllowDynamicProperties]
class Redis_lib {

    protected $CI;
    protected $redis;
    protected $prefix;
    protected $connected = FALSE;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('redis', TRUE);

        $this->prefix = $this->CI->config->item('redis_prefix', 'redis') ?: '';

        if ( ! class_exists('Redis'))
        {
            log_message('error', 'Redis extension (phpredis) is not installed');
            return;
        }

        $this->connect();
    }

    /**
     * Connect to Redis server
     */
    protected function connect()
    {
        try
        {
            $this->redis = new Redis();

            $host     = $this->CI->config->item('redis_host', 'redis') ?: '127.0.0.1';
            $port     = $this->CI->config->item('redis_port', 'redis') ?: 6379;
            $timeout  = $this->CI->config->item('redis_timeout', 'redis') ?: 5;
            $password = $this->CI->config->item('redis_password', 'redis');
            $database = $this->CI->config->item('redis_database', 'redis') ?: 0;

            $this->redis->connect($host, $port, $timeout);

            if ( ! empty($password))
            {
                $this->redis->auth($password);
            }

            if ($database > 0)
            {
                $this->redis->select($database);
            }

            $this->connected = TRUE;
            log_message('info', 'Redis connection established');
        }
        catch (\Exception $e)
        {
            log_message('error', 'Redis connection failed: ' . $e->getMessage());
            $this->connected = FALSE;
        }
    }

    /**
     * Check if connected to Redis
     *
     * @return bool
     */
    public function is_connected()
    {
        return $this->connected;
    }

    /**
     * Get a value by key
     *
     * @param  string      $key
     * @return mixed|bool  Value or FALSE on failure
     */
    public function get($key)
    {
        if ( ! $this->connected) return FALSE;
        $value = $this->redis->get($this->prefix . $key);
        $decoded = json_decode($value, TRUE);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
    }

    /**
     * Set a key-value pair
     *
     * @param  string    $key
     * @param  mixed     $value
     * @param  int|null  $ttl   Expiration in seconds (null = no expiry)
     * @return bool
     */
    public function set($key, $value, $ttl = NULL)
    {
        if ( ! $this->connected) return FALSE;
        $value = is_array($value) || is_object($value) ? json_encode($value) : $value;

        if ($ttl !== NULL)
        {
            return $this->redis->setex($this->prefix . $key, $ttl, $value);
        }

        return $this->redis->set($this->prefix . $key, $value);
    }

    /**
     * Delete a key
     *
     * @param  string  $key
     * @return int     Number of keys deleted
     */
    public function delete($key)
    {
        if ( ! $this->connected) return 0;
        return $this->redis->del($this->prefix . $key);
    }

    /**
     * Check if a key exists
     *
     * @param  string  $key
     * @return bool
     */
    public function exists($key)
    {
        if ( ! $this->connected) return FALSE;
        return (bool) $this->redis->exists($this->prefix . $key);
    }

    /**
     * Set expiration on a key
     *
     * @param  string  $key
     * @param  int     $seconds
     * @return bool
     */
    public function expire($key, $seconds)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->expire($this->prefix . $key, $seconds);
    }

    /**
     * Get remaining TTL
     *
     * @param  string  $key
     * @return int     TTL in seconds, -1 if no expiry, -2 if not exists
     */
    public function ttl($key)
    {
        if ( ! $this->connected) return -2;
        return $this->redis->ttl($this->prefix . $key);
    }

    /**
     * Increment a value
     *
     * @param  string  $key
     * @param  int     $by
     * @return int|bool
     */
    public function incr($key, $by = 1)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->incrBy($this->prefix . $key, $by);
    }

    /**
     * Decrement a value
     *
     * @param  string  $key
     * @param  int     $by
     * @return int|bool
     */
    public function decr($key, $by = 1)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->decrBy($this->prefix . $key, $by);
    }

    /**
     * Hash get
     *
     * @param  string  $key
     * @param  string  $field
     * @return string|bool
     */
    public function hget($key, $field)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->hGet($this->prefix . $key, $field);
    }

    /**
     * Hash set
     *
     * @param  string  $key
     * @param  string  $field
     * @param  mixed   $value
     * @return int|bool
     */
    public function hset($key, $field, $value)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->hSet($this->prefix . $key, $field, $value);
    }

    /**
     * Hash get all fields and values
     *
     * @param  string  $key
     * @return array|bool
     */
    public function hgetall($key)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->hGetAll($this->prefix . $key);
    }

    /**
     * Hash delete a field
     *
     * @param  string  $key
     * @param  string  $field
     * @return int|bool
     */
    public function hdel($key, $field)
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->hDel($this->prefix . $key, $field);
    }

    /**
     * Find keys matching a pattern
     *
     * @param  string  $pattern
     * @return array|bool
     */
    public function keys($pattern = '*')
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->keys($this->prefix . $pattern);
    }

    /**
     * Flush the current database
     *
     * @return bool
     */
    public function flush()
    {
        if ( ! $this->connected) return FALSE;
        return $this->redis->flushDB();
    }

    /**
     * Get the raw Redis instance
     *
     * @return Redis|null
     */
    public function instance()
    {
        return $this->connected ? $this->redis : NULL;
    }

    /**
     * Close connection on destruct
     */
    public function __destruct()
    {
        if ($this->connected && $this->redis)
        {
            try { $this->redis->close(); } catch (\Exception $e) {}
        }
    }
}
