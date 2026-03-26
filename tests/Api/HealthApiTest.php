<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

/**
 * API Health Endpoint Tests
 *
 * Tests the /api/health endpoint to ensure the API is running.
 *
 * HOW TO ADD A NEW API TEST:
 *
 *   1. Create a new file in tests/Api/ e.g. UserApiTest.php
 *   2. Extend TestCase and use the $base_url property
 *   3. Use $this->get() / $this->post() helpers to hit endpoints
 *   4. Assert response status, body, and headers
 *
 *   Example:
 *
 *     class UserApiTest extends TestCase
 *     {
 *         protected string $base_url = 'http://localhost';
 *
 *         public function test_create_user(): void
 *         {
 *             $response = $this->post('/api/users', [
 *                 'name'  => 'John',
 *                 'email' => 'john@example.com'
 *             ]);
 *             $this->assertEquals(201, $response['status']);
 *         }
 *     }
 */
class HealthApiTest extends TestCase
{
    protected string $base_url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_url = getenv('API_BASE_URL') ?: 'http://localhost';
    }

    // ---------------------------------------------------------------
    //  Helper: HTTP request methods
    // ---------------------------------------------------------------

    protected function get(string $uri, array $headers = []): array
    {
        return $this->request('GET', $uri, null, $headers);
    }

    protected function post(string $uri, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $uri, $data, $headers);
    }

    protected function request(string $method, string $uri, ?array $data = null, array $headers = []): array
    {
        $ch = curl_init($this->base_url . $uri);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $request_headers = ['Accept: application/json'];
        foreach ($headers as $key => $value) {
            $request_headers[] = "$key: $value";
        }

        if ($data !== null) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $request_headers[] = 'Content-Type: application/json';
            $request_headers[] = 'Content-Length: ' . strlen($json);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        $response    = curl_exec($ch);
        $status      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error       = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['status' => 0, 'headers' => [], 'body' => null, 'raw' => '', 'error' => $error];
        }

        $header_str = substr($response, 0, $header_size);
        $body_str   = substr($response, $header_size);
        $body       = json_decode($body_str, true);

        return [
            'status'  => $status,
            'headers' => $this->parseHeaders($header_str),
            'body'    => $body ?? $body_str,
            'raw'     => $body_str,
            'error'   => null
        ];
    }

    protected function parseHeaders(string $raw): array
    {
        $headers = [];
        foreach (explode("\r\n", trim($raw)) as $line) {
            if (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $headers[strtolower($key)] = $value;
            }
        }
        return $headers;
    }

    // ---------------------------------------------------------------
    //  Tests
    // ---------------------------------------------------------------

    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->get('/api/health');

        $this->assertEquals(200, $response['status'],
            'Health endpoint should return 200. Error: ' . ($response['error'] ?? $response['raw']));
    }

    public function test_health_endpoint_returns_json(): void
    {
        $response = $this->get('/api/health');

        $this->assertIsArray($response['body'], 'Response body should be valid JSON');
        $this->assertArrayHasKey('status', $response['body']);
        $this->assertArrayHasKey('message', $response['body']);
    }

    public function test_health_endpoint_contains_correct_data(): void
    {
        $response = $this->get('/api/health');

        $this->assertEquals(200, $response['body']['status']);
        $this->assertEquals('API is running', $response['body']['message']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertEquals('healthy', $response['body']['data']['status']);
        $this->assertEquals('CodeIgniter 3.1.13', $response['body']['data']['framework']);
    }

    public function test_health_endpoint_has_cors_headers(): void
    {
        $response = $this->get('/api/health');

        $this->assertArrayHasKey('access-control-allow-origin', $response['headers'],
            'CORS header Access-Control-Allow-Origin should be present');
    }

    public function test_health_endpoint_has_rate_limit_headers(): void
    {
        $response = $this->get('/api/health');

        $this->assertArrayHasKey('x-ratelimit-limit', $response['headers'],
            'Rate limit header X-RateLimit-Limit should be present');
        $this->assertArrayHasKey('x-ratelimit-remaining', $response['headers'],
            'Rate limit header X-RateLimit-Remaining should be present');
    }
}
