<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base API Controller
 * Extend this controller for all API endpoints.
 *
 * Provides: JWT auth, rate limiting, CORS, JSON I/O helpers
 */
#[AllowDynamicProperties]
class API_Controller extends CI_Controller {

    /**
     * Decoded JWT payload (set after successful auth)
     * @var object|null
     */
    protected $auth_user = NULL;

    /**
     * Whether to enforce JWT auth (override in child controllers)
     * @var bool
     */
    protected $require_auth = FALSE;

    /**
     * Whether to enforce rate limiting
     * @var bool
     */
    protected $rate_limit = TRUE;

    public function __construct()
    {
        parent::__construct();

        // Load required libraries
        $this->load->library('jwt_lib');
        $this->load->library('rate_limiter');
        $this->load->library('curl_lib');

        // Set CORS headers
        $this->_set_cors();

        // Handle preflight OPTIONS request
        if ($this->input->method() === 'options')
        {
            $this->output->set_status_header(200);
            exit;
        }

        // Rate limiting check
        if ($this->rate_limit)
        {
            if ( ! $this->rate_limiter->check())
            {
                $this->rate_limiter->limit_exceeded();
            }
        }

        // JWT authentication check
        if ($this->require_auth)
        {
            $this->_authenticate();
        }
    }

    /**
     * Authenticate via JWT token in Authorization header
     */
    protected function _authenticate()
    {
        $token = $this->jwt_lib->get_token_from_header();

        if ( ! $token)
        {
            $this->response(array(
                'status'  => 401,
                'error'   => 'unauthorized',
                'message' => 'No authentication token provided'
            ), 401);
        }

        $decoded = $this->jwt_lib->validate_token($token);

        if ( ! $decoded)
        {
            $this->response(array(
                'status'  => 401,
                'error'   => 'invalid_token',
                'message' => 'Invalid or expired token'
            ), 401);
        }

        $this->auth_user = $decoded;
    }

    /**
     * Get the authenticated user data from the JWT
     *
     * @return object|null
     */
    protected function get_auth_user()
    {
        return $this->auth_user;
    }

    /**
     * Get request body data (JSON or form)
     *
     * @return array
     */
    protected function get_request_data()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, TRUE);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data))
        {
            return $data;
        }

        // Fall back to POST/PUT data
        return $this->input->post() ?: array();
    }

    /**
     * Send a JSON response and exit
     *
     * @param  mixed  $data         Response data
     * @param  int    $status_code  HTTP status code
     */
    protected function response($data, $status_code = 200)
    {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    /**
     * Send a success response
     *
     * @param  mixed   $data
     * @param  string  $message
     * @param  int     $status_code
     */
    protected function success($data = NULL, $message = 'Success', $status_code = 200)
    {
        $response = array(
            'status'  => $status_code,
            'message' => $message
        );

        if ($data !== NULL)
        {
            $response['data'] = $data;
        }

        $this->response($response, $status_code);
    }

    /**
     * Send an error response
     *
     * @param  string  $message
     * @param  int     $status_code
     * @param  string  $error_code
     */
    protected function error($message = 'Error', $status_code = 400, $error_code = 'bad_request')
    {
        $this->response(array(
            'status'  => $status_code,
            'error'   => $error_code,
            'message' => $message
        ), $status_code);
    }

    /**
     * Set CORS headers
     */
    protected function _set_cors()
    {
        $this->output->set_header('Access-Control-Allow-Origin: *');
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $this->output->set_header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        $this->output->set_header('Access-Control-Max-Age: 3600');
    }
}
