<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Encrypt;

use Awf\Encrypt\Base32;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Base32 encode/decode using RFC 4648 test vectors and additional edge cases.
 *
 * RFC 4648 §10 Test Vectors (without padding, as this implementation omits '=' padding):
 *   ""       → ""
 *   "f"      → "MY"
 *   "fo"     → "MZXQ"
 *   "foo"    → "MZXW6"
 *   "foobar" → "MZXW6YTB"
 */
class Base32Test extends TestCase
{
    // -------------------------------------------------------------------------
    // encode – RFC 4648 test vectors
    // -------------------------------------------------------------------------

    public static function encodeRfc4648Provider(): array
    {
        return [
            // [input, expected Base32 (no padding)]
            'empty string'  => ['', ''],
            '"f"'           => ['f', 'MY'],
            '"fo"'          => ['fo', 'MZXQ'],
            '"foo"'         => ['foo', 'MZXW6'],
            '"foob"'        => ['foob', 'MZXW6YQ'],
            '"fooba"'       => ['fooba', 'MZXW6YTB'],
            '"foobar"'      => ['foobar', 'MZXW6YTBOI'],
        ];
    }

    #[DataProvider('encodeRfc4648Provider')]
    public function testEncodeRfc4648Vectors(string $input, string $expected): void
    {
        $b32 = new Base32();
        self::assertSame($expected, $b32->encode($input));
    }

    // -------------------------------------------------------------------------
    // decode – RFC 4648 test vectors
    // -------------------------------------------------------------------------

    public static function decodeRfc4648Provider(): array
    {
        return [
            // [Base32 input, expected decoded string]
            'empty string'  => ['', ''],
            '"MY"'          => ['MY', 'f'],
            '"MZXQ"'        => ['MZXQ', 'fo'],
            '"MZXW6"'       => ['MZXW6', 'foo'],
            '"MZXW6YQ"'     => ['MZXW6YQ', 'foob'],
            '"MZXW6YTB"'    => ['MZXW6YTB', 'fooba'],
            '"MZXW6YTBOI"'  => ['MZXW6YTBOI', 'foobar'],
        ];
    }

    #[DataProvider('decodeRfc4648Provider')]
    public function testDecodeRfc4648Vectors(string $input, string $expected): void
    {
        $b32 = new Base32();
        self::assertSame($expected, $b32->decode($input));
    }

    // -------------------------------------------------------------------------
    // decode – lowercase input is accepted (normalised internally)
    // -------------------------------------------------------------------------

    public function testDecodeLowercaseInputIsNormalised(): void
    {
        $b32 = new Base32();
        self::assertSame('foo', $b32->decode('mzxw6'));
    }

    public function testDecodeMixedCaseInputIsNormalised(): void
    {
        $b32 = new Base32();
        self::assertSame('foobar', $b32->decode('MzXw6YtBoI'));
    }

    // -------------------------------------------------------------------------
    // encode → decode round-trip
    // -------------------------------------------------------------------------

    public static function roundTripProvider(): array
    {
        return [
            'ascii word'        => ['hello'],
            'sentence'          => ['Hello, World!'],
            'digits'            => ['1234567890'],
            'single byte'       => ["\x00"],
            'high-byte string'  => ["\xff\xfe\xfd"],
            'long string'       => [str_repeat('abcdefghij', 10)],
        ];
    }

    #[DataProvider('roundTripProvider')]
    public function testEncodeDecodeRoundTrip(string $plaintext): void
    {
        $b32 = new Base32();
        self::assertSame($plaintext, $b32->decode($b32->encode($plaintext)));
    }

    // -------------------------------------------------------------------------
    // encode – output uses only valid Base32 alphabet characters
    // -------------------------------------------------------------------------

    public function testEncodeOutputUsesOnlyValidCharacters(): void
    {
        $b32 = new Base32();
        $encoded = $b32->encode('The quick brown fox jumps over the lazy dog.');
        self::assertMatchesRegularExpression('/^[A-Z2-7]*$/', $encoded);
    }

    // -------------------------------------------------------------------------
    // empty-string edge cases
    // -------------------------------------------------------------------------

    public function testEncodeEmptyStringReturnsEmptyString(): void
    {
        $b32 = new Base32();
        self::assertSame('', $b32->encode(''));
    }

    public function testDecodeEmptyStringReturnsEmptyString(): void
    {
        $b32 = new Base32();
        self::assertSame('', $b32->decode(''));
    }

    // -------------------------------------------------------------------------
    // decode – invalid characters throw an exception
    // -------------------------------------------------------------------------

    public function testDecodeInvalidCharacterThrowsException(): void
    {
        $b32 = new Base32();
        $this->expectException(\Exception::class);
        // '1', '8', '9', '0' are not in the Base32 alphabet
        $b32->decode('MZXW6!@#');
    }

    public function testDecodeDigitOneThrowsException(): void
    {
        $b32 = new Base32();
        $this->expectException(\Exception::class);
        // '1' is not in the RFC 4648 Base32 alphabet (only 2–7 are valid digits)
        $b32->decode('1ZZZZZZZ');
    }

    // -------------------------------------------------------------------------
    // Constant – verify the character set
    // -------------------------------------------------------------------------

    public function testCharacterSetConstant(): void
    {
        self::assertSame('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', Base32::CSRFC3548);
        self::assertSame(32, strlen(Base32::CSRFC3548));
    }
}
