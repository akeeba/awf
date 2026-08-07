<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Uri;

use Awf\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Uri\Uri — building and manipulation (setters, mutators, toString round-trip,
 * isInternal(), buildQuery(), setQuery(), setVar(), delVar()).
 */
class UriBuildingTest extends TestCase
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
    // setScheme()
    // -------------------------------------------------------------------------

    public function testSetSchemeChangesScheme(): void
    {
        $uri = new Uri('http://example.com/path');
        $uri->setScheme('https');
        self::assertSame('https', $uri->getScheme());
    }

    public function testSetSchemeReflectedInToString(): void
    {
        $uri = new Uri('http://example.com/path');
        $uri->setScheme('ftp');
        self::assertSame('ftp://example.com/path', $uri->toString());
    }

    public function testSetSchemeOnEmptyUri(): void
    {
        $uri = new Uri();
        $uri->setScheme('https');
        self::assertSame('https', $uri->getScheme());
    }

    // -------------------------------------------------------------------------
    // setHost()
    // -------------------------------------------------------------------------

    public function testSetHostChangesHost(): void
    {
        $uri = new Uri('http://old.example.com/');
        $uri->setHost('new.example.com');
        self::assertSame('new.example.com', $uri->getHost());
    }

    public function testSetHostReflectedInToString(): void
    {
        $uri = new Uri('http://old.example.com/path?q=1');
        $uri->setHost('new.example.com');
        self::assertSame('http://new.example.com/path?q=1', $uri->toString());
    }

    // -------------------------------------------------------------------------
    // setPort()
    // -------------------------------------------------------------------------

    public function testSetPortChangesPort(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setPort(9090);
        self::assertSame(9090, $uri->getPort());
    }

    public function testSetPortReflectedInToString(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setPort(8080);
        self::assertSame('http://example.com:8080/', $uri->toString());
    }

    public function testSetPortToNullRemovesPort(): void
    {
        $uri = new Uri('http://example.com:8080/');
        $uri->setPort(null);
        // null port should not appear in toString
        self::assertStringNotContainsString(':', $uri->toString(['port']));
    }

    // -------------------------------------------------------------------------
    // setUser() / setPass()
    // -------------------------------------------------------------------------

    public function testSetUserChangesUser(): void
    {
        $uri = new Uri('http://alice@example.com/');
        $uri->setUser('bob');
        self::assertSame('bob', $uri->getUser());
    }

    public function testSetPassChangesPass(): void
    {
        $uri = new Uri('http://alice:secret@example.com/');
        $uri->setPass('newpass');
        self::assertSame('newpass', $uri->getPass());
    }

    // -------------------------------------------------------------------------
    // setPath()
    // -------------------------------------------------------------------------

    public function testSetPathChangesPath(): void
    {
        $uri = new Uri('http://example.com/old/path');
        $uri->setPath('/new/path');
        self::assertSame('/new/path', $uri->getPath());
    }

    public function testSetPathReflectedInToString(): void
    {
        $uri = new Uri('http://example.com/old');
        $uri->setPath('/new');
        self::assertSame('http://example.com/new', $uri->toString());
    }

    public function testSetPathNormalisesDoubleDots(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setPath('/foo/bar/../baz');
        self::assertSame('/foo/baz', $uri->getPath());
    }

    public function testSetPathNormalisesDoubleSlashes(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setPath('/foo//bar');
        self::assertSame('/foo/bar', $uri->getPath());
    }

    // -------------------------------------------------------------------------
    // setFragment()
    // -------------------------------------------------------------------------

    public function testSetFragmentChangesFragment(): void
    {
        $uri = new Uri('http://example.com/page#old');
        $uri->setFragment('new-section');
        self::assertSame('new-section', $uri->getFragment());
    }

    public function testSetFragmentReflectedInToString(): void
    {
        $uri = new Uri('http://example.com/page');
        $uri->setFragment('section2');
        self::assertSame('http://example.com/page#section2', $uri->toString());
    }

    public function testSetFragmentNullRemovesFragment(): void
    {
        $uri = new Uri('http://example.com/page#old');
        $uri->setFragment(null);
        self::assertStringNotContainsString('#', $uri->toString());
    }

    // -------------------------------------------------------------------------
    // setVar()
    // -------------------------------------------------------------------------

    public function testSetVarAddsNewVar(): void
    {
        $uri = new Uri('http://example.com/?a=1');
        $uri->setVar('b', '2');
        self::assertSame('2', $uri->getVar('b'));
    }

    public function testSetVarReplacesExistingVar(): void
    {
        $uri = new Uri('http://example.com/?foo=old');
        $old = $uri->setVar('foo', 'new');
        self::assertSame('old', $old, 'setVar() must return the previous value');
        self::assertSame('new', $uri->getVar('foo'));
    }

    public function testSetVarReturnsPreviousValue(): void
    {
        $uri = new Uri('http://example.com/?key=before');
        $previous = $uri->setVar('key', 'after');
        self::assertSame('before', $previous);
    }

    public function testSetVarReturnsNullWhenKeyDoesNotExist(): void
    {
        $uri = new Uri('http://example.com/');
        $previous = $uri->setVar('brand_new', 'value');
        self::assertNull($previous);
    }

    public function testSetVarWithNullRemovesVar(): void
    {
        $uri = new Uri('http://example.com/?foo=bar');
        $uri->setVar('foo', null);
        self::assertFalse($uri->hasVar('foo'));
    }

    public function testSetVarWithNullReturnsOldValueBeforeRemoval(): void
    {
        $uri = new Uri('http://example.com/?foo=bar');
        $old = $uri->setVar('foo', null);
        self::assertSame('bar', $old);
    }

    public function testSetVarWithNullOnMissingKeyReturnsNull(): void
    {
        $uri = new Uri('http://example.com/');
        $result = $uri->setVar('nonexistent', null);
        self::assertNull($result);
    }

    public function testSetVarInvalidatesQueryCache(): void
    {
        $uri = new Uri('http://example.com/?a=1');
        // Trigger query caching
        $uri->getQuery();
        $uri->setVar('b', '2');
        // Query should now be rebuilt and include 'b'
        self::assertStringContainsString('b=2', $uri->getQuery());
    }

    public function testSetVarReflectedInToString(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setVar('view', 'list');
        self::assertStringContainsString('view=list', $uri->toString());
    }

    // -------------------------------------------------------------------------
    // delVar()
    // -------------------------------------------------------------------------

    public function testDelVarRemovesExistingVar(): void
    {
        $uri = new Uri('http://example.com/?a=1&b=2');
        $uri->delVar('a');
        self::assertFalse($uri->hasVar('a'));
        self::assertTrue($uri->hasVar('b'));
    }

    public function testDelVarOnMissingKeyIsNoOp(): void
    {
        $uri = new Uri('http://example.com/?a=1');
        // Should not throw; state unchanged
        $uri->delVar('nonexistent');
        self::assertTrue($uri->hasVar('a'));
    }

    public function testDelVarInvalidatesQueryCache(): void
    {
        $uri = new Uri('http://example.com/?a=1&b=2');
        // Populate query cache
        $uri->getQuery();
        $uri->delVar('b');
        self::assertStringNotContainsString('b=2', $uri->getQuery());
    }

    public function testDelVarReflectedInToString(): void
    {
        $uri = new Uri('http://example.com/?keep=yes&remove=me');
        $uri->delVar('remove');
        $result = $uri->toString();
        self::assertStringContainsString('keep=yes', $result);
        self::assertStringNotContainsString('remove=me', $result);
    }

    // -------------------------------------------------------------------------
    // setQuery()
    // -------------------------------------------------------------------------

    public function testSetQueryFromString(): void
    {
        $uri = new Uri('http://example.com/?old=stuff');
        $uri->setQuery('x=10&y=20');
        self::assertSame('10', $uri->getVar('x'));
        self::assertSame('20', $uri->getVar('y'));
        self::assertFalse($uri->hasVar('old'));
    }

    public function testSetQueryFromArray(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setQuery(['foo' => 'bar', 'baz' => 'qux']);
        self::assertSame('bar', $uri->getVar('foo'));
        self::assertSame('qux', $uri->getVar('baz'));
    }

    public function testSetQueryNormalisesAmpersandEntities(): void
    {
        $uri = new Uri('http://example.com/');
        $uri->setQuery('a=1&amp;b=2');
        self::assertSame('1', $uri->getVar('a'));
        self::assertSame('2', $uri->getVar('b'));
    }

    public function testSetQueryInvalidatesQueryCache(): void
    {
        $uri = new Uri('http://example.com/?orig=value');
        // Populate cache
        $uri->getQuery();
        $uri->setQuery('fresh=new');
        self::assertStringContainsString('fresh=new', $uri->getQuery());
        self::assertStringNotContainsString('orig=value', $uri->getQuery());
    }

    // -------------------------------------------------------------------------
    // getQuery()
    // -------------------------------------------------------------------------

    public function testGetQueryReturnsFlatString(): void
    {
        $uri = new Uri('http://example.com/?a=1&b=2');
        $q = $uri->getQuery();
        self::assertSame('a=1&b=2', $q);
    }

    public function testGetQueryReturnsArray(): void
    {
        $uri = new Uri('http://example.com/?a=1&b=2');
        $arr = $uri->getQuery(true);
        self::assertSame(['a' => '1', 'b' => '2'], $arr);
    }

    public function testGetQueryReturnsFalseWhenEmpty(): void
    {
        $uri = new Uri('http://example.com/');
        // buildQuery() returns false for empty array
        self::assertFalse($uri->getQuery());
    }

    // -------------------------------------------------------------------------
    // buildQuery()
    // -------------------------------------------------------------------------

    public function testBuildQueryProducesQueryString(): void
    {
        $result = Uri::buildQuery(['foo' => 'bar', 'baz' => 'qux']);
        self::assertSame('foo=bar&baz=qux', $result);
    }

    public function testBuildQueryReturnsFalseForEmptyArray(): void
    {
        self::assertFalse(Uri::buildQuery([]));
    }

    public function testBuildQueryDecodesUrlEncodedValues(): void
    {
        // urldecode() is applied; spaces in values should come out as spaces, not '+'
        $result = Uri::buildQuery(['q' => 'hello world']);
        self::assertSame('q=hello world', $result);
    }

    public function testBuildQueryWithNumericKeys(): void
    {
        $result = Uri::buildQuery(['key' => '1']);
        self::assertSame('key=1', $result);
    }

    // -------------------------------------------------------------------------
    // toString() round-trip after mutation
    // -------------------------------------------------------------------------

    public static function mutationRoundTripProvider(): array
    {
        return [
            'change scheme only' => [
                'http://example.com/path?q=1#frag',
                static function (Uri $uri): void {
                    $uri->setScheme('https');
                },
                'https://example.com/path?q=1#frag',
            ],
            'change host only' => [
                'http://old.example.com/path',
                static function (Uri $uri): void {
                    $uri->setHost('new.example.com');
                },
                'http://new.example.com/path',
            ],
            'add query var' => [
                'http://example.com/page',
                static function (Uri $uri): void {
                    $uri->setVar('view', 'report');
                },
                'http://example.com/page?view=report',
            ],
            'remove query var' => [
                'http://example.com/page?a=1&b=2',
                static function (Uri $uri): void {
                    $uri->delVar('b');
                },
                'http://example.com/page?a=1',
            ],
            'replace query var' => [
                'http://example.com/?color=red',
                static function (Uri $uri): void {
                    $uri->setVar('color', 'blue');
                },
                'http://example.com/?color=blue',
            ],
            'change fragment' => [
                'http://example.com/page#old',
                static function (Uri $uri): void {
                    $uri->setFragment('new');
                },
                'http://example.com/page#new',
            ],
            'change path' => [
                'http://example.com/old',
                static function (Uri $uri): void {
                    $uri->setPath('/new');
                },
                'http://example.com/new',
            ],
            'set port' => [
                'http://example.com/',
                static function (Uri $uri): void {
                    $uri->setPort(8080);
                },
                'http://example.com:8080/',
            ],
        ];
    }

    #[DataProvider('mutationRoundTripProvider')]
    public function testMutationRoundTrip(string $initial, callable $mutate, string $expected): void
    {
        $uri = new Uri($initial);
        $mutate($uri);
        self::assertSame($expected, $uri->toString());
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testMagicToStringAfterMutation(): void
    {
        $uri = new Uri('http://example.com/page?old=1');
        $uri->setVar('new', '2');
        $uri->delVar('old');
        self::assertSame('http://example.com/page?new=2', (string) $uri);
    }

    // -------------------------------------------------------------------------
    // isInternal()
    // -------------------------------------------------------------------------

    /**
     * Seed $_SERVER with a synthetic request so Uri::getInstance('SERVER')
     * builds the right base URL, then yield a callable that restores the
     * original values. The caller MUST invoke the returned closure (typically
     * in a finally block) so we never leak $_SERVER mutations across tests.
     *
     * @param   string  $host       Value for $_SERVER['HTTP_HOST'].
     * @param   string  $requestUri Value for $_SERVER['REQUEST_URI'].
     *
     * @return  \Closure
     */
    private function seedServer(string $host, string $requestUri = '/'): \Closure
    {
        $origHttpHost   = $_SERVER['HTTP_HOST']   ?? null;
        $origPhpSelf    = $_SERVER['PHP_SELF']    ?? null;
        $origRequestUri = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['HTTP_HOST']   = $host;
        $_SERVER['PHP_SELF']    = '/index.php';
        $_SERVER['REQUEST_URI'] = $requestUri;

        return static function () use ($origHttpHost, $origPhpSelf, $origRequestUri): void {
            if ($origHttpHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $origHttpHost;
            }
            if ($origPhpSelf === null) {
                unset($_SERVER['PHP_SELF']);
            } else {
                $_SERVER['PHP_SELF'] = $origPhpSelf;
            }
            if ($origRequestUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $origRequestUri;
            }
        };
    }

    public function testIsInternalReturnsTrueForSameHost(): void
    {
        $restore = $this->seedServer('example.com', '/');

        try {
            self::assertTrue(Uri::isInternal('http://example.com/admin'));
            self::assertTrue(Uri::isInternal('http://example.com'));
            // Same scheme+host, different (non-default) port — still our site.
            self::assertTrue(Uri::isInternal('http://example.com:8080/x'));
        } finally {
            $restore();
        }
    }

    public function testIsInternalReturnsFalseForDifferentHost(): void
    {
        $restore = $this->seedServer('example.com', '/');

        try {
            self::assertFalse(Uri::isInternal('http://attacker.com/path'));
            self::assertFalse(Uri::isInternal('http://example.org/path'));
            // HTTPS-downgrade guard: a different scheme on the same host must
            // be rejected, otherwise an attacker who controls a "return URL"
            // parameter could silently strip TLS off the user's redirect
            // target. This is a load-bearing security guarantee — do not relax.
            self::assertFalse(Uri::isInternal('https://example.com/admin'));
        } finally {
            $restore();
        }
    }

    /**
     * Same-host / different-scheme case from the opposite direction: an
     * http://example.com return URL must be rejected when the site is served
     * over HTTPS. Pair with testIsInternalReturnsFalseForDifferentHost() —
     * together they pin both directions of the downgrade guard.
     */
    public function testIsInternalRejectsHttpDowngradeFromHttpsSite(): void
    {
        $restore = $this->seedServer('example.com', '/');
        // Force the SERVER instance to advertise HTTPS by setting $_SERVER['HTTPS'].
        $origHttps = $_SERVER['HTTPS'] ?? null;
        $_SERVER['HTTPS'] = 'on';

        try {
            self::assertFalse(Uri::isInternal('http://example.com/admin'));
            // Same scheme — must still be accepted (sanity check the harness).
            self::assertTrue(Uri::isInternal('https://example.com/admin'));
        } finally {
            if ($origHttps === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $origHttps;
            }
            $restore();
        }
    }

    public function testIsInternalRejectsPrefixAttack(): void
    {
        // Regression test for the open-redirect bug: isInternal() used a bare
        // stripos() prefix match, so a URL like http://example.com.evil.tld/x
        // was treated as internal when the site is http://example.com.
        $restore = $this->seedServer('example.com', '/');

        try {
            self::assertFalse(Uri::isInternal('http://example.com.evil.tld/x'));
            self::assertFalse(Uri::isInternal('http://example-com.evil.tld/x'));
            self::assertFalse(Uri::isInternal('http://example.comA/x'));
        } finally {
            $restore();
        }
    }

    public function testIsInternalWithEmptyHost(): void
    {
        // A relative URL (no host) should be treated as internal.
        $restore = $this->seedServer('example.com', '/');

        try {
            self::assertTrue(Uri::isInternal('/path/to/page'));
            self::assertTrue(Uri::isInternal('index.php?foo=bar'));
        } finally {
            $restore();
        }
    }

    // -------------------------------------------------------------------------
    // parse_url() edge cases
    // -------------------------------------------------------------------------

    public function testParseUrlHandlesStandardUrl(): void
    {
        $parts = Uri::parse_url('http://www.example.com/path?q=hello#frag');
        self::assertSame('http', $parts['scheme']);
        self::assertSame('www.example.com', $parts['host']);
        self::assertSame('/path', $parts['path']);
        self::assertSame('q=hello', $parts['query']);
        self::assertSame('frag', $parts['fragment']);
    }

    public function testParseUrlHandlesSpecialCharsInQuery(): void
    {
        $parts = Uri::parse_url('http://example.com/?a=1&b=hello world');
        self::assertStringContainsString('hello world', $parts['query']);
    }

    // -------------------------------------------------------------------------
    // Chained mutations (parse → mutate → toString)
    // -------------------------------------------------------------------------

    public function testChainedMutations(): void
    {
        $uri = new Uri('http://old.example.com:80/foo?a=1&b=2#section');
        $uri->setScheme('https');
        $uri->setHost('new.example.com');
        $uri->setPort(443);
        $uri->setPath('/bar');
        $uri->setVar('c', '3');
        $uri->delVar('a');
        $uri->setFragment('top');

        $result = $uri->toString();

        self::assertStringStartsWith('https://new.example.com:443/bar?', $result);
        self::assertStringContainsString('b=2', $result);
        self::assertStringContainsString('c=3', $result);
        self::assertStringNotContainsString('a=1', $result);
        self::assertStringEndsWith('#top', $result);
    }

    public function testBuildQueryWithArrayValues(): void
    {
        // Nested arrays are supported by http_build_query
        $result = Uri::buildQuery(['arr' => ['x', 'y']]);
        self::assertNotEmpty($result);
        self::assertStringContainsString('arr', $result);
    }
}
