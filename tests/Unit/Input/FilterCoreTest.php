<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Input;

use Awf\Input\Filter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Filter::class)]
class FilterCoreTest extends TestCase
{
    private Filter $filter;

    protected function setUp(): void
    {
        $this->filter = new Filter();
    }

    // -------------------------------------------------------------------------
    // INT
    // -------------------------------------------------------------------------

    public static function intProvider(): array
    {
        return [
            'positive integer'              => ['42', 'INT', 42],
            'negative integer'              => ['-17', 'INT', -17],
            'integer alias INTEGER'         => ['99', 'INTEGER', 99],
            'zero'                          => ['0', 'INT', 0],
            'float truncated to int'        => ['3.99', 'INT', 3],
            'string with prefix text'       => ['abc-5xyz', 'INT', -5],
            'string with no digits'         => ['hello', 'INT', 0],
            'hex-looking string'            => ['0x1A', 'INT', 0],
            'large negative'                => ['-9999', 'INT', -9999],
            'SQL injection attempt'         => ["1 OR 1=1", 'INT', 1],
        ];
    }

    #[DataProvider('intProvider')]
    public function testInt(mixed $input, string $type, int $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // UINT
    // -------------------------------------------------------------------------

    public static function uintProvider(): array
    {
        return [
            'positive stays positive'       => ['42', 'UINT', 42],
            'negative becomes positive'     => ['-17', 'UINT', 17],
            'zero'                          => ['0', 'UINT', 0],
            'float truncated, unsigned'     => ['3.99', 'UINT', 3],
            'no digits returns zero'        => ['hello', 'UINT', 0],
            'SQL injection attempt'         => ["1 OR 1=1", 'UINT', 1],
        ];
    }

    #[DataProvider('uintProvider')]
    public function testUint(mixed $input, string $type, int $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // FLOAT
    // -------------------------------------------------------------------------

    public static function floatProvider(): array
    {
        return [
            'simple float'                  => ['3.14', 'FLOAT', 3.14],
            'float alias DOUBLE'            => ['2.71', 'DOUBLE', 2.71],
            'negative float'                => ['-1.5', 'FLOAT', -1.5],
            'integer string becomes float'  => ['42', 'FLOAT', 42.0],
            'no digits returns zero'        => ['abc', 'FLOAT', 0.0],
            'text around float'             => ['price: 9.99 usd', 'FLOAT', 9.99],
            'injection with float'          => ["3.14; DROP TABLE users", 'FLOAT', 3.14],
        ];
    }

    #[DataProvider('floatProvider')]
    public function testFloat(mixed $input, string $type, float $expected): void
    {
        self::assertEqualsWithDelta($expected, $this->filter->clean($input, $type), 0.0001);
    }

    // -------------------------------------------------------------------------
    // BOOLEAN
    // -------------------------------------------------------------------------

    public static function booleanProvider(): array
    {
        return [
            'truthy string'                 => ['1', 'BOOLEAN', true],
            'bool alias BOOL'               => [true, 'BOOL', true],
            'empty string is false'         => ['', 'BOOLEAN', false],
            'zero string is false'          => ['0', 'BOOLEAN', false],
            'null is false'                 => [null, 'BOOLEAN', false],
            'non-empty string is true'      => ['false', 'BOOLEAN', true],
            'integer 1 is true'             => [1, 'BOOLEAN', true],
            'integer 0 is false'            => [0, 'BOOLEAN', false],
            'array with items is true'      => [['a'], 'BOOLEAN', true],
            'empty array is false'          => [[], 'BOOLEAN', false],
        ];
    }

    #[DataProvider('booleanProvider')]
    public function testBoolean(mixed $input, string $type, bool $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // WORD
    // -------------------------------------------------------------------------

    public static function wordProvider(): array
    {
        return [
            'pure word passes through'      => ['hello', 'WORD', 'hello'],
            'underscore allowed'            => ['my_word', 'WORD', 'my_word'],
            'digits stripped'               => ['abc123', 'WORD', 'abc'],
            'spaces stripped'               => ['hello world', 'WORD', 'helloworld'],
            'special chars stripped'        => ['<script>alert(1)</script>', 'WORD', 'scriptalertscript'],
            'empty string'                  => ['', 'WORD', ''],
            'all non-word chars'            => ['123!@#', 'WORD', ''],
            'mixed case preserved'          => ['CamelCase', 'WORD', 'CamelCase'],
        ];
    }

    #[DataProvider('wordProvider')]
    public function testWord(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // ALNUM
    // -------------------------------------------------------------------------

    public static function alnumProvider(): array
    {
        return [
            'alphanumeric passes through'   => ['abc123', 'ALNUM', 'abc123'],
            'spaces stripped'               => ['hello world', 'ALNUM', 'helloworld'],
            'special chars stripped'        => ['<script>alert(1)</script>', 'ALNUM', 'scriptalert1script'],
            'underscore stripped'           => ['my_var', 'ALNUM', 'myvar'],
            'empty string'                  => ['', 'ALNUM', ''],
            'only specials become empty'    => ['!@#$%', 'ALNUM', ''],
            'SQL injection stripped'        => ["1 OR 1=1--", 'ALNUM', '1OR11'],
        ];
    }

    #[DataProvider('alnumProvider')]
    public function testAlnum(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // CMD
    // -------------------------------------------------------------------------

    public static function cmdProvider(): array
    {
        return [
            'simple cmd'                    => ['mycommand', 'CMD', 'mycommand'],
            'cmd with dots and hyphens'     => ['my-cmd.v2', 'CMD', 'my-cmd.v2'],
            'cmd with underscores'          => ['my_cmd', 'CMD', 'my_cmd'],
            'spaces stripped'               => ['my cmd', 'CMD', 'mycmd'],
            'leading dot stripped'          => ['../etc/passwd', 'CMD', 'etcpasswd'],
            'double dot traversal'          => ['../../secret', 'CMD', 'secret'],
            'script tag stripped'           => ['<script>', 'CMD', 'script'],
            'empty string'                  => ['', 'CMD', ''],
            'SQL injection stripped'        => ["1;DROP TABLE users", 'CMD', '1DROPTABLEusers'],
        ];
    }

    #[DataProvider('cmdProvider')]
    public function testCmd(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // BASE64
    // -------------------------------------------------------------------------

    public static function base64Provider(): array
    {
        return [
            'valid base64 chars'            => ['SGVsbG8=', 'BASE64', 'SGVsbG8='],
            'base64 with slashes'           => ['abc/def+ghi=', 'BASE64', 'abc/def+ghi='],
            'invalid chars stripped'        => ['abc!@#def', 'BASE64', 'abcdef'],
            'spaces stripped'               => ['abc def', 'BASE64', 'abcdef'],
            'empty string'                  => ['', 'BASE64', ''],
            'script injection stripped'     => ['<script>alert(1)</script>', 'BASE64', 'scriptalert1/script'],
        ];
    }

    #[DataProvider('base64Provider')]
    public function testBase64(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // STRING
    // -------------------------------------------------------------------------

    public static function stringProvider(): array
    {
        return [
            // plain text is left untouched
            'plain text passes through'     => ['Hello, World!', 'STRING', 'Hello, World!'],
            // <script> is blacklisted; the tag wrapper is stripped but inner text remains
            'script tags: tag stripped, content remains' => ['<script>alert(1)</script>', 'STRING', 'alert(1)'],
            // <b> is not blacklisted but default tagsMethod=0 means whitelist-mode with empty
            // allowed-tags list, so all tags are stripped and only text content survives
            'bold tag stripped in whitelist mode'   => ['<b>bold</b>', 'STRING', 'bold'],
            'empty string'                  => ['', 'STRING', ''],
            'html entities decoded'         => ['Hello &amp; World', 'STRING', 'Hello & World'],
        ];
    }

    #[DataProvider('stringProvider')]
    public function testString(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // ARRAY
    // -------------------------------------------------------------------------

    public static function arrayProvider(): array
    {
        return [
            'array passes through as array'         => [['a', 'b'], 'ARRAY', ['a', 'b']],
            'string becomes single-element array'   => ['hello', 'ARRAY', ['hello']],
            'integer becomes single-element array'  => [42, 'ARRAY', [42]],
            'null becomes empty array'              => [null, 'ARRAY', []],
            'assoc array preserved'                 => [['key' => 'val'], 'ARRAY', ['key' => 'val']],
            'empty array stays empty'               => [[], 'ARRAY', []],
        ];
    }

    #[DataProvider('arrayProvider')]
    public function testArray(mixed $input, string $type, array $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // PATH
    // -------------------------------------------------------------------------

    public static function pathProvider(): array
    {
        return [
            'simple filename'               => ['myfile.txt', 'PATH', 'myfile.txt'],
            'unix path'                     => ['path/to/file', 'PATH', 'path/to/file'],
            'windows-style path'            => ['path\\to\\file', 'PATH', 'path\\to\\file'],
            'path traversal stripped'       => ['../../etc/passwd', 'PATH', ''],
            'spaces stripped (returns empty)' => ['path with spaces', 'PATH', ''],
            'empty string'                  => ['', 'PATH', ''],
            'path with hyphens'             => ['my-dir/my-file', 'PATH', 'my-dir/my-file'],
            'path with dots in filename'    => ['dir/file.min.js', 'PATH', 'dir/file.min.js'],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testPath(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // USERNAME
    // -------------------------------------------------------------------------

    public static function usernameProvider(): array
    {
        return [
            'normal username'               => ['john_doe', 'USERNAME', 'john_doe'],
            'username with digits'          => ['user123', 'USERNAME', 'user123'],
            'null bytes stripped'           => ["user\x00name", 'USERNAME', 'username'],
            'control chars stripped'        => ["user\x1fname", 'USERNAME', 'username'],
            'angle brackets stripped'       => ['<admin>', 'USERNAME', 'admin'],
            'double quotes stripped'        => ['us"er', 'USERNAME', 'user'],
            'single quotes stripped'        => ["us'er", 'USERNAME', 'user'],
            'percent sign stripped'         => ['us%er', 'USERNAME', 'user'],
            'ampersand stripped'            => ['us&er', 'USERNAME', 'user'],
            'empty string'                  => ['', 'USERNAME', ''],
            'unicode username allowed'      => ['ユーザー', 'USERNAME', 'ユーザー'],
            'XSS attempt stripped'          => ['<script>alert</script>', 'USERNAME', 'scriptalert/script'],
        ];
    }

    #[DataProvider('usernameProvider')]
    public function testUsername(mixed $input, string $type, string $expected): void
    {
        self::assertSame($expected, $this->filter->clean($input, $type));
    }

    // -------------------------------------------------------------------------
    // RAW — passthrough, no filtering
    // -------------------------------------------------------------------------

    public function testRawPassesValueUnchanged(): void
    {
        $value = "<script>alert('xss')</script>";
        self::assertSame($value, $this->filter->clean($value, 'RAW'));
    }

    public function testRawPassesArrayUnchanged(): void
    {
        $value = ['<b>bold</b>', "javascript:void(0)"];
        self::assertSame($value, $this->filter->clean($value, 'RAW'));
    }

    // -------------------------------------------------------------------------
    // Default / fallback behaviour (unknown type treats input as string/array)
    // -------------------------------------------------------------------------

    public function testUnknownTypeWithStringFiltersXss(): void
    {
        $result = $this->filter->clean('<script>alert(1)</script>', 'UNKNOWN');
        self::assertStringNotContainsString('<script>', $result);
    }

    public function testUnknownTypeWithArrayFiltersEachElement(): void
    {
        $input  = ['good' => 'hello', 'bad' => '<script>evil</script>'];
        $result = $this->filter->clean($input, 'UNKNOWN');
        self::assertIsArray($result);
        self::assertSame('hello', $result['good']);
        self::assertStringNotContainsString('<script>', $result['bad']);
    }

    public function testUnknownTypeWithNonStringNonArrayReturnsAsIs(): void
    {
        self::assertSame(42, $this->filter->clean(42, 'UNKNOWN'));
    }

    // -------------------------------------------------------------------------
    // getInstance — singleton per signature
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsSameObjectForSameParams(): void
    {
        $a = Filter::getInstance();
        $b = Filter::getInstance();
        self::assertSame($a, $b);
    }

    public function testGetInstanceReturnsDifferentObjectForDifferentParams(): void
    {
        $a = Filter::getInstance([], [], 0, 0, 1);
        $b = Filter::getInstance(['script'], [], 1, 0, 1);
        self::assertNotSame($a, $b);
    }

    // -------------------------------------------------------------------------
    // checkAttribute — static helper
    // -------------------------------------------------------------------------

    public function testCheckAttributeDetectsJavascriptProtocol(): void
    {
        self::assertTrue(Filter::checkAttribute(['href', 'javascript:alert(1)']));
    }

    public function testCheckAttributeDetectsVbscriptProtocol(): void
    {
        self::assertTrue(Filter::checkAttribute(['href', 'vbscript:something']));
    }

    public function testCheckAttributeDetectsStyleExpression(): void
    {
        self::assertTrue(Filter::checkAttribute(['style', 'width:expression(alert(1))']));
    }

    public function testCheckAttributeAllowsNormalAttribute(): void
    {
        self::assertFalse(Filter::checkAttribute(['href', 'https://example.com']));
    }

    public function testCheckAttributeAllowsNormalClass(): void
    {
        self::assertFalse(Filter::checkAttribute(['class', 'btn btn-primary']));
    }
}
