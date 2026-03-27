<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load Composer autoloader (run `composer install` in the project root if this fails)
if ( ! class_exists('Firebase\\JWT\\JWT', FALSE))
{
    $autoload = FCPATH . 'vendor/autoload.php';
    if ( ! file_exists($autoload))
    {
        show_error(
            'Composer dependencies not found. Run <code>composer install</code> in the project root.',
            500,
            'Jwt_lib Error'
        );
    }
    require_once $autoload;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


/**
 * JWT Library for CodeIgniter 3
 * Wrapper around firebase/php-jwt
 */
#[AllowDynamicProperties]
class Jwt_lib {

    protected $CI;
    protected $secret;
    protected $algorithm;
    protected $expiration;
    protected $issuer;
    protected $audience;
    protected $refresh_expiration;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('jwt', TRUE);

        $this->secret              = $this->CI->config->item('jwt_secret', 'jwt');
        $this->algorithm           = $this->CI->config->item('jwt_algorithm', 'jwt');
        $this->expiration          = $this->CI->config->item('jwt_expiration', 'jwt');
        $this->issuer              = $this->CI->config->item('jwt_issuer', 'jwt');
        $this->audience            = $this->CI->config->item('jwt_audience', 'jwt');
        $this->refresh_expiration  = $this->CI->config->item('jwt_refresh_expiration', 'jwt');
    }

    /**
     * Generate a JWT access token
     *
     * @param  array  $data  Custom claims to include
     * @return string        Encoded JWT token
     */
    public function generate_token($data = array())
    {
        $time = time();
        $payload = array(
            'iss'  => $this->issuer,
            'iat'  => $time,
            'nbf'  => $time,
            'exp'  => $time + $this->expiration,
            'data' => $data
        );

        if ( ! empty($this->audience))
        {
            $payload['aud'] = $this->audience;
        }

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate a refresh token with longer expiry
     *
     * @param  array  $data  Custom claims
     * @return string        Encoded JWT refresh token
     */
    public function generate_refresh_token($data = array())
    {
        $time = time();
        $payload = array(
            'iss'  => $this->issuer,
            'iat'  => $time,
            'nbf'  => $time,
            'exp'  => $time + $this->refresh_expiration,
            'type' => 'refresh',
            'data' => $data
        );

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Validate and decode a JWT token
     *
     * @param  string      $token  The JWT token string
     * @return object|bool         Decoded payload on success, FALSE on failure
     */
    public function validate_token($token)
    {
        try
        {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded;
        }
        catch (\Exception $e)
        {
            log_message('error', 'JWT validation failed: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Decode a token without validation (for debugging)
     *
     * @param  string  $token
     * @return object|bool
     */
    public function decode_token($token)
    {
        try
        {
            $parts = explode('.', $token);
            if (count($parts) !== 3)
            {
                return FALSE;
            }
            return json_decode(base64_decode($parts[1]));
        }
        catch (\Exception $e)
        {
            return FALSE;
        }
    }

    /**
     * Extract token from Authorization header
     *
     * @return string|bool  Token string or FALSE if not found
     */
    public function get_token_from_header()
    {
        $header = $this->CI->input->get_request_header('Authorization');

        if ( ! empty($header))
        {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches))
            {
                return $matches[1];
            }
        }

        return FALSE;
    }
}
