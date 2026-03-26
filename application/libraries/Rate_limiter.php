<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate Limiter Library for CodeIgniter 3
 * IP-based rate limiting with file or Redis storage
 */
#[AllowDynamicProperties]
class Rate_limiter {

    protected $CI;
    protected $max_requests;
    protected $window;
    protected $driver;
    protected $cache_path;
    protected $include_headers;
    protected $exclude_routes;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('rate_limit', TRUE);

        $this->max_requests    = $this->CI->config->item('rate_limit_requests', 'rate_limit') ?: 60;
        $this->window          = $this->CI->config->item('rate_limit_window', 'rate_limit') ?: 60;
        $this->driver          = $this->CI->config->item('rate_limit_driver', 'rate_limit') ?: 'file';
        $this->cache_path      = $this->CI->config->item('rate_limit_cache_path', 'rate_limit');
        $this->include_headers = $this->CI->config->item('rate_limit_headers', 'rate_limit') !== FALSE;
        $this->exclude_routes  = $this->CI->config->item('rate_limit_exclude', 'rate_limit') ?: array();

        // Create cache directory if using file driver
        if ($this->driver === 'file' && ! is_dir($this->cache_path))
        {
            mkdir($this->cache_path, 0755, TRUE);
        }
    }

    /**
     * Check if the current request is rate limited
     *
     * @param  string|null  $identifier  Custom identifier (defaults to IP)
     * @return bool         TRUE if allowed, FALSE if rate limited
     */
    public function check($identifier = NULL)
    {
        // Check if current route is excluded
        $uri = $this->CI->uri->uri_string();
        foreach ($this->exclude_routes as $pattern)
        {
            if (preg_match('#^' . $pattern . '$#', $uri))
            {
                return TRUE;
            }
        }

        if ($identifier === NULL)
        {
            $identifier = $this->CI->input->ip_address();
        }

        $key = 'rate_limit:' . md5($identifier);

        if ($this->driver === 'redis')
        {
            return $this->_check_redis($key);
        }

        return $this->_check_file($key);
    }

    /**
     * Check rate limit using file storage
     */
    protected function _check_file($key)
    {
        $file = $this->cache_path . $key . '.json';

        $data = array('count' => 0, 'start' => time());

        if (file_exists($file))
        {
            $content = file_get_contents($file);
            $data = json_decode($content, TRUE);

            if ($data === NULL)
            {
                $data = array('count' => 0, 'start' => time());
            }

            // Check if window has expired
            if ((time() - $data['start']) >= $this->window)
            {
                $data = array('count' => 0, 'start' => time());
            }
        }

        $data['count']++;

        file_put_contents($file, json_encode($data), LOCK_EX);

        // Set headers
        $remaining = max(0, $this->max_requests - $data['count']);
        $reset = $data['start'] + $this->window;
        $this->_set_rate_headers($this->max_requests, $remaining, $reset);

        if ($data['count'] > $this->max_requests)
        {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check rate limit using Redis storage
     */
    protected function _check_redis($key)
    {
        if ( ! isset($this->CI->redis_lib) || ! $this->CI->redis_lib->is_connected())
        {
            log_message('error', 'Rate limiter: Redis not available, falling back to file driver');
            return $this->_check_file($key);
        }

        $count = $this->CI->redis_lib->get($key);

        if ($count === FALSE)
        {
            // First request in this window
            $this->CI->redis_lib->set($key, 1, $this->window);
            $remaining = $this->max_requests - 1;
            $this->_set_rate_headers($this->max_requests, $remaining, time() + $this->window);
            return TRUE;
        }

        $count = (int) $count;

        if ($count >= $this->max_requests)
        {
            $ttl = $this->CI->redis_lib->ttl($key);
            $reset = time() + max(0, $ttl);
            $this->_set_rate_headers($this->max_requests, 0, $reset);
            return FALSE;
        }

        $this->CI->redis_lib->incr($key);
        $ttl = $this->CI->redis_lib->ttl($key);
        $remaining = max(0, $this->max_requests - $count - 1);
        $this->_set_rate_headers($this->max_requests, $remaining, time() + max(0, $ttl));
        return TRUE;
    }

    /**
     * Set rate limit HTTP headers
     */
    protected function _set_rate_headers($limit, $remaining, $reset)
    {
        if ( ! $this->include_headers) return;

        $this->CI->output->set_header('X-RateLimit-Limit: ' . $limit);
        $this->CI->output->set_header('X-RateLimit-Remaining: ' . $remaining);
        $this->CI->output->set_header('X-RateLimit-Reset: ' . $reset);
    }

    /**
     * Send 429 Too Many Requests response
     *
     * @param  string  $message
     */
    public function limit_exceeded($message = 'Too Many Requests')
    {
        $this->CI->output
            ->set_status_header(429)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => 429,
                'error'   => 'rate_limit_exceeded',
                'message' => $message
            )))
            ->_display();
        exit;
    }

    /**
     * Clean up expired file-based rate limit entries
     */
    public function cleanup()
    {
        if ($this->driver !== 'file') return;

        $files = glob($this->cache_path . '*.json');
        $now = time();

        foreach ($files as $file)
        {
            $content = file_get_contents($file);
            $data = json_decode($content, TRUE);

            if ($data && ($now - $data['start']) > ($this->window * 2))
            {
                unlink($file);
            }
        }
    }
}
