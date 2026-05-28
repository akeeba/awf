<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\ParseIni;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParseIniTest extends TestCase
{
    /** @var string[] List of temporary files to clean up. */
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file)
        {
            if (is_file($file))
            {
                @unlink($file);
            }
        }

        $this->tmpFiles = [];
    }

    /**
     * Write INI content to a temporary file and return its path.
     */
    private function makeTempFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'awfini');

        if ($file === false)
        {
            self::fail('Could not create a temporary file for the test.');
        }

        file_put_contents($file, $contents);
        $this->tmpFiles[] = $file;

        return $file;
    }

    // -------------------------------------------------------------------------
    // Raw data parsing (the common case) — no sections
    // -------------------------------------------------------------------------

    public static function flatProvider(): array
    {
        return [
            'simple key/value pairs'      => [
                "key1 = value1\nkey2 = value2\n",
                ['key1' => 'value1', 'key2' => 'value2'],
            ],
            'double quoted value'         => [
                "key = \"hello world\"\n",
                ['key' => 'hello world'],
            ],
            'single quoted value'         => [
                "key = 'hello world'\n",
                ['key' => 'hello world'],
            ],
            'special chars not interpolated' => [
                "pass = \${foo}bar\n",
                ['pass' => '${foo}bar'],
            ],
            'whitespace around tokens trimmed' => [
                "   key   =    value   \n",
                ['key' => 'value'],
            ],
            'tabs are treated as spaces'  => [
                "key\t=\tvalue\n",
                ['key' => 'value'],
            ],
            'empty value yields empty string' => [
                "key =\n",
                ['key' => ''],
            ],
            'value containing equals sign' => [
                "key = a=b=c\n",
                ['key' => 'a=b=c'],
            ],
            'backslash sequences kept literal' => [
                'key = a\nb' . "\n",
                ['key' => 'a\nb'],
            ],
        ];
    }

    #[DataProvider('flatProvider')]
    public function testFlatRawParsing(string $ini, array $expected): void
    {
        self::assertSame($expected, ParseIni::parse_ini_file($ini, false, true));
    }

    // -------------------------------------------------------------------------
    // Comments
    // -------------------------------------------------------------------------

    public static function commentProvider(): array
    {
        return [
            'semicolon line comment' => [
                "; this is a comment\nkey = val\n",
                ['key' => 'val'],
            ],
            'hash line comment'      => [
                "# this is a comment\nkey = val\n",
                ['key' => 'val'],
            ],
            'trailing inline comment stripped' => [
                "key = value ; trailing comment\n",
                ['key' => 'value'],
            ],
            'semicolon inside double quotes kept' => [
                "key = \"value ; with semicolon\" ; trailing\n",
                ['key' => 'value ; with semicolon'],
            ],
            'semicolon inside single quotes kept' => [
                "key = 'value ; with semicolon' ; trailing\n",
                ['key' => 'value ; with semicolon'],
            ],
            'blank lines ignored'    => [
                "\n\nkey = val\n\n",
                ['key' => 'val'],
            ],
            'only comments yields empty array' => [
                "; foo\n# bar\n",
                [],
            ],
        ];
    }

    #[DataProvider('commentProvider')]
    public function testComments(string $ini, array $expected): void
    {
        self::assertSame($expected, ParseIni::parse_ini_file($ini, false, true));
    }

    // -------------------------------------------------------------------------
    // Booleans stay as raw strings (no type coercion)
    // -------------------------------------------------------------------------

    public static function booleanProvider(): array
    {
        return [
            'true stays string'  => ["key = true\n", ['key' => 'true']],
            'false stays string' => ["key = false\n", ['key' => 'false']],
            'on stays string'    => ["key = on\n", ['key' => 'on']],
            'off stays string'   => ["key = off\n", ['key' => 'off']],
            'number stays string' => ["key = 123\n", ['key' => '123']],
        ];
    }

    #[DataProvider('booleanProvider')]
    public function testBooleansAreNotCoerced(string $ini, array $expected): void
    {
        self::assertSame($expected, ParseIni::parse_ini_file($ini, false, true));
    }

    // -------------------------------------------------------------------------
    // Array values (key[] syntax)
    //
    // NOTE: This parser does NOT actually build PHP arrays from the `key[]`
    // syntax. The internal check `substr($line, -1, 2) == '[]'` only ever
    // returns the single trailing `]` character, so the array branch is never
    // taken and the `[]` is never stripped from the key. These tests pin the
    // real, observed behaviour: the literal key `key[]` is used and the last
    // assignment wins.
    // -------------------------------------------------------------------------

    public function testArrayBracketSyntaxKeepsLiteralKeyAndLastValueWins(): void
    {
        $ini = "key[] = a\nkey[] = b\n";

        self::assertSame(
            ['key[]' => 'b'],
            ParseIni::parse_ini_file($ini, false, true)
        );
    }

    public function testArrayBracketSyntaxInsideSection(): void
    {
        $ini = "[sec]\nk[] = a\nk[] = b\n";

        self::assertSame(
            ['sec' => ['k[]' => 'b']],
            ParseIni::parse_ini_file($ini, true, true)
        );
    }

    // -------------------------------------------------------------------------
    // Sections
    // -------------------------------------------------------------------------

    public function testSectionsProcessed(): void
    {
        $ini = "[sec1]\na = 1\nb = 2\n[sec2]\nc = 3\n";

        self::assertSame(
            [
                'sec1' => ['a' => '1', 'b' => '2'],
                'sec2' => ['c' => '3'],
            ],
            ParseIni::parse_ini_file($ini, true, true)
        );
    }

    public function testSectionsFlattenedWhenNotProcessed(): void
    {
        $ini = "[sec1]\na = 1\nb = 2\n[sec2]\nc = 3\n";

        self::assertSame(
            ['a' => '1', 'b' => '2', 'c' => '3'],
            ParseIni::parse_ini_file($ini, false, true)
        );
    }

    public function testGlobalsAndSectionsProcessedTogether(): void
    {
        $ini = "g = global\n[sec]\na = 1\n";

        self::assertSame(
            [
                'sec' => ['a' => '1'],
                'g'   => 'global',
            ],
            ParseIni::parse_ini_file($ini, true, true)
        );
    }

    // -------------------------------------------------------------------------
    // Empty input
    // -------------------------------------------------------------------------

    public function testEmptyStringYieldsEmptyArray(): void
    {
        self::assertSame([], ParseIni::parse_ini_file('', false, true));
    }

    // -------------------------------------------------------------------------
    // CRLF line endings are normalised
    // -------------------------------------------------------------------------

    public function testCarriageReturnsAreStripped(): void
    {
        $ini = "key1 = value1\r\nkey2 = value2\r\n";

        self::assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            ParseIni::parse_ini_file($ini, false, true)
        );
    }

    // -------------------------------------------------------------------------
    // File-based parsing (rawdata = false / default)
    // -------------------------------------------------------------------------

    public function testFileBasedParsingWithoutSections(): void
    {
        $file = $this->makeTempFile("key1 = value1\nkey2 = value2\n");

        self::assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            ParseIni::parse_ini_file($file, false)
        );
    }

    public function testFileBasedParsingWithSections(): void
    {
        $file = $this->makeTempFile("[sec]\nfoo = bar\nbaz = qux\n");

        self::assertSame(
            ['sec' => ['foo' => 'bar', 'baz' => 'qux']],
            ParseIni::parse_ini_file($file, true)
        );
    }

    public function testParseIniFilePhpAliasMatchesPublicEntryPoint(): void
    {
        $ini = "key = value\n";

        self::assertSame(
            ParseIni::parse_ini_file($ini, false, true),
            ParseIni::parse_ini_file_php($ini, false, true)
        );
    }
}
