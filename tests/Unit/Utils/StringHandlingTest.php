<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\StringHandling;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StringHandlingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // toSlug
    // -------------------------------------------------------------------------

    public static function toSlugProvider(): array
    {
        return [
            'simple two words'        => ['Hello World', 'hello-world'],
            'already hyphenated'      => ['Some-Title Here', 'some-title-here'],
            'transliterated accents'  => ['café crème', 'cafe-creme'],
            'uppercased input'        => ['UPPER CASE', 'upper-case'],
            'collapses whitespace'    => ['multiple   spaces', 'multiple-spaces'],
            'strips special chars'    => ['Hello@#World!', 'helloworld'],
            'keeps underscores'       => ['under_score', 'under_score'],
            'keeps digits'            => ['Title 123', 'title-123'],
            'leading/trailing spaces' => ['  spaced  ', 'spaced'],
            'empty string'            => ['', ''],
        ];
    }

    #[DataProvider('toSlugProvider')]
    public function testToSlug(string $input, string $expected): void
    {
        $this->assertSame($expected, StringHandling::toSlug($input));
    }

    public function testToSlugLimitsLengthTo100Characters(): void
    {
        $input  = str_repeat('a', 150);
        $result = StringHandling::toSlug($input);

        $this->assertSame(100, strlen($result));
        $this->assertSame(str_repeat('a', 100), $result);
    }

    public function testToSlugShorterThan100IsNotTruncated(): void
    {
        $input  = str_repeat('a', 50);
        $result = StringHandling::toSlug($input);

        $this->assertSame(50, strlen($result));
    }

    // -------------------------------------------------------------------------
    // toASCII
    // -------------------------------------------------------------------------

    public static function toAsciiProvider(): array
    {
        return [
            'plain ascii unchanged' => ['hello', 'hello'],
            'e acute'               => ['café', 'cafe'],
            'u umlaut → ue'         => ['Müller', 'Mueller'],
            'sharp s → ss'          => ['Größe', 'Groesse'],
            'i diaeresis'           => ['naïve', 'naive'],
            'u umlaut lowercase'    => ['über', 'ueber'],
            'empty string'          => ['', ''],
        ];
    }

    #[DataProvider('toAsciiProvider')]
    public function testToAscii(string $input, string $expected): void
    {
        $this->assertSame($expected, StringHandling::toASCII($input));
    }

    // -------------------------------------------------------------------------
    // toBool
    // -------------------------------------------------------------------------

    public static function toBoolTruthyProvider(): array
    {
        return [
            'true'               => ['true'],
            'yes'                => ['yes'],
            'on'                 => ['on'],
            'enabled'            => ['enabled'],
            'uppercase TRUE'     => ['TRUE'],
            'mixed case Yes'     => ['Yes'],
            'whitespace padded'  => [' on '],
            'string one'         => ['1'],
            // Non-keyword, non-empty strings fall through to (bool) cast → true
            'arbitrary string'   => ['random'],
            'numeric two'        => ['2'],
        ];
    }

    #[DataProvider('toBoolTruthyProvider')]
    public function testToBoolReturnsTrue(string $input): void
    {
        $this->assertTrue(StringHandling::toBool($input));
    }

    public static function toBoolFalsyProvider(): array
    {
        return [
            'false'           => ['false'],
            'no'              => ['no'],
            'off'             => ['off'],
            'disabled'        => ['disabled'],
            'uppercase FALSE' => ['FALSE'],
            'empty string'    => [''],
            'whitespace only' => ['   '],
            'string zero'     => ['0'],
        ];
    }

    #[DataProvider('toBoolFalsyProvider')]
    public function testToBoolReturnsFalse(string $input): void
    {
        $this->assertFalse(StringHandling::toBool($input));
    }

    public function testToBoolWithIntegerOne(): void
    {
        $this->assertTrue(StringHandling::toBool(1));
    }

    public function testToBoolWithIntegerZero(): void
    {
        $this->assertFalse(StringHandling::toBool(0));
    }
}
