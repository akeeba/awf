<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Uri;

use Awf\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Uri\Uri — parsing and accessors.
 *
 * Covers: construction, parse(), getScheme(), getUser(), getPass(), getHost(),
 * getPort(), getPath(), getFragment(), getQuery(), getVar(), hasVar(),
 * isSSL(), toString(), and static getInstance() / reset().
 */
class UriParsingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // setUp / tearDown — reset the singleton cache between tests
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        Uri::reset();
    }

    protected function tearDown(): void
    {
        Uri::reset();
    }

    // -------------------------------------------------------------------------
    // parse() — scheme
    // -------------------------------------------------------------------------

    public static function schemeProvider(): array
    {
        return [
            'http scheme'       => ['http://www.example.com/', 'http'],
            'https scheme'      => ['https://www.example.com/', 'https'],
            'ftp scheme'        => ['ftp://ftp.example.com/', 'ftp'],
            'mailto scheme'     => ['mailto:user@example.com', 'mailto'],
            'no scheme'         => ['/path/to/page', null],
            'relative no host'  => ['path/to/page', null],
        ];
    }

    #[DataProvider('schemeProvider')]
    public function testGetScheme(string $url, ?string $expected): void
    {
        $uri = new Uri($url);
        self::assertSame($expected, $uri->getScheme());
    }

    // -------------------------------------------------------------------------
    // parse() — host
    // -------------------------------------------------------------------------

    public static function hostProvider(): array
    {
        return [
            'simple host'          => ['http://www.example.com/', 'www.example.com'],
            'host with port'       => ['http://www.example.com:8080/', 'www.example.com'],
            'host with user/pass'  => ['http://user:pass@host.example.com/', 'host.example.com'],
            'IPv4 host'            => ['http://192.168.1.1/', '192.168.1.1'],
            'path only — no host'  => ['/path/only', null],
        ];
    }

    #[DataProvider('hostProvider')]
    public function testGetHost(string $url, ?string $expected): void
    {
        $uri = new Uri($url);
        self::assertSame($expected, $uri->getHost());
    }

    // -------------------------------------------------------------------------
    // parse() — port
    // -------------------------------------------------------------------------

    public static function portProvider(): array
    {
        return [
            // Note: Uri::parse_url() decodes all parts as strings, so port is a string, not int.
            'explicit port 8080'   => ['http://www.example.com:8080/', '8080'],
            'explicit port 443'    => ['https://www.example.com:443/', '443'],
            'no port in URL'       => ['http://www.example.com/', null],
            'path only'            => ['/path/only', null],
        ];
    }

    #[DataProvider('portProvider')]
    public function testGetPort(string $url, ?string $expected): void
    {
        $uri = new Uri($url);
        self::assertSame($expected, $uri->getPort());
    }

    // -------------------------------------------------------------------------
    // parse() — user & pass
    // -------------------------------------------------------------------------

    public static function userPassProvider(): array
    {
        return [
            'user and pass'    => ['http://alice:secret@example.com/', 'alice', 'secret'],
            'user only'        => ['http://alice@example.com/', 'alice', null],
            'no credentials'   => ['http://example.com/', null, null],
        ];
    }

    #[DataProvider('userPassProvider')]
    public function testGetUserAndPass(string $url, ?string $expectedUser, ?string $expectedPass): void
    {
        $uri = new Uri($url);
        self::assertSame($expectedUser, $uri->getUser());
        self::assertSame($expectedPass, $uri->getPass());
    }

    // -------------------------------------------------------------------------
    // parse() — path
    // -------------------------------------------------------------------------

    public static function pathProvider(): array
    {
        return [
            'root path'            => ['http://www.example.com/', '/'],
            'deep path'            => ['http://www.example.com/a/b/c', '/a/b/c'],
            'path with query'      => ['http://www.example.com/search?q=foo', '/search'],
            'path with fragment'   => ['http://www.example.com/page#sec', '/page'],
            'relative path only'   => ['path/to/file', 'path/to/file'],
            'absolute path only'   => ['/path/to/file', '/path/to/file'],
            'no path at all'       => ['http://www.example.com', null],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testGetPath(string $url, ?string $expected): void
    {
        $uri = new Uri($url);
        self::assertSame($expected, $uri->getPath());
    }

    // -------------------------------------------------------------------------
    // parse() — fragment
    // -------------------------------------------------------------------------

    public static function fragmentProvider(): array
    {
        return [
            'simple fragment'      => ['http://www.example.com/page#section1', 'section1'],
            'fragment with dash'   => ['http://www.example.com/page#my-section', 'my-section'],
            'no fragment'          => ['http://www.example.com/page', null],
            // PHP parse_url returns '' for an empty fragment (trailing '#'), not null
            'empty after hash'     => ['http://www.example.com/page#', ''],
        ];
    }

    #[DataProvider('fragmentProvider')]
    public function testGetFragment(string $url, ?string $expected): void
    {
        $uri = new Uri($url);
        self::assertSame($expected, $uri->getFragment());
    }

    // -------------------------------------------------------------------------
    // parse() — query string and getVar()
    // -------------------------------------------------------------------------

    public static function queryStringProvider(): array
    {
        return [
            'single var'            => ['http://example.com/?foo=bar', 'foo=bar'],
            'multiple vars'         => ['http://example.com/?a=1&b=2', 'a=1&b=2'],
            'no query'              => ['http://example.com/', null],
            'amp-encoded query'     => ['http://example.com/?a=1&amp;b=2', 'a=1&b=2'],
        ];
    }

    #[DataProvider('queryStringProvider')]
    public function testGetQuery(string $url, ?string $expected): void
    {
        $uri = new Uri($url);
        // getQuery() returns false when no vars and no query was set,
        // but null is stored; normalise for comparison.
        $result = $uri->getQuery();
        if ($expected === null) {
            // Either null or empty-string/false counts as "no query"
            self::assertEmpty($result);
        } else {
            self::assertSame($expected, $result);
        }
    }

    public function testGetQueryAsArray(): void
    {
        $uri = new Uri('http://example.com/?foo=bar&baz=qux');
        $arr = $uri->getQuery(true);
        self::assertIsArray($arr);
        self::assertSame('bar', $arr['foo']);
        self::assertSame('qux', $arr['baz']);
    }

    public function testGetVarReturnsValue(): void
    {
        $uri = new Uri('http://example.com/?name=world&page=2');
        self::assertSame('world', $uri->getVar('name'));
        self::assertSame('2', $uri->getVar('page'));
    }

    public function testGetVarReturnsDefaultWhenMissing(): void
    {
        $uri = new Uri('http://example.com/?name=world');
        self::assertNull($uri->getVar('missing'));
        self::assertSame('fallback', $uri->getVar('missing', 'fallback'));
    }

    public function testHasVar(): void
    {
        $uri = new Uri('http://example.com/?present=1');
        self::assertTrue($uri->hasVar('present'));
        self::assertFalse($uri->hasVar('absent'));
    }

    public function testAmpersandEntitiesAreNormalisedInQueryVars(): void
    {
        $uri = new Uri('http://example.com/?a=1&amp;b=2');
        self::assertSame('1', $uri->getVar('a'));
        self::assertSame('2', $uri->getVar('b'));
    }

    // -------------------------------------------------------------------------
    // isSSL()
    // -------------------------------------------------------------------------

    public function testIsSSLReturnsTrueForHttps(): void
    {
        $uri = new Uri('https://secure.example.com/');
        self::assertTrue($uri->isSSL());
    }

    public function testIsSSLReturnsFalseForHttp(): void
    {
        $uri = new Uri('http://insecure.example.com/');
        self::assertFalse($uri->isSSL());
    }

    public function testIsSSLReturnsFalseForNoScheme(): void
    {
        $uri = new Uri('/path/only');
        self::assertFalse($uri->isSSL());
    }

    // -------------------------------------------------------------------------
    // toString()
    // -------------------------------------------------------------------------

    public function testToStringReconstructsFullUrl(): void
    {
        $url = 'http://www.example.com/path/to/page?foo=bar#section';
        $uri = new Uri($url);
        self::assertSame($url, $uri->toString());
    }

    public function testToStringWithCredentialsAndPort(): void
    {
        $url = 'http://alice:secret@example.com:8080/path';
        $uri = new Uri($url);
        self::assertSame($url, $uri->toString());
    }

    public function testToStringSelectiveParts(): void
    {
        $uri = new Uri('http://www.example.com:8080/path?foo=bar#frag');
        self::assertSame('http://www.example.com:8080', $uri->toString(['scheme', 'host', 'port']));
        self::assertSame('/path', $uri->toString(['path']));
        self::assertSame('?foo=bar', $uri->toString(['query']));
        self::assertSame('#frag', $uri->toString(['fragment']));
    }

    public function testMagicToString(): void
    {
        $url = 'https://example.com/hello?world=1';
        $uri = new Uri($url);
        self::assertSame($url, (string) $uri);
    }

    // -------------------------------------------------------------------------
    // Constructor with null / empty
    // -------------------------------------------------------------------------

    public function testConstructorWithNullDoesNotParse(): void
    {
        $uri = new Uri();
        self::assertNull($uri->getScheme());
        self::assertNull($uri->getHost());
        self::assertNull($uri->getPath());
    }

    public function testConstructorWithEmptyString(): void
    {
        $uri = new Uri('');
        // Empty string results in all-null components
        self::assertNull($uri->getScheme());
        self::assertNull($uri->getHost());
    }

    // -------------------------------------------------------------------------
    // static getInstance() and reset()
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsSameObjectForSameKey(): void
    {
        $a = Uri::getInstance('http://example.com/');
        $b = Uri::getInstance('http://example.com/');
        self::assertSame($a, $b, 'getInstance() must return the cached object for the same URI');
    }

    public function testGetInstanceReturnsDifferentObjectsForDifferentKeys(): void
    {
        $a = Uri::getInstance('http://example.com/');
        $b = Uri::getInstance('http://other.com/');
        self::assertNotSame($a, $b);
    }

    public function testResetClearsInstanceCache(): void
    {
        $a = Uri::getInstance('http://example.com/');
        Uri::reset();
        $b = Uri::getInstance('http://example.com/');
        self::assertNotSame($a, $b, 'After reset(), getInstance() must create a fresh object');
    }

    public function testGetInstanceParsesProvidedUri(): void
    {
        $uri = Uri::getInstance('https://test.example.com:9000/foo?x=1#bar');
        self::assertSame('https', $uri->getScheme());
        self::assertSame('test.example.com', $uri->getHost());
        // Port is decoded as a string by Uri::parse_url()
        self::assertSame('9000', $uri->getPort());
        self::assertSame('/foo', $uri->getPath());
        self::assertSame('1', $uri->getVar('x'));
        self::assertSame('bar', $uri->getFragment());
    }

    // -------------------------------------------------------------------------
    // parse() return value
    // -------------------------------------------------------------------------

    public function testParseReturnsTrueOnValidUri(): void
    {
        $uri = new Uri();
        $result = $uri->parse('http://www.example.com/');
        self::assertTrue($result);
    }

    public function testParseReturnsTrueOnEmptyString(): void
    {
        $uri = new Uri();
        // PHP parse_url('') returns ['path' => ''] — a non-empty array — so parse() returns true.
        $result = $uri->parse('');
        self::assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Full-URL edge cases
    // -------------------------------------------------------------------------

    public static function fullUrlRoundTripProvider(): array
    {
        return [
            'simple http'         => ['http://example.com/'],
            'https with path'     => ['https://example.com/path/to/page'],
            'with query and frag' => ['http://example.com/page?a=1&b=2#top'],
            'with port'           => ['http://example.com:8080/'],
            'with credentials'    => ['http://user:pass@example.com/'],
        ];
    }

    #[DataProvider('fullUrlRoundTripProvider')]
    public function testRoundTripToString(string $url): void
    {
        $uri = new Uri($url);
        self::assertSame($url, $uri->toString());
    }
}
