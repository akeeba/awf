<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\Utf8;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Awf\Utils\Utf8 ISO-8859-1 <-> UTF-8 conversion helpers.
 *
 * On PHP 8.2+ the class no longer delegates to the (removed) native
 * utf8_encode()/utf8_decode() functions. Instead it converts using the first
 * available backend in this order: mb_convert_encoding(), UConverter, iconv(),
 * and finally a pure-PHP fallback. The expected results below describe the
 * canonical ISO-8859-1 <-> UTF-8 mapping, which all backends agree on.
 */
class Utf8Test extends TestCase
{
    // -------------------------------------------------------------------------
    // utf8_encode (ISO-8859-1 -> UTF-8)
    // -------------------------------------------------------------------------

    public static function encodeProvider(): array
    {
        return [
            // Pure ASCII is unchanged.
            'empty string'      => ['', ''],
            'plain ASCII'       => ['Hello', 'Hello'],
            'ASCII punctuation' => ['a-z_0.9!', 'a-z_0.9!'],

            // Latin-1 high bytes (0x80-0xFF) become two-byte UTF-8 sequences.
            // é = 0xE9  -> 0xC3 0xA9
            'accented e'        => ["Caf\xE9", "Caf\xC3\xA9"],
            // ä ö ü = 0xE4 0xF6 0xFC
            'german umlauts'    => ["\xE4\xF6\xFC", "\xC3\xA4\xC3\xB6\xC3\xBC"],
            // À = 0xC0 -> 0xC3 0x80 (the 0x80-0xBF / 0xC0+ branch boundary)
            'capital a grave'   => ["\xC0", "\xC3\x80"],
            // copyright sign © = 0xA9 -> 0xC2 0xA9 (the < 0xC0 branch)
            'copyright sign'    => ["\xA9", "\xC2\xA9"],
            // 0x7F is the last single-byte value.
            'del control 0x7F'  => ["\x7F", "\x7F"],
            // 0x80 is the first value needing the 0xC2 prefix.
            'first high byte'   => ["\x80", "\xC2\x80"],
            // 0xFF (ÿ) -> 0xC3 0xBF
            'last high byte'    => ["\xFF", "\xC3\xBF"],
            // Mixed ASCII + high bytes.
            'mixed content'     => ["a\xE9b", "a\xC3\xA9b"],
        ];
    }

    #[DataProvider('encodeProvider')]
    public function testUtf8Encode(string $input, string $expected): void
    {
        $this->assertSame($expected, Utf8::utf8_encode($input));
    }

    public function testUtf8EncodeProducesValidUtf8(): void
    {
        $encoded = Utf8::utf8_encode("\xE9\xE8\xEA"); // é è ê

        $this->assertSame("\xC3\xA9\xC3\xA8\xC3\xAA", $encoded);
        // The result must be well-formed UTF-8.
        $this->assertTrue((bool) preg_match('//u', $encoded));
    }

    // -------------------------------------------------------------------------
    // utf8_decode (UTF-8 -> ISO-8859-1)
    // -------------------------------------------------------------------------

    public static function decodeProvider(): array
    {
        return [
            // Pure ASCII is unchanged.
            'empty string'      => ['', ''],
            'plain ASCII'       => ['Hello', 'Hello'],

            // Two-byte sequences that map into Latin-1.
            // é UTF-8 0xC3 0xA9 -> 0xE9
            'accented e'        => ["Caf\xC3\xA9", "Caf\xE9"],
            'german umlauts'    => ["\xC3\xA4\xC3\xB6\xC3\xBC", "\xE4\xF6\xFC"],
            'copyright sign'    => ["\xC2\xA9", "\xA9"],
            'capital a grave'   => ["\xC3\x80", "\xC0"],
            'last latin1 byte'  => ["\xC3\xBF", "\xFF"],
            'mixed content'     => ["a\xC3\xA9b", "a\xE9b"],
        ];
    }

    #[DataProvider('decodeProvider')]
    public function testUtf8Decode(string $input, string $expected): void
    {
        $this->assertSame($expected, Utf8::utf8_decode($input));
    }

    /**
     * Characters that fall outside ISO-8859-1 (CJK, emoji, etc.) cannot be
     * represented and are replaced with a substitution character ('?').
     */
    public static function decodeUnrepresentableProvider(): array
    {
        return [
            // 日 (CJK ideograph) = three-byte 0xE6 0x97 0xA5
            'single CJK char'   => ["\xE6\x97\xA5"],
            // grinning face emoji = four-byte 0xF0 0x9F 0x98 0x80
            'single emoji'      => ["\xF0\x9F\x98\x80"],
            // € (euro sign) = three-byte 0xE2 0x82 0xAC
            'euro sign'         => ["\xE2\x82\xAC"],
        ];
    }

    #[DataProvider('decodeUnrepresentableProvider')]
    public function testUtf8DecodeReplacesUnrepresentableCharacters(string $input): void
    {
        $decoded = Utf8::utf8_decode($input);

        // Each unrepresentable character collapses to a single '?'.
        $this->assertSame('?', $decoded);
    }

    public function testUtf8DecodePreservesAsciiAroundUnrepresentable(): void
    {
        // "A" + 日 + "B"
        $decoded = Utf8::utf8_decode("A\xE6\x97\xA5B");

        $this->assertSame('A?B', $decoded);
    }

    // -------------------------------------------------------------------------
    // Round trips
    // -------------------------------------------------------------------------

    public function testEncodeThenDecodeRoundTripForLatin1(): void
    {
        // Every byte 0x00-0xFF is a valid ISO-8859-1 character. Encoding to
        // UTF-8 and decoding back must reproduce the original byte string.
        $latin1 = '';

        for ($i = 0; $i <= 255; $i++)
        {
            $latin1 .= chr($i);
        }

        $roundTripped = Utf8::utf8_decode(Utf8::utf8_encode($latin1));

        $this->assertSame($latin1, $roundTripped);
    }

    public function testDecodeThenEncodeRoundTripForRepresentableUtf8(): void
    {
        // UTF-8 string whose characters all live in the Latin-1 range.
        $utf8 = "Caf\xC3\xA9 \xC3\xA0 la \xC2\xBD"; // "Café à la ½"

        $roundTripped = Utf8::utf8_encode(Utf8::utf8_decode($utf8));

        $this->assertSame($utf8, $roundTripped);
    }
}
