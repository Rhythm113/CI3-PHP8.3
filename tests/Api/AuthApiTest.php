<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

/**
 * JWT Authentication API Tests
 *
 * Tests token generation and protected endpoint access.
 *
 * ┌─────────────────────────────────────────────────────────────┐
 * │  ADDING NEW AUTH TESTS                                      │
 * │                                                              │
 * │  - Use $this->getToken() to get a valid JWT for requests    │
 * │  - Pass it via: $this->get('/api/endpoint', [               │
 * │        'Authorization' => 'Bearer ' . $token                │
 * │    ]);                                                       │
 * │  - Test both authenticated and unauthenticated scenarios    │
 * └─────────────────────────────────────────────────────────────┘
 */
class AuthApiTest extends TestCase
{
    protected string $base_url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_url = getenv('API_BASE_URL') ?: 'http://localhost';
    }

    // ── HTTP helpers (same pattern as HealthApiTest) ──

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
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['status' => 0, 'headers' => [], 'body' => null, 'raw' => '', 'error' => $error];
        }

        $body_str = substr($response, $header_size);
        $header_str = substr($response, 0, $header_size);
        $headers_parsed = [];
        foreach (explode("\r\n", trim($header_str)) as $line) {
            if (str_contains($line, ': ')) {
                [$k, $v] = explode(': ', $line, 2);
                $headers_parsed[strtolower($k)] = $v;
            }
        }

        return [
            'status'  => $status,
            'headers' => $headers_parsed,
            'body'    => json_decode($body_str, true) ?? $body_str,
            'raw'     => $body_str,
            'error'   => null
        ];
    }

    /**
     * Helper: Get a valid JWT token from the token endpoint
     */
    protected function getToken(): ?string
    {
        $response = $this->post('/api/token', [
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        return $response['body']['data']['access_token'] ?? null;
    }

    // ─────────────────────────────────────────────────────────────
    //  Token Generation Tests
    // ─────────────────────────────────────────────────────────────

    public function test_token_endpoint_returns_token(): void
    {
        $response = $this->post('/api/token', [
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('access_token', $response['body']['data']);
        $this->assertArrayHasKey('refresh_token', $response['body']['data']);
        $this->assertEquals('Bearer', $response['body']['data']['token_type']);
    }

    public function test_token_endpoint_rejects_empty_credentials(): void
    {
        $response = $this->post('/api/token', [
            'username' => '',
            'password' => ''
        ]);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('validation_error', $response['body']['error']);
    }

    public function test_token_endpoint_rejects_missing_fields(): void
    {
        $response = $this->post('/api/token', []);

        $this->assertEquals(400, $response['status']);
    }

    // ─────────────────────────────────────────────────────────────
    //  Protected Endpoint Tests
    // ─────────────────────────────────────────────────────────────

    public function test_protected_endpoint_rejects_no_token(): void
    {
        $response = $this->get('/api/protected');

        $this->assertEquals(401, $response['status']);
        $this->assertEquals('unauthorized', $response['body']['error']);
    }

    public function test_protected_endpoint_rejects_invalid_token(): void
    {
        $response = $this->get('/api/protected', [
            'Authorization' => 'Bearer invalid.token.here'
        ]);

        $this->assertEquals(401, $response['status']);
        $this->assertEquals('invalid_token', $response['body']['error']);
    }

    public function test_protected_endpoint_accepts_valid_token(): void
    {
        $token = $this->getToken();
        $this->assertNotNull($token, 'Token generation should succeed');

        $response = $this->get('/api/protected', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('auth_data', $response['body']['data']);
    }
}
