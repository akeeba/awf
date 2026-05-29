<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Router;

use Awf\Application\Configuration;
use Awf\Container\Container;
use Awf\Input\Input;
use Awf\Router\Router;
use Awf\Router\Rule;
use Awf\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Awf\Router\Router
 *
 * Covers:
 *  - addRule / addRuleFromDefinition / addRules / clearRules
 *  - route()  — converts a query-string URL into a routed URL
 *  - parse()  — converts a routed URL path into input vars
 *  - Rule precedence (first matching rule wins)
 *  - exportRoutes / importRoutes round-trip
 *  - Rebase on/off behaviour
 */
class RouterTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a minimal Container.
     *
     * Setting `base_url` to an absolute HTTP URL avoids calls to Uri::base()
     * (which needs $_SERVER superglobals) and keeps every test self-contained.
     *
     * @param string $baseUrl The value of `base_url` in appConfig
     */
    private function makeContainer(string $baseUrl = 'http://example.com'): Container
    {
        $tmpDir = sys_get_temp_dir();

        $container = new Container([
            'application_name'     => 'RouterTestApp',
            'applicationNamespace' => '\\RouterTestApp',
            'session_segment_name' => 'routertest_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
        ]);

        $container->appConfig->set('base_url', $baseUrl);

        return $container;
    }

    private function makeRouter(string $baseUrl = 'http://example.com'): Router
    {
        return new Router($this->makeContainer($baseUrl));
    }

    // =========================================================================
    // addRule / addRuleFromDefinition / addRules / clearRules
    // =========================================================================

    public function testAddRuleAndClearRules(): void
    {
        $router = $this->makeRouter();

        $rule = new Rule(['path' => 'foo/bar']);
        $router->addRule($rule);

        // Export to verify the rule was stored
        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(1, $maps);
        self::assertSame('foo/bar', $maps[0]['path']);

        $router->clearRules();
        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(0, $maps);
    }

    public function testAddRuleFromDefinition(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition(['path' => 'items/:id']);

        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(1, $maps);
        self::assertSame('items/:id', $maps[0]['path']);
    }

    public function testAddRulesAcceptsRuleObjectsAndArrayDefinitions(): void
    {
        $router = $this->makeRouter();

        $rule = new Rule(['path' => 'alpha']);

        $router->addRules([
            $rule,                           // Rule object
            ['path' => 'beta'],              // array definition
        ]);

        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(2, $maps);
        self::assertSame('alpha', $maps[0]['path']);
        self::assertSame('beta',  $maps[1]['path']);
    }

    public function testAddRulesIgnoresNonRuleNonArrayItems(): void
    {
        $router = $this->makeRouter();

        // A stdClass is not a Rule, so it should be ignored
        $router->addRules([
            new \stdClass(),
            ['path' => 'valid'],
        ]);

        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(1, $maps);
        self::assertSame('valid', $maps[0]['path']);
    }

    public function testAddRulesIgnoresScalarItems(): void
    {
        $router = $this->makeRouter();
        $router->addRules(['not-an-array-or-rule', 42, true]);

        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(0, $maps);
    }

    public function testAddRulesWithEmptyArrayDoesNothing(): void
    {
        $router = $this->makeRouter();
        $router->addRules([]);

        $json = $router->exportRoutes();
        $maps = json_decode($json, true);
        self::assertCount(0, $maps);
    }

    // =========================================================================
    // route() — no rebase (rebase=false)
    // =========================================================================

    public function testRouteWithNoRulesReturnsOriginalUrl(): void
    {
        $router = $this->makeRouter();

        $result = $router->route('http://example.com/?view=items&id=5', false);

        // No rule applied; URL passes through as-is
        self::assertStringContainsString('view=items', $result);
        self::assertStringContainsString('id=5', $result);
    }

    public function testRouteWithMatchingRuleBuildsSegmentedUrl(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition([
            'path'      => 'items/:id',
            'matchVars' => ['view' => 'items'],
        ]);

        $result = $router->route('http://example.com/?view=items&id=42', false);

        // The rule must have consumed 'view' and 'id', produced path items/42
        self::assertStringContainsString('items/42', $result);
        self::assertStringNotContainsString('id=42', $result);
        self::assertStringNotContainsString('view=items', $result);
    }

    public function testRouteWithNonMatchingRuleLeavesUrlUntouched(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition([
            'path'      => 'items/:id',
            'matchVars' => ['view' => 'items'],
        ]);

        // 'view=other' does not match
        $result = $router->route('http://example.com/?view=other&id=1', false);

        self::assertStringContainsString('view=other', $result);
        self::assertStringContainsString('id=1', $result);
    }

    public function testRouteWithOptionalVariablePresentInUrl(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition(['path' => 'blog/:slug?']);

        $result = $router->route('http://example.com/?slug=my-post', false);

        self::assertStringContainsString('blog/my-post', $result);
        self::assertStringNotContainsString('slug=', $result);
    }

    public function testRouteWithOptionalVariableAbsentFromUrl(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition(['path' => 'blog/:slug?']);

        $result = $router->route('http://example.com/', false);

        // Path becomes just 'blog' (optional segment omitted)
        self::assertStringContainsString('blog', $result);
        self::assertStringNotContainsString('slug=', $result);
    }

    // =========================================================================
    // route() — with rebase
    // =========================================================================

    public function testRouteWithRebasePrependsBaseUrl(): void
    {
        $router = $this->makeRouter('http://example.com/app');

        $router->addRuleFromDefinition([
            'path'      => 'items/:id',
            'matchVars' => ['view' => 'items'],
        ]);

        $result = $router->route('http://example.com/?view=items&id=7');

        self::assertStringStartsWith('http://example.com', $result);
        self::assertStringContainsString('items/7', $result);
    }

    public function testRouteWithRebaseMergesQueryVars(): void
    {
        $router = $this->makeRouter('http://example.com');

        $router->addRuleFromDefinition([
            'path'      => 'shop/:id',
            'matchVars' => ['view' => 'shop'],
        ]);

        $result = $router->route('http://example.com/?view=shop&id=3&extra=yes');

        self::assertStringContainsString('shop/3', $result);
        self::assertStringContainsString('extra=yes', $result);
    }

    // =========================================================================
    // route() — rule callable
    // =========================================================================

    public function testRouteUsingCallable(): void
    {
        $router = $this->makeRouter();

        $router->addRuleFromDefinition([
            'routeCallable' => static function (string $url): array {
                return [
                    'segments' => ['custom', 'callable'],
                    'vars'     => [],
                ];
            },
        ]);

        $result = $router->route('http://example.com/?anything=1', false);

        self::assertStringContainsString('custom/callable', $result);
    }

    public function testRouteCallableReturningNullIsSkipped(): void
    {
        $router = $this->makeRouter();

        $router->addRuleFromDefinition([
            'routeCallable' => static function (string $url): ?array {
                return null;
            },
        ]);
        $router->addRuleFromDefinition(['path' => 'fallback']);

        $result = $router->route('http://example.com/', false);

        self::assertStringContainsString('fallback', $result);
    }

    // =========================================================================
    // Rule precedence
    // =========================================================================

    public function testFirstMatchingRuleWins(): void
    {
        $router = $this->makeRouter();

        // Both rules would match view=list, but the first should win
        $router->addRuleFromDefinition([
            'path'      => 'first/:id',
            'matchVars' => ['view' => 'list'],
        ]);
        $router->addRuleFromDefinition([
            'path'      => 'second/:id',
            'matchVars' => ['view' => 'list'],
        ]);

        $result = $router->route('http://example.com/?view=list&id=9', false);

        self::assertStringContainsString('first/9', $result);
        self::assertStringNotContainsString('second', $result);
    }

    public function testNonMatchingFirstRuleFallsToSecond(): void
    {
        $router = $this->makeRouter();

        $router->addRuleFromDefinition([
            'path'      => 'alpha/:id',
            'matchVars' => ['view' => 'alpha'],
        ]);
        $router->addRuleFromDefinition([
            'path'      => 'beta/:id',
            'matchVars' => ['view' => 'beta'],
        ]);

        $result = $router->route('http://example.com/?view=beta&id=2', false);

        self::assertStringContainsString('beta/2', $result);
        self::assertStringNotContainsString('alpha', $result);
    }

    // =========================================================================
    // parse() — no rebase
    // =========================================================================

    public function testParseWithNoRulesSetsUriQueryVarsOnInput(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->parse('http://example.com/?view=test&id=77', false);

        self::assertSame('test', $container->input->get('view', null, 'raw'));
        self::assertSame('77',   $container->input->get('id',   null, 'raw'));
    }

    public function testParseWithMatchingRuleSetsSegmentVarsOnInput(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->addRuleFromDefinition([
            'path'     => 'products/:id',
            'pushVars' => ['view' => 'products'],
        ]);

        $router->parse('http://example.com/products/123', false);

        self::assertSame('123',      $container->input->get('id',   null, 'raw'));
        self::assertSame('products', $container->input->get('view', null, 'raw'));
    }

    public function testParseWithNonMatchingRuleFallsBackToQueryString(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->addRuleFromDefinition([
            'path' => 'products/:id',
        ]);

        // The path 'totally/different' won't match 'products/:id'
        $router->parse('http://example.com/totally/different?view=fallback', false);

        self::assertSame('fallback', $container->input->get('view', null, 'raw'));
    }

    public function testParseWithPushVarsMixedWithQueryStringVars(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->addRuleFromDefinition([
            'path'     => 'cats/:id',
            'pushVars' => ['view' => 'cats', 'layout' => 'grid'],
        ]);

        $router->parse('http://example.com/cats/99?extra=bonus', false);

        self::assertSame('99',    $container->input->get('id',     null, 'raw'));
        self::assertSame('cats',  $container->input->get('view',   null, 'raw'));
        self::assertSame('grid',  $container->input->get('layout', null, 'raw'));
        self::assertSame('bonus', $container->input->get('extra',  null, 'raw'));
    }

    // =========================================================================
    // parse() — callable override
    // =========================================================================

    public function testParseUsingCallable(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->addRuleFromDefinition([
            'parseCallable' => static function (string $path): array {
                return ['view' => 'custom', 'id' => '55'];
            },
        ]);

        $router->parse('http://example.com/whatever', false);

        self::assertSame('custom', $container->input->get('view', null, 'raw'));
        self::assertSame('55',     $container->input->get('id',   null, 'raw'));
    }

    public function testParseCallableReturningNullIsSkipped(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->addRuleFromDefinition([
            'parseCallable' => static function (string $path): ?array {
                return null;
            },
        ]);

        // Falls back to plain query-string parsing
        $router->parse('http://example.com/?view=fallback', false);

        self::assertSame('fallback', $container->input->get('view', null, 'raw'));
    }

    // =========================================================================
    // parse() — precedence between rules
    // =========================================================================

    public function testParseFirstMatchingRuleWins(): void
    {
        $container = $this->makeContainer();
        $router    = new Router($container);

        $router->addRuleFromDefinition([
            'path'     => 'first/:id',
            'pushVars' => ['winner' => 'first'],
        ]);
        $router->addRuleFromDefinition([
            'path'     => 'first/:id',
            'pushVars' => ['winner' => 'second'],
        ]);

        $router->parse('http://example.com/first/1', false);

        self::assertSame('first', $container->input->get('winner', null, 'raw'));
    }

    // =========================================================================
    // exportRoutes / importRoutes round-trip
    // =========================================================================

    public function testExportRoutesReturnsValidJson(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition([
            'path'      => 'foo/:bar',
            'matchVars' => ['view' => 'foo'],
            'pushVars'  => ['layout' => 'default'],
        ]);

        $json = $router->exportRoutes();

        self::assertJson($json);

        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertCount(1, $decoded);
        self::assertSame('foo/:bar', $decoded[0]['path']);
        self::assertSame(['view' => 'foo'], $decoded[0]['matchVars']);
        self::assertSame(['layout' => 'default'], $decoded[0]['pushVars']);
    }

    public function testImportRoutesReplacesExistingRules(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition(['path' => 'old/route']);

        $newJson = json_encode([
            ['path' => 'new/route', 'types' => [], 'matchVars' => [], 'pushVars' => [], 'routeCallable' => null, 'parseCallable' => null],
        ]);
        $router->importRoutes($newJson, true);

        $maps = json_decode($router->exportRoutes(), true);
        self::assertCount(1, $maps);
        self::assertSame('new/route', $maps[0]['path']);
    }

    public function testImportRoutesAppendWhenReplaceIsFalse(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition(['path' => 'existing/route']);

        $additionalJson = json_encode([
            ['path' => 'added/route', 'types' => [], 'matchVars' => [], 'pushVars' => [], 'routeCallable' => null, 'parseCallable' => null],
        ]);
        $router->importRoutes($additionalJson, false);

        $maps = json_decode($router->exportRoutes(), true);
        self::assertCount(2, $maps);
    }

    public function testExportImportRoundTrip(): void
    {
        $router = $this->makeRouter();
        $router->addRuleFromDefinition([
            'path'      => 'category/:cat/item/:id',
            'matchVars' => ['view' => 'item'],
            'pushVars'  => ['layout' => 'blog'],
            'types'     => ['id' => '/^\d+$/', 'cat' => '/^[a-z]+$/'],
        ]);

        $json = $router->exportRoutes();

        $router2 = $this->makeRouter();
        $router2->importRoutes($json);

        $json2 = $router2->exportRoutes();

        self::assertSame(
            json_decode($json, true),
            json_decode($json2, true)
        );
    }

    // =========================================================================
    // parse() — with rebase: removes base path from URL before parsing
    // =========================================================================

    public function testParseWithRebaseStripsBasePath(): void
    {
        // Set live_site so that Uri::base() returns a known prefix path.
        // We set live_site = 'http://example.com/app' so Uri::base() → 'http://example.com/app/'
        // We also set base_url = '' (empty) so no extra concatenation happens.
        // Then parse() builds: base = 'http://example.com/app' + '/' + '' = 'http://example.com/app'
        // → removePath = 'app'
        // The URL 'http://example.com/app/products/88' strips 'app' → '/products/88' → parsed.

        Uri::reset();

        $container = $this->makeContainer('');
        $container->appConfig->set('live_site', 'http://example.com/app');

        $router = new Router($container);
        $router->addRuleFromDefinition([
            'path'     => 'products/:id',
            'pushVars' => ['view' => 'products'],
        ]);

        // The URL contains the base path '/app' prefix; parse() should strip it
        $router->parse('http://example.com/app/products/88', true);

        self::assertSame('88',       $container->input->get('id',   null, 'raw'));
        self::assertSame('products', $container->input->get('view', null, 'raw'));

        // Restore
        Uri::reset();
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    public function testRouteEmptyRulesDoesNotModifyPath(): void
    {
        $router = $this->makeRouter();

        // No rules — the URL must come back with original query string
        $result = $router->route('http://example.com/index.php?view=main', false);

        self::assertStringContainsString('view=main', $result);
    }

    public function testParseEmptyUrlUsesCurrentUrl(): void
    {
        // When $url is null/empty, parse() falls back to Uri::current().
        // Seed the minimum $_SERVER keys so Uri::getInstance() does not emit warnings.
        $prevHost   = $_SERVER['HTTP_HOST']   ?? null;
        $prevScript = $_SERVER['SCRIPT_NAME'] ?? null;
        $prevReq    = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/index.php';

        Uri::reset();

        $container = $this->makeContainer();
        $router    = new Router($container);

        // This should not throw, even if Uri::current() returns something odd.
        try {
            $router->parse('', false);
            $this->addToAssertionCount(1);
        } catch (\Throwable $e) {
            self::fail('parse() threw unexpectedly: ' . $e->getMessage());
        }

        // Restore server vars and URI cache
        Uri::reset();

        if ($prevHost === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $prevHost;
        }

        if ($prevScript === null) {
            unset($_SERVER['SCRIPT_NAME']);
        } else {
            $_SERVER['SCRIPT_NAME'] = $prevScript;
        }

        if ($prevReq === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $prevReq;
        }
    }
}
