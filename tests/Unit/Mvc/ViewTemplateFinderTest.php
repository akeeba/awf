<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc;

// Load stub MVC classes that live under the fake \VtfTestApp\… namespace.
require_once __DIR__ . '/Fixtures/ViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Exception\LayoutNotFoundException;
use Awf\Input\Input;
use Awf\Mvc\View;
use Awf\Mvc\ViewTemplateFinder;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\ViewTemplateFinder.
 *
 * Covered:
 *  - Constructor config parsing (extensions, defaultLayout, defaultTpl, strict flags)
 *  - getExtensions / setExtensions / addExtension / removeExtension
 *  - getDefaultLayout / setDefaultLayout
 *  - getDefaultTpl / setDefaultTpl
 *  - isStrictView / setStrictView
 *  - isStrictTpl / setStrictTpl
 *  - isStrictLayout / setStrictLayout
 *  - getViewTemplateUris — all combinations of strict flags
 *  - parseTemplateUri — empty, single-segment, two-segment URIs
 *  - resolveUriToPath — lookup-order (theme override > ViewTemplates > View/tmpl > views/tmpl > system
 *                        fallback), .blade.php preferred over .php, extra paths, not-found exception
 */
class ViewTemplateFinderTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fixture constants
    // -------------------------------------------------------------------------

    /** Root of the fixture tree used by resolveUriToPath tests. */
    private string $fixtureBase;

    // -------------------------------------------------------------------------
    // Set-up / tear-down
    // -------------------------------------------------------------------------

    /** @var array<string,mixed> Saved $_SERVER keys */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->fixtureBase = __DIR__ . '/_data/vtf';

        $this->serverBackup = [
            'HTTP_HOST'   => $_SERVER['HTTP_HOST']   ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI']  ?? null,
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME']  ?? null,
        ];
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container sufficient for ViewTemplateFinder construction.
     *
     * @param string $templateName  The active theme name (returned by application->getTemplate()).
     * @param array<string,mixed> $extras  Extra keys merged on top of defaults.
     */
    private function makeContainer(string $templateName = 'default', array $extras = []): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(static fn(string $k, ...$args) => $k);

        $input = new Input([]);

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('VtfTestApp');
        $application->method('getTemplate')->willReturn($templateName);

        $defaults = [
            'application_name'     => 'VtfTestApp',
            'applicationNamespace' => '\\VtfTestApp',
            'session_segment_name' => 'vtftestapp_seg',
            'basePath'             => $this->fixtureBase,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $this->fixtureBase . '/templates',
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $this->fixtureBase,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
        ];

        return new Container(array_merge($defaults, $extras));
    }

    /**
     * Build a View mock for the given view name, backed by the fixture container.
     */
    private function makeView(string $viewName = 'Item', string $templateName = 'default'): View
    {
        $container = $this->makeContainer($templateName);

        $view = $this->createMock(View::class);
        $view->method('getName')->willReturn($viewName);
        $view->method('getContainer')->willReturn($container);

        return $view;
    }

    /**
     * Build a ViewTemplateFinder for the given view name, with optional config.
     */
    private function makeFinder(
        string $viewName = 'Item',
        array $config = [],
        string $templateName = 'default'
    ): ViewTemplateFinder {
        $view = $this->makeView($viewName, $templateName);
        return new ViewTemplateFinder($view, $config);
    }

    // =========================================================================
    // Constructor / config parsing
    // =========================================================================

    public function testDefaultsAfterConstruction(): void
    {
        $finder = $this->makeFinder();

        self::assertSame(['.blade.php', '.php'], $finder->getExtensions());
        self::assertSame('default', $finder->getDefaultLayout());
        self::assertSame('', $finder->getDefaultTpl());
        self::assertTrue($finder->isStrictView());
        self::assertTrue($finder->isStrictTpl());
        self::assertTrue($finder->isStrictLayout());
    }

    public function testConstructorParsesExtensionsFromArray(): void
    {
        $finder = $this->makeFinder('Item', ['extensions' => ['.twig', '.html']]);

        self::assertSame(['.twig', '.html'], $finder->getExtensions());
    }

    public function testConstructorParsesExtensionsFromString(): void
    {
        $finder = $this->makeFinder('Item', ['extensions' => '.twig, .html']);

        self::assertSame(['.twig', '.html'], $finder->getExtensions());
    }

    public function testConstructorSetsDefaultLayout(): void
    {
        $finder = $this->makeFinder('Item', ['defaultLayout' => 'list']);

        self::assertSame('list', $finder->getDefaultLayout());
    }

    public function testConstructorSetsDefaultTpl(): void
    {
        $finder = $this->makeFinder('Item', ['defaultTpl' => 'form']);

        self::assertSame('form', $finder->getDefaultTpl());
    }

    public static function strictFlagTruthyProvider(): array
    {
        return [
            'bool true'    => [true],
            'string true'  => ['true'],
            'string yes'   => ['yes'],
            'string on'    => ['on'],
            'int 1'        => [1],
        ];
    }

    #[DataProvider('strictFlagTruthyProvider')]
    public function testConstructorStrictViewTruthyValues(mixed $value): void
    {
        $finder = $this->makeFinder('Item', ['strictView' => $value]);
        self::assertTrue($finder->isStrictView());
    }

    public function testConstructorStrictViewFalsyValue(): void
    {
        $finder = $this->makeFinder('Item', ['strictView' => false]);
        self::assertFalse($finder->isStrictView());
    }

    #[DataProvider('strictFlagTruthyProvider')]
    public function testConstructorStrictTplTruthyValues(mixed $value): void
    {
        $finder = $this->makeFinder('Item', ['strictTpl' => $value]);
        self::assertTrue($finder->isStrictTpl());
    }

    public function testConstructorStrictTplFalsyValue(): void
    {
        $finder = $this->makeFinder('Item', ['strictTpl' => false]);
        self::assertFalse($finder->isStrictTpl());
    }

    #[DataProvider('strictFlagTruthyProvider')]
    public function testConstructorStrictLayoutTruthyValues(mixed $value): void
    {
        $finder = $this->makeFinder('Item', ['strictLayout' => $value]);
        self::assertTrue($finder->isStrictLayout());
    }

    public function testConstructorStrictLayoutFalsyValue(): void
    {
        $finder = $this->makeFinder('Item', ['strictLayout' => false]);
        self::assertFalse($finder->isStrictLayout());
    }

    // =========================================================================
    // Extension management
    // =========================================================================

    public function testSetExtensionsReplacesAll(): void
    {
        $finder = $this->makeFinder();
        $finder->setExtensions(['.html']);

        self::assertSame(['.html'], $finder->getExtensions());
    }

    public function testAddExtensionAppends(): void
    {
        $finder = $this->makeFinder();
        $finder->addExtension('.twig');

        self::assertContains('.twig', $finder->getExtensions());
    }

    public function testAddExtensionPrependsDot(): void
    {
        $finder = $this->makeFinder();
        $finder->addExtension('twig');

        self::assertContains('.twig', $finder->getExtensions());
    }

    public function testAddExtensionIgnoresDuplicates(): void
    {
        $finder = $this->makeFinder();
        $finder->addExtension('.php');

        self::assertSame(['.blade.php', '.php'], $finder->getExtensions());
    }

    public function testAddExtensionIgnoresEmptyString(): void
    {
        $finder = $this->makeFinder();
        $finder->addExtension('');

        self::assertSame(['.blade.php', '.php'], $finder->getExtensions());
    }

    public function testRemoveExtensionRemovesExisting(): void
    {
        $finder = $this->makeFinder();
        $finder->removeExtension('.php');

        self::assertNotContains('.php', $finder->getExtensions());
        self::assertContains('.blade.php', $finder->getExtensions());
    }

    public function testRemoveExtensionPrependsDot(): void
    {
        $finder = $this->makeFinder();
        $finder->removeExtension('php');

        self::assertNotContains('.php', $finder->getExtensions());
    }

    public function testRemoveExtensionIgnoresMissing(): void
    {
        $finder = $this->makeFinder();
        $finder->removeExtension('.twig');

        self::assertSame(['.blade.php', '.php'], $finder->getExtensions());
    }

    public function testRemoveExtensionIgnoresEmptyString(): void
    {
        $finder = $this->makeFinder();
        $finder->removeExtension('');

        self::assertSame(['.blade.php', '.php'], $finder->getExtensions());
    }

    // =========================================================================
    // Getters / setters for simple properties
    // =========================================================================

    public function testSetGetDefaultLayout(): void
    {
        $finder = $this->makeFinder();
        $finder->setDefaultLayout('form');
        self::assertSame('form', $finder->getDefaultLayout());
    }

    public function testSetGetDefaultTpl(): void
    {
        $finder = $this->makeFinder();
        $finder->setDefaultTpl('ajax');
        self::assertSame('ajax', $finder->getDefaultTpl());
    }

    public function testSetGetStrictView(): void
    {
        $finder = $this->makeFinder();
        $finder->setStrictView(false);
        self::assertFalse($finder->isStrictView());
        $finder->setStrictView(true);
        self::assertTrue($finder->isStrictView());
    }

    public function testSetGetStrictTpl(): void
    {
        $finder = $this->makeFinder();
        $finder->setStrictTpl(false);
        self::assertFalse($finder->isStrictTpl());
        $finder->setStrictTpl(true);
        self::assertTrue($finder->isStrictTpl());
    }

    public function testSetGetStrictLayout(): void
    {
        $finder = $this->makeFinder();
        $finder->setStrictLayout(false);
        self::assertFalse($finder->isStrictLayout());
        $finder->setStrictLayout(true);
        self::assertTrue($finder->isStrictLayout());
    }

    // =========================================================================
    // getViewTemplateUris
    // =========================================================================

    public function testGetViewTemplateUrisAllStrictReturnsOneUri(): void
    {
        $finder = $this->makeFinder('Item', [
            'strictView'   => true,
            'strictLayout' => true,
            'strictTpl'    => true,
        ]);

        $uris = $finder->getViewTemplateUris(['view' => 'Item', 'layout' => 'default', 'tpl' => '']);

        self::assertSame(['Item/default'], $uris);
    }

    public function testGetViewTemplateUrisWithSubtemplate(): void
    {
        $finder = $this->makeFinder('Item', [
            'strictView'   => true,
            'strictLayout' => true,
            'strictTpl'    => true,
        ]);

        $uris = $finder->getViewTemplateUris(['view' => 'Item', 'layout' => 'default', 'tpl' => 'form']);

        self::assertSame(['Item/default_form'], $uris);
    }

    public function testGetViewTemplateUrisNotStrictTplAddsBase(): void
    {
        $finder = $this->makeFinder('Item');

        $uris = $finder->getViewTemplateUris([
            'view'      => 'Item',
            'layout'    => 'default',
            'tpl'       => 'form',
            'strictTpl' => false,
        ]);

        self::assertContains('Item/default_form', $uris);
        self::assertContains('Item/default', $uris);
    }

    public function testGetViewTemplateUrisNotStrictLayoutAddsFallback(): void
    {
        $finder = $this->makeFinder('Item');

        $uris = $finder->getViewTemplateUris([
            'view'         => 'Item',
            'layout'       => 'custom',
            'tpl'          => '',
            'strictLayout' => false,
            'strictTpl'    => true,
        ]);

        self::assertContains('Item/custom', $uris);
        self::assertContains('Item/default', $uris);
    }

    public function testGetViewTemplateUrisNotStrictLayoutNotStrictTpl(): void
    {
        $finder = $this->makeFinder('Item');

        $uris = $finder->getViewTemplateUris([
            'view'         => 'Item',
            'layout'       => 'custom',
            'tpl'          => 'form',
            'strictLayout' => false,
            'strictTpl'    => false,
        ]);

        self::assertContains('Item/custom_form', $uris);
        self::assertContains('Item/custom', $uris);
        self::assertContains('Item/default_form', $uris);
        self::assertContains('Item/default', $uris);
    }

    public function testGetViewTemplateUrisNotStrictViewAddsPluralised(): void
    {
        $finder = $this->makeFinder('Item');

        $uris = $finder->getViewTemplateUris([
            'view'       => 'Item',
            'layout'     => 'default',
            'tpl'        => '',
            'strictView' => false,
        ]);

        // Should include both the singular and the pluralised form
        self::assertContains('Item/default', $uris);
        self::assertContains('Items/default', $uris);
    }

    public function testGetViewTemplateUrisNotStrictViewWithPluralInput(): void
    {
        $finder = $this->makeFinder('Items');

        $uris = $finder->getViewTemplateUris([
            'view'       => 'Items',
            'layout'     => 'default',
            'tpl'        => '',
            'strictView' => false,
        ]);

        // Should include both the plural and the singularised form
        self::assertContains('Items/default', $uris);
        self::assertContains('Item/default', $uris);
    }

    public function testGetViewTemplateUrisUsesFinderDefaultsWhenNotInParameters(): void
    {
        $finder = $this->makeFinder('Item', [
            'defaultLayout' => 'list',
            'defaultTpl'    => 'row',
            'strictView'    => true,
            'strictLayout'  => true,
            'strictTpl'     => true,
        ]);

        $uris = $finder->getViewTemplateUris([]);

        self::assertSame(['Item/list_row'], $uris);
    }

    public function testGetViewTemplateUrisReturnsUniqueUris(): void
    {
        $finder = $this->makeFinder('Item', [
            'defaultLayout' => 'default',
        ]);

        // When layout == 'default' and strictLayout is false, the fallback
        // to 'default' would duplicate the primary URI → should be deduplicated.
        $uris = $finder->getViewTemplateUris([
            'view'         => 'Item',
            'layout'       => 'default',
            'tpl'          => '',
            'strictLayout' => false,
            'strictTpl'    => true,
        ]);

        self::assertSame(count($uris), count(array_unique($uris)));
    }

    // =========================================================================
    // parseTemplateUri
    // =========================================================================

    public function testParseTemplateUriEmptyReturnsDefaults(): void
    {
        $finder = $this->makeFinder('Item');

        $parts = $finder->parseTemplateUri('');

        self::assertSame('Item', $parts['view']);
        self::assertSame('default', $parts['template']);
    }

    public function testParseTemplateUriTwoSegments(): void
    {
        $finder = $this->makeFinder('Foo');

        $parts = $finder->parseTemplateUri('Bar/baz');

        self::assertSame('Bar', $parts['view']);
        self::assertSame('baz', $parts['template']);
    }

    public function testParseTemplateUriSingleSegmentIsView(): void
    {
        $finder = $this->makeFinder('Foo');

        $parts = $finder->parseTemplateUri('Bar');

        self::assertSame('Bar', $parts['view']);
        // template key must still exist, with the default value
        self::assertArrayHasKey('template', $parts);
        self::assertSame('default', $parts['template']);
    }

    public function testParseTemplateUriOnlyTakesFirstSlash(): void
    {
        // The explode limit is 2, so extra slashes stay in the template part
        $finder = $this->makeFinder('Foo');

        $parts = $finder->parseTemplateUri('view/sub/deep');

        self::assertSame('view', $parts['view']);
        self::assertSame('sub/deep', $parts['template']);
    }

    // =========================================================================
    // resolveUriToPath — path lookup order
    // =========================================================================

    /**
     * The ViewTemplates directory is preferred over View/tmpl and views/tmpl.
     */
    public function testResolveUriPrefersViewTemplatesOverLegacyPaths(): void
    {
        // Both ViewTemplates/Item/default.php and View/Item/tmpl/default.php exist.
        $finder = $this->makeFinder('Item', ['.php']);

        $path = $finder->resolveUriToPath('Item/default');

        self::assertStringContainsString('ViewTemplates/Item', $path);
    }

    /**
     * Blade extension is tried before .php.
     */
    public function testResolveUriBladePrecedesPhp(): void
    {
        // Both ViewTemplates/Item/default.blade.php and default.php exist.
        $finder = $this->makeFinder();

        $path = $finder->resolveUriToPath('Item/default');

        self::assertStringEndsWith('default.blade.php', $path);
    }

    /**
     * Theme override (templates/{theme}/html/{view}) is tried before ViewTemplates.
     */
    public function testResolveUriThemeOverrideWinsOverViewTemplates(): void
    {
        // templates/mytheme/html/Item/default.php exists.
        $finder = $this->makeFinder('Item', [], 'mytheme');

        $path = $finder->resolveUriToPath('Item/default');

        self::assertStringContainsString('mytheme/html/Item', $path);
    }

    /**
     * Legacy template override path (templatePath/html/{view}) is tried before ViewTemplates.
     * We test this by putting a file only in templates/html/LegacyItem (no theme subdir)
     * so that it doesn't compete with ViewTemplates/Item which exists.
     *
     * The source code uses `$this->container->templatePath . '/html/' . $parts['view']`
     * as the second path (legacy) and `basePath . '/ViewTemplates/' . $parts['view']` as the third.
     */
    public function testResolveUriLegacyTemplatePathBeforeViewTemplates(): void
    {
        // Create an ad-hoc fixture file in the legacy template override location for a unique view name
        $legacyDir  = $this->fixtureBase . '/templates/html/LegacyItem';
        $legacyFile = $legacyDir . '/legacy_only.blade.php';
        if (!is_dir($legacyDir)) {
            mkdir($legacyDir, 0777, true);
        }
        file_put_contents($legacyFile, '{{-- legacy template --}}');

        // Also create a competing file in ViewTemplates for this view (to verify legacy wins)
        $vtDir  = $this->fixtureBase . '/ViewTemplates/LegacyItem';
        $vtFile = $vtDir . '/legacy_only.blade.php';
        if (!is_dir($vtDir)) {
            mkdir($vtDir, 0777, true);
        }
        file_put_contents($vtFile, '{{-- ViewTemplates version --}}');

        try {
            // Use a theme whose dedicated folder has no files (so path 1 misses)
            $finder = $this->makeFinder('LegacyItem', [], 'nonexistent_theme');
            $path   = $finder->resolveUriToPath('LegacyItem/legacy_only');

            self::assertStringContainsString('templates/html/LegacyItem', $path);
        } finally {
            @unlink($legacyFile);
            @rmdir($legacyDir);
            @unlink($vtFile);
            @rmdir($vtDir);
        }
    }

    /**
     * When the view-specific template is missing but the system fallback exists, use the fallback.
     */
    public function testResolveUriSystemFallback(): void
    {
        // The fixture has templates/system/html/Item/default.php.
        // We use a base dir that has NO ViewTemplates, View, or views folders for this view
        // by using a view name that only exists in the system fallback.
        $finder = $this->makeFinder('Item', ['.php'], 'nonexistent_theme');

        // Remove ViewTemplates/Item to make system fallback reachable is not
        // necessary because 'nonexistent_theme' misses and ViewTemplates has the file.
        // Instead, we test with a view name that only exists in the system folder.
        // Create a temporary system-only view 'Sole'.
        $systemDir  = $this->fixtureBase . '/templates/system/html/Sole';
        $systemFile = $systemDir . '/only.php';
        if (!is_dir($systemDir)) {
            mkdir($systemDir, 0777, true);
        }
        file_put_contents($systemFile, '<?php // system fallback');

        try {
            $finderSole = $this->makeFinder('Sole', [], 'nonexistent_theme');
            $path       = $finderSole->resolveUriToPath('Sole/only');

            self::assertStringContainsString('system/html/Sole', $path);
        } finally {
            @unlink($systemFile);
            @rmdir($systemDir);
        }
    }

    /**
     * Extra paths are searched before all the built-in paths.
     */
    public function testResolveUriExtraPathsHaveHighestPriority(): void
    {
        // fixture: extra/Item/default.php exists
        $extraPath = $this->fixtureBase . '/extra';

        $finder = $this->makeFinder();

        $path = $finder->resolveUriToPath('Item/default', '', [$extraPath . '/Item']);

        self::assertStringContainsString('/extra/Item', $path);
    }

    /**
     * When no file can be found, a LayoutNotFoundException (code 500) is thrown. This dedicated type is what lets
     * View::loadTemplate() distinguish a missing layout (fall back) from a render-time error (propagate).
     */
    public function testResolveUriThrowsWhenNotFound(): void
    {
        $finder = $this->makeFinder('Nonexistent');

        try {
            $finder->resolveUriToPath('Nonexistent/missing_template');
            self::fail('Expected a LayoutNotFoundException to be thrown.');
        } catch (LayoutNotFoundException $e) {
            self::assertSame(500, $e->getCode());
        }
    }

    /**
     * Legacy View subfolder (basePath/View/{view}/tmpl) is tried before views/tmpl.
     * We verify with a view whose file is ONLY in View/tmpl.
     */
    public function testResolveUriViewTmplBeforeViewsTmpl(): void
    {
        // Create a unique template file only in View/Unique/tmpl (not in ViewTemplates or views)
        $viewTmplDir  = $this->fixtureBase . '/View/Unique/tmpl';
        $viewTmplFile = $viewTmplDir . '/special.php';
        if (!is_dir($viewTmplDir)) {
            mkdir($viewTmplDir, 0777, true);
        }
        file_put_contents($viewTmplFile, '<?php // View tmpl');

        try {
            $finder = $this->makeFinder('Unique', [], 'nonexistent_theme');
            $path   = $finder->resolveUriToPath('Unique/special');

            self::assertStringContainsString('View/Unique/tmpl', $path);
        } finally {
            @unlink($viewTmplFile);
            @rmdir($viewTmplDir);
            @rmdir(dirname($viewTmplDir));
        }
    }

    /**
     * Custom extension order is respected.
     */
    public function testResolveUriCustomExtensionOrder(): void
    {
        // Both default.blade.php and default.php exist.
        // With extensions set to ['.php'] only, .blade.php should NOT be returned.
        $finder = $this->makeFinder('Item', ['extensions' => ['.php']]);

        $path = $finder->resolveUriToPath('Item/default');

        self::assertStringEndsWith('default.php', $path);
        self::assertStringNotContainsString('.blade.php', $path);
    }
}
