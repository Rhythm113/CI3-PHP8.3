<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * cURL Library for CodeIgniter 3
 * HTTP client wrapper around PHP cURL extension
 */
#[AllowDynamicProperties]
class Curl_lib {

    protected $CI;
    protected $headers = array();
    protected $options = array();
    protected $timeout = 30;
    protected $connect_timeout = 10;
    protected $verify_ssl = TRUE;
    protected $last_response;
    protected $last_info;
    protected $last_error;

    public function __construct()
    {
        $this->CI =& get_instance();

        if ( ! function_exists('curl_init'))
        {
            log_message('error', 'cURL extension is not loaded');
            show_error('cURL extension is required but not loaded.');
        }
    }

    /**
     * Set request headers
     *
     * @param  array  $headers  Associative array of headers
     * @return Curl_lib
     */
    public function set_headers($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set a single header
     *
     * @param  string  $key
     * @param  string  $value
     * @return Curl_lib
     */
    public function set_header($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set timeout
     *
     * @param  int  $seconds
     * @return Curl_lib
     */
    public function set_timeout($seconds)
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set connection timeout
     *
     * @param  int  $seconds
     * @return Curl_lib
     */
    public function set_connect_timeout($seconds)
    {
        $this->connect_timeout = $seconds;
        return $this;
    }

    /**
     * Enable/disable SSL verification
     *
     * @param  bool  $verify
     * @return Curl_lib
     */
    public function verify_ssl($verify = TRUE)
    {
        $this->verify_ssl = $verify;
        return $this;
    }

    /**
     * Set basic authentication
     *
     * @param  string  $username
     * @param  string  $password
     * @return Curl_lib
     */
    public function set_basic_auth($username, $password)
    {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
        return $this;
    }

    /**
     * Set bearer token
     *
     * @param  string  $token
     * @return Curl_lib
     */
    public function set_bearer_token($token)
    {
        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $this;
    }

    /**
     * GET request
     *
     * @param  string  $url
     * @param  array   $params  Query parameters
     * @return mixed
     */
    public function get($url, $params = array())
    {
        if ( ! empty($params))
        {
            $url .= '?' . http_build_query($params);
        }

        return $this->_execute('GET', $url);
    }

    /**
     * POST request
     *
     * @param  string  $url
     * @param  mixed   $data  Post data (array or string)
     * @return mixed
     */
    public function post($url, $data = array())
    {
        return $this->_execute('POST', $url, $data);
    }

    /**
     * PUT request
     *
     * @param  string  $url
     * @param  mixed   $data
     * @return mixed
     */
    public function put($url, $data = array())
    {
        return $this->_execute('PUT', $url, $data);
    }

    /**
     * PATCH request
     *
     * @param  string  $url
     * @param  mixed   $data
     * @return mixed
     */
    public function patch($url, $data = array())
    {
        return $this->_execute('PATCH', $url, $data);
    }

    /**
     * DELETE request
     *
     * @param  string  $url
     * @param  mixed   $data
     * @return mixed
     */
    public function delete($url, $data = array())
    {
        return $this->_execute('DELETE', $url, $data);
    }

    /**
     * POST JSON data
     *
     * @param  string  $url
     * @param  mixed   $data  Data to encode as JSON
     * @return mixed
     */
    public function post_json($url, $data = array())
    {
        $this->set_header('Content-Type', 'application/json');
        return $this->_execute('POST', $url, json_encode($data));
    }

    /**
     * PUT JSON data
     *
     * @param  string  $url
     * @param  mixed   $data
     * @return mixed
     */
    public function put_json($url, $data = array())
    {
        $this->set_header('Content-Type', 'application/json');
        return $this->_execute('PUT', $url, json_encode($data));
    }

    /**
     * Get last response info
     *
     * @return array|null
     */
    public function get_info()
    {
        return $this->last_info;
    }

    /**
     * Get last HTTP status code
     *
     * @return int|null
     */
    public function get_status_code()
    {
        return isset($this->last_info['http_code']) ? $this->last_info['http_code'] : NULL;
    }

    /**
     * Get last error
     *
     * @return string|null
     */
    public function get_error()
    {
        return $this->last_error;
    }

    /**
     * Get last raw response
     *
     * @return string|null
     */
    public function get_response()
    {
        return $this->last_response;
    }

    /**
     * Execute the cURL request
     *
     * @param  string  $method
     * @param  string  $url
     * @param  mixed   $data
     * @return mixed   Response body (auto-decoded if JSON) or FALSE on error
     */
    protected function _execute($method, $url, $data = NULL)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verify_ssl ? 2 : 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        // Set the HTTP method
        switch (strtoupper($method))
        {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, TRUE);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        // Set post data
        if ($data !== NULL)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        }

        // Set headers
        if ( ! empty($this->headers))
        {
            $formatted = array();
            foreach ($this->headers as $key => $value)
            {
                $formatted[] = $key . ': ' . $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted);
        }

        // Set custom options
        foreach ($this->options as $opt => $val)
        {
            curl_setopt($ch, $opt, $val);
        }

        // Execute
        $response = curl_exec($ch);
        $this->last_info = curl_getinfo($ch);
        $this->last_error = curl_error($ch);
        $this->last_response = $response;

        curl_close($ch);

        // Reset state for next request
        $this->headers = array();
        $this->options = array();

        if ($response === FALSE)
        {
            log_message('error', 'cURL error: ' . $this->last_error);
            return FALSE;
        }

        // Auto-decode JSON responses
        $decoded = json_decode($response, TRUE);
        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $decoded;
        }

        return $response;
    }
}
