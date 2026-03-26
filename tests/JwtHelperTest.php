<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Tests for the JWT encode/decode workflow used by Jwt_lib.
 *
 * These tests exercise the firebase/php-jwt library directly (no CI
 * bootstrap required) to validate that token generation and validation
 * logic works correctly on PHP 8.3.
 */
class JwtHelperTest extends TestCase
{
    private string $secret    = 'test-secret-key-for-unit-tests-only';
    private string $algorithm = 'HS256';

    // ---------------------------------------------------------------
    // Token encoding
    // ---------------------------------------------------------------

    public function testEncodeReturnsThreePartToken(): void
    {
        $token = JWT::encode(['sub' => '1'], $this->secret, $this->algorithm);
        $parts = explode('.', $token);

        $this->assertCount(3, $parts, 'A JWT must have exactly three dot-separated parts.');
    }

    // ---------------------------------------------------------------
    // Token decoding
    // ---------------------------------------------------------------

    public function testDecodeReturnsCorrectClaims(): void
    {
        $payload = ['sub' => '42', 'role' => 'user'];
        $token   = JWT::encode($payload, $this->secret, $this->algorithm);
        $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

        $this->assertSame('42',   $decoded->sub);
        $this->assertSame('user', $decoded->role);
    }

    public function testDecodeFailsWithWrongSecret(): void
    {
        $token       = JWT::encode(['sub' => '1'], $this->secret, $this->algorithm);
        $wrongSecret = 'wrong-secret-key-that-is-long-enough-for-hs256';

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);
        JWT::decode($token, new Key($wrongSecret, $this->algorithm));
    }

    public function testDecodeFailsForExpiredToken(): void
    {
        $payload = [
            'sub' => '1',
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ];
        $token = JWT::encode($payload, $this->secret, $this->algorithm);

        $this->expectException(\Firebase\JWT\ExpiredException::class);
        JWT::decode($token, new Key($this->secret, $this->algorithm));
    }

    // ---------------------------------------------------------------
    // Inline decode helper (mirrors Jwt_lib::decode_token logic)
    // ---------------------------------------------------------------

    /**
     * Replicate the decode_token() helper from Jwt_lib without requiring the
     * CodeIgniter singleton, so the logic can be tested in isolation.
     */
    private function decodeTokenParts(string $token): object|false
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
        {
            return false;
        }

        $decoded = json_decode(base64_decode($parts[1]));
        return ($decoded instanceof \stdClass) ? $decoded : false;
    }

    public function testDecodeTokenPartsExtractsPayload(): void
    {
        $payload = ['user_id' => 5, 'username' => 'alice'];
        $token   = JWT::encode($payload, $this->secret, $this->algorithm);

        $result = $this->decodeTokenParts($token);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame(5,       $result->user_id);
        $this->assertSame('alice', $result->username);
    }

    public function testDecodeTokenPartsReturnsFalseForInvalidToken(): void
    {
        $this->assertFalse($this->decodeTokenParts('not.a.valid.jwt.string.with.too.many.parts'));
        $this->assertFalse($this->decodeTokenParts('onlytwoparts'));
    }

    // ---------------------------------------------------------------
    // Standard claims
    // ---------------------------------------------------------------

    public function testTokenContainsIssuedAtClaim(): void
    {
        $before  = time();
        $payload = ['sub' => '1', 'iat' => $before];
        $token   = JWT::encode($payload, $this->secret, $this->algorithm);
        $after   = time();

        $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

        $this->assertGreaterThanOrEqual($before, $decoded->iat);
        $this->assertLessThanOrEqual($after,  $decoded->iat);
    }
}
