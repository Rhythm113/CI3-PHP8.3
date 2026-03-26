<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load the custom API base controller (CI3 only auto-loads MY_*.php from
// application/core/, so API_Controller.php must be required explicitly).
if ( ! class_exists('API_Controller', FALSE))
{
    require_once APPPATH . 'core/API_Controller.php';
}

/**
 * Example API Controller
 * Demonstrates how to use the API_Controller base class
 */
class Api extends API_Controller {

    // Set to FALSE for public endpoints; override per-method if needed
    protected $require_auth = FALSE;

    /**
     * Health check endpoint
     * GET /api/health
     */
    public function health_get()
    {
        $this->success(array(
            'framework' => 'CodeIgniter 3.1.13',
            'php'       => PHP_VERSION,
            'timestamp' => date('c'),
            'status'    => 'healthy'
        ), 'API is running');
    }

    /**
     * Generate a test JWT token
     * POST /api/token
     */
    public function token_post()
    {
        $data = $this->get_request_data();

        // In production, validate credentials against your database here
        $username = isset($data['username']) ? $data['username'] : '';
        $password = isset($data['password']) ? $data['password'] : '';

        // Demo: accept any non-empty credentials
        if (empty($username) || empty($password))
        {
            $this->error('Username and password are required', 400, 'validation_error');
        }

        $token = $this->jwt_lib->generate_token(array(
            'user_id'  => 1,
            'username' => $username,
            'role'     => 'user'
        ));

        $refresh = $this->jwt_lib->generate_refresh_token(array(
            'user_id' => 1
        ));

        $this->success(array(
            'access_token'  => $token,
            'refresh_token' => $refresh,
            'token_type'    => 'Bearer',
            'expires_in'    => $this->config->item('jwt_expiration')
        ), 'Token generated');
    }

    /**
     * Protected endpoint example (requires JWT)
     * GET /api/protected
     */
    public function protected_get()
    {
        // Manually require auth for this endpoint
        $this->_authenticate();

        $user = $this->get_auth_user();

        $this->success(array(
            'message'   => 'You have accessed a protected resource',
            'auth_data' => $user->data
        ), 'Authorized');
    }

    /**
     * Route requests to method_verb pattern
     * e.g., GET /api/health -> health_get()
     */
    public function _remap($method, $params = array())
    {
        $verb = strtolower($this->input->method());
        $callable = $method . '_' . $verb;

        if (method_exists($this, $callable))
        {
            return call_user_func_array(array($this, $callable), $params);
        }

        // Try without verb suffix
        if (method_exists($this, $method))
        {
            return call_user_func_array(array($this, $method), $params);
        }

        $this->error('Endpoint not found', 404, 'not_found');
    }
}
