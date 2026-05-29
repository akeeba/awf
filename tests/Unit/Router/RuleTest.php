<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Router;

use Awf\Router\Rule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Awf\Router\Rule
 *
 * Covers:
 *  - route()  — converts a non-SEF URL into segments + leftover vars
 *  - parse()  — converts a SEF path back into query parameters
 *  - Callable overrides for both directions
 *  - matchVars / pushVars semantics
 *  - path segment types: static, :var, :var?, :var*, *
 */
class RuleTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a Rule from a definition array, so we can use named-argument style.
     */
    private function makeRule(array $definition): Rule
    {
        return new Rule($definition);
    }

    // =========================================================================
    // route() — happy paths
    // =========================================================================

    public function testRouteStaticSegmentsOnly(): void
    {
        $rule = $this->makeRule(['path' => 'foo/bar']);

        $result = $rule->route('http://example.com/?x=1');

        self::assertIsArray($result);
        self::assertSame(['foo', 'bar'], $result['segments']);
        self::assertSame(['x' => '1'], $result['vars']);
    }

    public function testRouteNamedVariable(): void
    {
        $rule = $this->makeRule(['path' => 'item/:id']);

        $result = $rule->route('http://example.com/?id=42&extra=yes');

        self::assertIsArray($result);
        self::assertSame(['item', '42'], $result['segments']);
        // id is consumed; extra remains
        self::assertSame(['extra' => 'yes'], $result['vars']);
    }

    public function testRouteOptionalVariablePresentInUrl(): void
    {
        $rule = $this->makeRule(['path' => 'article/:slug?']);

        $result = $rule->route('http://example.com/?slug=hello-world');

        self::assertIsArray($result);
        self::assertSame(['article', 'hello-world'], $result['segments']);
        self::assertArrayNotHasKey('slug', $result['vars']);
    }

    public function testRouteOptionalVariableAbsentFromUrl(): void
    {
        $rule = $this->makeRule(['path' => 'article/:slug?']);

        $result = $rule->route('http://example.com/');

        self::assertIsArray($result);
        // Optional var is absent → segment omitted, no remaining vars
        self::assertSame(['article'], $result['segments']);
        self::assertSame([], $result['vars']);
    }

    public function testRouteArrayVariable(): void
    {
        $rule = $this->makeRule(['path' => 'tag/:tags*']);

        $result = $rule->route('http://example.com/?tags[]=php&tags[]=unit');

        self::assertIsArray($result);
        self::assertSame(['tag', 'php', 'unit'], $result['segments']);
        self::assertArrayNotHasKey('tags', $result['vars']);
    }

    public function testRouteLoneStarIsIgnored(): void
    {
        $rule = $this->makeRule(['path' => 'base/*']);

        $result = $rule->route('http://example.com/?x=1');

        self::assertIsArray($result);
        // Lone star produces no segment
        self::assertSame(['base'], $result['segments']);
        self::assertSame(['x' => '1'], $result['vars']);
    }

    // =========================================================================
    // route() — matchVars
    // =========================================================================

    public function testRouteMatchVarsExactValuePresent(): void
    {
        $rule = $this->makeRule([
            'path'      => 'admin',
            'matchVars' => ['view' => 'admin'],
        ]);

        // view=admin triggers the rule and is consumed from vars
        $result = $rule->route('http://example.com/?view=admin&task=list');

        self::assertIsArray($result);
        self::assertArrayNotHasKey('view', $result['vars']);
        self::assertSame(['task' => 'list'], $result['vars']);
    }

    public function testRouteMatchVarsExactValueMissing(): void
    {
        $rule = $this->makeRule([
            'path'      => 'admin',
            'matchVars' => ['view' => 'admin'],
        ]);

        // view=users ≠ admin → rule must not match
        $result = $rule->route('http://example.com/?view=users');

        self::assertNull($result);
    }

    public function testRouteMatchVarsNullValuePresent(): void
    {
        $rule = $this->makeRule([
            'path'      => 'items',
            'matchVars' => ['view' => null],
        ]);

        // view can be anything; it is NOT removed from vars
        $result = $rule->route('http://example.com/?view=products');

        self::assertIsArray($result);
        self::assertSame('products', $result['vars']['view']);
    }

    public function testRouteMatchVarsRequiredKeyMissing(): void
    {
        $rule = $this->makeRule([
            'path'      => 'items',
            'matchVars' => ['view' => null],
        ]);

        // view key is absent → rule must not match
        $result = $rule->route('http://example.com/?task=list');

        self::assertNull($result);
    }

    // =========================================================================
    // route() — missing required named variable
    // =========================================================================

    public function testRouteMissingRequiredVarReturnsNull(): void
    {
        $rule = $this->makeRule(['path' => 'item/:id']);

        // id is not present in the query string
        $result = $rule->route('http://example.com/?other=1');

        self::assertNull($result);
    }

    // =========================================================================
    // route() — callable override
    // =========================================================================

    public function testRouteCallable(): void
    {
        $called = false;
        $rule   = $this->makeRule([
            'path'          => 'ignored',
            'routeCallable' => function (string $url) use (&$called): array {
                $called = true;
                return ['segments' => ['custom'], 'vars' => []];
            },
        ]);

        $result = $rule->route('http://example.com/?x=1');

        self::assertTrue($called, 'Route callable must be invoked');
        self::assertSame(['custom'], $result['segments']);
    }

    public function testRouteCallableNullDisablesCallable(): void
    {
        $rule = $this->makeRule(['path' => 'foo']);
        $rule->setRouteCallable(null);

        // Should fall through to normal routing
        $result = $rule->route('http://example.com/');

        self::assertIsArray($result);
        self::assertSame(['foo'], $result['segments']);
    }

    // =========================================================================
    // parse() — happy paths
    // =========================================================================

    public function testParseStaticSegments(): void
    {
        $rule = $this->makeRule(['path' => 'foo/bar']);

        $result = $rule->parse('foo/bar');

        self::assertIsArray($result);
        self::assertSame([], $result);
    }

    public function testParseNamedVariable(): void
    {
        $rule = $this->makeRule(['path' => 'item/:id']);

        $result = $rule->parse('item/42');

        self::assertIsArray($result);
        self::assertSame(['id' => '42'], $result);
    }

    public function testParseOptionalVariablePresent(): void
    {
        $rule = $this->makeRule(['path' => 'article/:slug?']);

        $result = $rule->parse('article/hello-world');

        self::assertIsArray($result);
        self::assertSame(['slug' => 'hello-world'], $result);
    }

    public function testParseOptionalVariableAbsent(): void
    {
        $rule = $this->makeRule(['path' => 'article/:slug?']);

        $result = $rule->parse('article');

        self::assertIsArray($result);
        self::assertSame([], $result);
    }

    public function testParseArrayVariable(): void
    {
        $rule = $this->makeRule(['path' => 'tag/:tags*']);

        $result = $rule->parse('tag/php/unit/testing');

        self::assertIsArray($result);
        self::assertSame(['tags' => ['php', 'unit', 'testing']], $result);
    }

    public function testParseLoneStar(): void
    {
        $rule = $this->makeRule(['path' => '*/item/:id']);

        // Any prefix is consumed by the lone star
        $result = $rule->parse('anything/here/item/99');

        self::assertIsArray($result);
        self::assertSame(['id' => '99'], $result);
    }

    // =========================================================================
    // parse() — type constraints
    // =========================================================================

    public function testParseNamedVariableWithTypeMatchingSegment(): void
    {
        $rule = $this->makeRule([
            'path'  => 'item/:id',
            'types' => ['id' => '/^\d+$/'],
        ]);

        $result = $rule->parse('item/123');

        self::assertIsArray($result);
        self::assertSame(['id' => '123'], $result);
    }

    public function testParseNamedVariableWithTypeNotMatching(): void
    {
        $rule = $this->makeRule([
            'path'  => 'item/:id',
            'types' => ['id' => '/^\d+$/'],
        ]);

        // 'abc' is not a digit string → rule should fail
        $result = $rule->parse('item/abc');

        self::assertNull($result);
    }

    public function testParseOptionalVariableTypeNotMatchingStillCapturesSegment(): void
    {
        // When the type regex does not match an optional variable, the rule sets
        // $rule = null but execution falls through to the capture block, so the
        // segment IS still captured.  This is the current behaviour of parseRoute().
        $rule = $this->makeRule([
            'path'  => 'item/:id?',
            'types' => ['id' => '/^\d+$/'],
        ]);

        $result = $rule->parse('item/abc');

        self::assertIsArray($result);
        // Despite the type mismatch the optional var captures the segment
        self::assertSame('abc', $result['id']);
    }

    public function testParseOptionalVariableTypeNotMatchingWithNoSegment(): void
    {
        $rule = $this->makeRule([
            'path'  => 'item/:id?',
            'types' => ['id' => '/^\d+$/'],
        ]);

        // No segment at all → optional var simply absent, not an error
        $result = $rule->parse('item');

        self::assertIsArray($result);
        self::assertArrayNotHasKey('id', $result);
    }

    // =========================================================================
    // parse() — static segment mismatch
    // =========================================================================

    public function testParseStaticSegmentMismatchReturnsNull(): void
    {
        $rule = $this->makeRule(['path' => 'foo/bar']);

        $result = $rule->parse('foo/baz');

        self::assertNull($result);
    }

    // =========================================================================
    // parse() — pushVars
    // =========================================================================

    public function testParsePushVarsAreMergedIntoResult(): void
    {
        $rule = $this->makeRule([
            'path'     => 'item/:id',
            'pushVars' => ['view' => 'item', 'task' => 'read'],
        ]);

        $result = $rule->parse('item/7');

        self::assertIsArray($result);
        self::assertSame('item', $result['view']);
        self::assertSame('read', $result['task']);
        self::assertSame('7', $result['id']);
    }

    // =========================================================================
    // parse() — query string inside path
    // =========================================================================

    public function testParsePathWithInlineQueryString(): void
    {
        $rule = $this->makeRule(['path' => 'item/:id']);

        // path contains a query string — Rule should split it out and merge
        $result = $rule->parse('item/5?extra=yes');

        self::assertIsArray($result);
        self::assertSame('5', $result['id']);
        self::assertSame('yes', $result['extra']);
    }

    // =========================================================================
    // parse() — callable override
    // =========================================================================

    public function testParseCallable(): void
    {
        $called = false;
        $rule   = $this->makeRule([
            'path'          => 'ignored',
            'parseCallable' => function (string $path) use (&$called): array {
                $called = true;
                return ['view' => 'custom'];
            },
        ]);

        $result = $rule->parse('anything');

        self::assertTrue($called, 'Parse callable must be invoked');
        self::assertSame(['view' => 'custom'], $result);
    }

    public function testParseCallableReturningNullPropagatesNull(): void
    {
        $rule = $this->makeRule([
            'path'          => 'ignored',
            'parseCallable' => fn(string $path): ?array => null,
        ]);

        $result = $rule->parse('anything');

        self::assertNull($result);
    }

    public function testParseCallableNullDisablesCallable(): void
    {
        $rule = $this->makeRule(['path' => 'foo/bar']);
        $rule->setParseCallable(null);

        $result = $rule->parse('foo/bar');

        self::assertIsArray($result);
    }

    // =========================================================================
    // parse() — too many / too few segments
    // =========================================================================

    public function testParseExtraSegmentsReturnsNull(): void
    {
        // Strict static path; extra segments should fail
        $rule = $this->makeRule(['path' => 'foo/bar']);

        $result = $rule->parse('foo/bar/baz');

        self::assertNull($result);
    }

    public function testParseMissingRequiredSegmentReturnsNull(): void
    {
        $rule = $this->makeRule(['path' => 'item/:id']);

        $result = $rule->parse('item');

        self::assertNull($result);
    }

    public function testParseAllOptionalRulesRemainingIsOk(): void
    {
        // path has a required static + optional var
        $rule = $this->makeRule(['path' => 'article/:slug?']);

        $result = $rule->parse('article');

        self::assertIsArray($result);
        self::assertSame([], $result);
    }

    // =========================================================================
    // route() + parse() round-trip
    // =========================================================================

    public static function roundTripProvider(): array
    {
        return [
            'simple static + var' => [
                'path'   => 'item/:id',
                'params' => ['id' => '42', 'extra' => 'yes'],
                // 'extra' is not in the path so it stays in vars, but we only
                // round-trip the id segment here.
                'expectedSegments' => ['item', '42'],
            ],
            'optional var present' => [
                'path'   => 'article/:slug?',
                'params' => ['slug' => 'hello'],
                'expectedSegments' => ['article', 'hello'],
            ],
        ];
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(string $path, array $params, array $expectedSegments): void
    {
        $rule = $this->makeRule(['path' => $path]);

        // Build the query string from params
        $url    = 'http://example.com/?' . http_build_query($params);
        $routed = $rule->route($url);

        self::assertIsArray($routed);
        self::assertSame($expectedSegments, $routed['segments']);

        // Now parse the produced segments back (Router trims leading slash before calling parse)
        $sefPath = implode('/', $routed['segments']);
        $parsed  = $rule->parse($sefPath);

        self::assertIsArray($parsed);

        // Every path variable must survive the round-trip
        foreach ($expectedSegments as $seg) {
            // static segments don't appear in parsed vars, skip them
        }

        // At minimum: id / slug must be present if they were in params
        foreach (['id', 'slug'] as $key) {
            if (isset($params[$key])) {
                self::assertSame($params[$key], $parsed[$key]);
            }
        }
    }

    // =========================================================================
    // getters / setters (sanity checks)
    // =========================================================================

    public function testSettersAndGetters(): void
    {
        $rule = new Rule();

        $rule->setPath('a/b');
        self::assertSame('a/b', $rule->getPath());

        $rule->setTypes(['id' => '/^\d+$/']);
        self::assertSame(['id' => '/^\d+$/'], $rule->getTypes());

        $rule->setMatchVars(['view' => 'item']);
        self::assertSame(['view' => 'item'], $rule->getMatchVars());

        $rule->setPushVars(['task' => 'read']);
        self::assertSame(['task' => 'read'], $rule->getPushVars());

        $cb = fn() => null;
        $rule->setRouteCallable($cb);
        self::assertSame($cb, $rule->getRouteCallable());

        $cb2 = fn() => null;
        $rule->setParseCallable($cb2);
        self::assertSame($cb2, $rule->getParseCallable());
    }

    public function testConstructorInitialisesFromDefinition(): void
    {
        $rule = new Rule([
            'path'      => 'foo/:bar',
            'types'     => ['bar' => '/^[a-z]+$/'],
            'matchVars' => ['view' => 'foo'],
            'pushVars'  => ['task' => 'view'],
        ]);

        self::assertSame('foo/:bar', $rule->getPath());
        self::assertSame(['bar' => '/^[a-z]+$/'], $rule->getTypes());
        self::assertSame(['view' => 'foo'], $rule->getMatchVars());
        self::assertSame(['task' => 'view'], $rule->getPushVars());
    }
}
