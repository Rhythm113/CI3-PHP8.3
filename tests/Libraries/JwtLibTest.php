<?php

namespace Tests\Libraries;

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT Library Unit Tests
 *
 * Tests JWT token encoding/decoding directly (no HTTP needed).
 *
 * HOW TO ADD LIBRARY TESTS:
 *
 *   1. Create a file in tests/Libraries/ e.g. RedisLibTest.php
 *   2. Test the library logic directly (no HTTP requests needed)
 *   3. Use setUp() to initialize any shared state
 *
 *   Example:
 *
 *     class RedisLibTest extends TestCase
 *     {
 *         public function test_can_set_and_get_value(): void
 *         {
 *             $redis = new \Redis();
 *             $redis->connect('127.0.0.1', 6379);
 *             $redis->set('test_key', 'hello');
 *             $this->assertEquals('hello', $redis->get('test_key'));
 *             $redis->del('test_key');
 *         }
 *     }
 */
class JwtLibTest extends TestCase
{
    // firebase/php-jwt v7 requires HS256 keys to be at least 256 bits (32 bytes)
    private string $secret = 'test-secret-key-for-phpunit-ci3-tests';
    private string $algorithm = 'HS256';

    public function test_can_encode_and_decode_token(): void
    {
        $payload = [
            'iss'  => 'test',
            'iat'  => time(),
            'exp'  => time() + 3600,
            'data' => ['user_id' => 42, 'role' => 'admin']
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);
        $this->assertIsString($token);

        $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
        $this->assertEquals(42, $decoded->data->user_id);
        $this->assertEquals('admin', $decoded->data->role);
    }

    public function test_expired_token_throws_exception(): void
    {
        $payload = [
            'iss' => 'test',
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'data' => ['user_id' => 1]
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);

        $this->expectException(\Firebase\JWT\ExpiredException::class);
        JWT::decode($token, new Key($this->secret, $this->algorithm));
    }

    public function test_wrong_secret_throws_exception(): void
    {
        $payload = [
            'iss' => 'test',
            'iat' => time(),
            'exp' => time() + 3600,
            'data' => []
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);
        // wrong-secret-key-that-is-long-enough-for-v7 = 40+ chars
        JWT::decode($token, new Key('wrong-secret-key-that-is-long-enough-for-v7', $this->algorithm));
    }

    public function test_token_has_three_parts(): void
    {
        $payload = [
            'iss' => 'test',
            'iat' => time(),
            'exp' => time() + 3600,
            'data' => []
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);
        $parts = explode('.', $token);

        $this->assertCount(3, $parts, 'JWT should have 3 dot-separated parts');
    }

    public function test_payload_data_is_preserved(): void
    {
        $data = [
            'user_id'     => 99,
            'username'    => 'testuser',
            'role'        => 'editor',
            'permissions' => ['read', 'write']
        ];

        $payload = [
            'iss'  => 'ci3-api',
            'iat'  => time(),
            'exp'  => time() + 3600,
            'data' => $data
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);
        $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

        $this->assertEquals(99, $decoded->data->user_id);
        $this->assertEquals('testuser', $decoded->data->username);
        $this->assertEquals('editor', $decoded->data->role);
        $this->assertCount(2, $decoded->data->permissions);
    }
}
