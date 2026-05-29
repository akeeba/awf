<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Encrypt;

use Awf\Encrypt\Base32;
use Awf\Encrypt\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Totp — RFC 6238 / RFC 4226 TOTP/HOTP generation.
 *
 * RFC 6238 §B Appendix B test vectors use the ASCII secret "12345678901234567890"
 * and 8-digit codes with SHA-1.
 *
 * Expected 8-digit codes (from RFC 6238 Appendix B, SHA-1 column):
 *   T=59           → 94287082
 *   T=1111111109   → 07081804
 *   T=1111111111   → 14050471
 *   T=1234567890   → 89005924
 *   T=2000000000   → 69279037
 *   T=20000000000  → 65353130
 */
class TotpTest extends TestCase
{
    /** ASCII secret used in RFC 6238 test vectors */
    private const RFC_SECRET_ASCII = '12345678901234567890';

    /** Base32-encoded form of RFC_SECRET_ASCII */
    private string $rfcSecretB32;

    protected function setUp(): void
    {
        $this->rfcSecretB32 = (new Base32())->encode(self::RFC_SECRET_ASCII);
    }

    // -------------------------------------------------------------------------
    // getPeriod
    // -------------------------------------------------------------------------

    public static function getPeriodProvider(): array
    {
        return [
            // [timestamp, timeStep, expectedPeriod]
            // getPeriod() returns a float (floor() result), so expected values are floats.
            'T=0 step=30'          => [0,          30, 0.0],
            'T=29 step=30'         => [29,          30, 0.0],
            'T=30 step=30'         => [30,          30, 1.0],
            'T=59 step=30'         => [59,          30, 1.0],
            'T=60 step=30'         => [60,          30, 2.0],
            'T=1234567890 step=30' => [1234567890,  30, 41152263.0],
            'T=59 step=60'         => [59,          60, 0.0],
            'T=60 step=60'         => [60,          60, 1.0],
        ];
    }

    #[DataProvider('getPeriodProvider')]
    public function testGetPeriod(int $timestamp, int $timeStep, float $expectedPeriod): void
    {
        $totp = new Totp($timeStep);
        self::assertSame($expectedPeriod, $totp->getPeriod($timestamp));
    }

    public function testGetPeriodWithNullUsesCurrentTime(): void
    {
        $totp   = new Totp(30);
        $before = floor(time() / 30);
        $period = $totp->getPeriod(null);
        $after  = floor(time() / 30);

        // The period must be within [before, after] (same step or one ahead at boundary)
        self::assertGreaterThanOrEqual($before, $period);
        self::assertLessThanOrEqual($after, $period);
    }

    // -------------------------------------------------------------------------
    // getCode – RFC 6238 Appendix B test vectors (8-digit, SHA-1)
    // -------------------------------------------------------------------------

    public static function rfc6238VectorProvider(): array
    {
        return [
            // [timestamp, expected 8-digit code]
            'T=59'           => [59,           '94287082'],
            'T=1111111109'   => [1111111109,   '07081804'],
            'T=1111111111'   => [1111111111,   '14050471'],
            'T=1234567890'   => [1234567890,   '89005924'],
            'T=2000000000'   => [2000000000,   '69279037'],
            'T=20000000000'  => [20000000000,  '65353130'],
        ];
    }

    #[DataProvider('rfc6238VectorProvider')]
    public function testGetCodeRfc6238Vectors(int $timestamp, string $expectedCode): void
    {
        $totp = new Totp(30, 8, 20);
        self::assertSame($expectedCode, $totp->getCode($this->rfcSecretB32, $timestamp));
    }

    // -------------------------------------------------------------------------
    // getCode – 6-digit codes (subset of above, last 6 digits)
    // -------------------------------------------------------------------------

    public static function sixDigitCodeProvider(): array
    {
        return [
            // [timestamp, expected 6-digit code] — rightmost 6 digits of RFC vectors
            'T=59 6-digit'          => [59,         '287082'],
            'T=1111111109 6-digit'  => [1111111109, '081804'],
            'T=1234567890 6-digit'  => [1234567890, '005924'],
        ];
    }

    #[DataProvider('sixDigitCodeProvider')]
    public function testGetCodeSixDigits(int $timestamp, string $expectedCode): void
    {
        $totp = new Totp(30, 6, 20);
        self::assertSame($expectedCode, $totp->getCode($this->rfcSecretB32, $timestamp));
    }

    // -------------------------------------------------------------------------
    // getCode – code is zero-padded to passCodeLength digits
    // -------------------------------------------------------------------------

    public function testGetCodeIsZeroPadded(): void
    {
        // T=1111111109 produces 6-digit "081804" — starts with '0'
        $totp = new Totp(30, 6, 20);
        $code = $totp->getCode($this->rfcSecretB32, 1111111109);
        self::assertSame(6, strlen($code));
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
        self::assertSame('081804', $code);
    }

    // -------------------------------------------------------------------------
    // getCode – output length matches passCodeLength
    // -------------------------------------------------------------------------

    public static function passCodeLengthProvider(): array
    {
        return [
            '6-digit code'  => [6],
            '8-digit code'  => [8],
        ];
    }

    #[DataProvider('passCodeLengthProvider')]
    public function testGetCodeLengthMatchesPassCodeLength(int $passCodeLength): void
    {
        $totp = new Totp(30, $passCodeLength, 20);
        $code = $totp->getCode($this->rfcSecretB32, 1234567890);
        self::assertSame($passCodeLength, strlen($code));
        self::assertMatchesRegularExpression('/^\d+$/', $code);
    }

    // -------------------------------------------------------------------------
    // getCode – null time uses current time (smoke test; no known-value check)
    // -------------------------------------------------------------------------

    public function testGetCodeWithNullTimeReturnsDigitString(): void
    {
        $totp = new Totp(30, 6, 20);
        $code = $totp->getCode($this->rfcSecretB32, null);
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    // -------------------------------------------------------------------------
    // checkCode – window: exact, one step before, one step after
    // -------------------------------------------------------------------------

    public function testCheckCodeReturnsTrueForExactTimestamp(): void
    {
        $totp = new Totp(30, 8, 20);
        $code = $totp->getCode($this->rfcSecretB32, 1111111109);
        self::assertTrue($totp->checkCode($this->rfcSecretB32, $code, 1111111109));
    }

    public function testCheckCodeReturnsTrueForOneStepAhead(): void
    {
        $totp = new Totp(30, 8, 20);
        // Code generated for period of T=1111111109; check at T+30 (one step ahead)
        $code = $totp->getCode($this->rfcSecretB32, 1111111109);
        self::assertTrue($totp->checkCode($this->rfcSecretB32, $code, 1111111109 + 30));
    }

    public function testCheckCodeReturnsTrueForOneStepBehind(): void
    {
        $totp = new Totp(30, 8, 20);
        // Code generated for period of T=1111111109; check at T-30 (one step behind)
        $code = $totp->getCode($this->rfcSecretB32, 1111111109);
        self::assertTrue($totp->checkCode($this->rfcSecretB32, $code, 1111111109 - 30));
    }

    public function testCheckCodeReturnsFalseForTwoStepsAhead(): void
    {
        $totp = new Totp(30, 8, 20);
        $code = $totp->getCode($this->rfcSecretB32, 1111111109);
        self::assertFalse($totp->checkCode($this->rfcSecretB32, $code, 1111111109 + 60));
    }

    public function testCheckCodeReturnsFalseForTwoStepsBehind(): void
    {
        $totp = new Totp(30, 8, 20);
        $code = $totp->getCode($this->rfcSecretB32, 1111111109);
        self::assertFalse($totp->checkCode($this->rfcSecretB32, $code, 1111111109 - 60));
    }

    public function testCheckCodeReturnsFalseForWrongCode(): void
    {
        $totp = new Totp(30, 8, 20);
        self::assertFalse($totp->checkCode($this->rfcSecretB32, '00000000', 1111111109));
    }

    // -------------------------------------------------------------------------
    // getUrl
    // -------------------------------------------------------------------------

    public function testGetUrlReturnsExpectedFormat(): void
    {
        $totp   = new Totp();
        $url    = $totp->getUrl('alice', 'example.com', 'MYSECRET');

        // Must start with the Google Chart QR encoder prefix
        self::assertStringStartsWith(
            'https://chart.googleapis.com/chart?chs=200x200&chld=Q|2&cht=qr&chl=',
            $url
        );

        // The CHL parameter must be URL-encoded OTP auth URI
        $expectedChl = urlencode('otpauth://totp/alice@example.com?secret=MYSECRET');
        self::assertStringEndsWith($expectedChl, $url);
    }

    public function testGetUrlEncodesSpecialCharactersInUser(): void
    {
        $totp = new Totp();
        $url  = $totp->getUrl('alice+test', 'example.com', 'MYSECRET');

        // The @ separator should appear in the CHL portion
        self::assertStringContainsString(urlencode('alice+test@example.com'), $url);
    }

    public function testGetUrlContainsSecretParameter(): void
    {
        $totp   = new Totp();
        $secret = 'GEZDGNBVGY3TQOJQ';
        $url    = $totp->getUrl('user', 'host.example', $secret);

        self::assertStringContainsString(urlencode("secret=$secret"), $url);
    }

    // -------------------------------------------------------------------------
    // generateSecret
    // -------------------------------------------------------------------------

    public function testGenerateSecretReturnsNonEmptyBase32String(): void
    {
        $totp   = new Totp(30, 6, 10);
        $secret = $totp->generateSecret();

        self::assertNotEmpty($secret);
        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGenerateSecretIsDecodable(): void
    {
        $totp   = new Totp(30, 6, 10);
        $secret = $totp->generateSecret();

        // Decoding must not throw; round-trip encode should equal original
        $b32      = new Base32();
        $decoded  = $b32->decode($secret);
        $reencoded = $b32->encode($decoded);
        self::assertSame($secret, $reencoded);
    }

    public function testGenerateSecretLengthCorrespondsToSecretLength(): void
    {
        // secretLength = number of raw bytes; Base32 expands roughly 5 bits per char.
        // For 10 bytes (80 bits) → 16 Base32 chars.
        $totp   = new Totp(30, 6, 10);
        $secret = $totp->generateSecret();
        $b32    = new Base32();
        $decoded = $b32->decode($secret);

        self::assertSame(10, strlen($decoded));
    }

    public function testGenerateSecretProducesDifferentValuesOnSuccessiveCalls(): void
    {
        $totp    = new Totp(30, 6, 10);
        $secrets = [];

        for ($i = 0; $i < 5; $i++) {
            $secrets[] = $totp->generateSecret();
        }

        // At least two distinct secrets expected from 5 calls
        self::assertGreaterThan(1, count(array_unique($secrets)));
    }

    // -------------------------------------------------------------------------
    // Custom timeStep – different step sizes produce correct periods
    // -------------------------------------------------------------------------

    public function testCustomTimeStepAffectsPeriod(): void
    {
        $totp60 = new Totp(60);
        $totp30 = new Totp(30);

        // At T=60, step-60 gives period 1, step-30 gives period 2
        // getPeriod() returns float (floor())
        self::assertSame(1.0, $totp60->getPeriod(60));
        self::assertSame(2.0, $totp30->getPeriod(60));
    }

    public function testCustomTimeStepAffectsCode(): void
    {
        $b32 = new Base32();
        $b32secret = $b32->encode('12345678901234567890');

        $totp30 = new Totp(30, 6, 20);
        $totp60 = new Totp(60, 6, 20);

        // Codes for T=1234567890 differ between step-30 and step-60
        $code30 = $totp30->getCode($b32secret, 1234567890);
        $code60 = $totp60->getCode($b32secret, 1234567890);

        self::assertNotEquals($code30, $code60);
    }
}
