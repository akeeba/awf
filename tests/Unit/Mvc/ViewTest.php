<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc;

// Load stub MVC classes that live under the fake \ViewTestApp\… namespace.
require_once __DIR__ . '/Fixtures/ViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Engine\BladeEngine;
use Awf\Mvc\Engine\PhpEngine;
use Awf\Mvc\Model;
use Awf\Mvc\View;
use Awf\Mvc\ViewTemplateFinder;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for Awf\Mvc\View — core behaviour and data binding.
 *
 * Covered:
 *  - getName()                               — parsed from class name; cached; throws on invalid class name
 *  - escape()                                — HTML special-character escaping; null coalesce
 *  - set/get of view data (dynamic properties, model binding via get())
 *  - setDefaultModel / setDefaultModelName / setModel / getModel
 *  - getEngine()                             — .blade.php → BladeEngine, .php → PhpEngine, unknown → RuntimeException
 *  - setLayout / getLayout                   — plain name and colon-separated template:layout form
 *  - setTask / getTask / setDoTask / getDoTask
 *  - setStrictTpl / setStrictLayout
 *  - loadTemplate()                          — resolves real .php fixtures from the _data directory
 *  - display()                               — happy path; returns true and echoes output
 */
class ViewTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Set-up / tear-down
    // -------------------------------------------------------------------------

    /** @var array<string,mixed> Saved $_SERVER keys */
    private array $serverBackup = [];

    /** Absolute path to the fixture template directory. */
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->serverBackup = [
            'HTTP_HOST'   => $_SERVER['HTTP_HOST']   ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI']  ?? null,
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME']  ?? null,
        ];
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->fixtureDir = __DIR__ . '/_data/views/Item/tmpl';
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
     * Build a minimal Container sufficient for View construction without
     * hitting the filesystem, a real application, or any database.
     *
     * @param array<string,mixed> $extras  Extra keys merged on top of defaults (values override).
     */
    private function makeContainer(array $extras = []): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(static fn(string $k) => $k);

        $input = new Input([]);

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('ViewTestApp');
        $application->method('getTemplate')->willReturn('default');

        $defaults = [
            'application_name'     => 'ViewTestApp',
            'applicationNamespace' => '\\ViewTestApp',
            'session_segment_name' => 'viewtestapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
        ];

        return new Container(array_merge($defaults, $extras));
    }

    /**
     * Build a \ViewTestApp\View\Item\Html instance with its template path
     * pointing to the fixture directory.
     *
     * @param array<string,mixed> $mvcConfig  Keys merged into mvc_config.
     */
    private function makeView(array $mvcConfig = []): \ViewTestApp\View\Item\Html
    {
        $config = array_merge(
            ['template_path' => $this->fixtureDir],
            $mvcConfig
        );

        $container = $this->makeContainer(['mvc_config' => $config]);

        return new \ViewTestApp\View\Item\Html($container);
    }

    // =========================================================================
    // getName()
    // =========================================================================

    public function testGetNameParsesClassNameCorrectly(): void
    {
        $view = $this->makeView();

        self::assertSame('Item', $view->getName());
    }

    public function testGetNameIsCached(): void
    {
        $view = $this->makeView();

        // Call twice; both should return the same value (second call uses cache).
        self::assertSame($view->getName(), $view->getName());
    }

    public function testGetNameThrowsForAnonymousClass(): void
    {
        $container = $this->makeContainer(['mvc_config' => ['template_path' => $this->fixtureDir]]);

        // An anonymous class that extends View will not match the
        // …\View\{Name}\{Format} pattern, so getName() must throw.
        $this->expectException(RuntimeException::class);

        // Build an anonymous subclass inline.
        $anon = new class($container) extends View {
            // Force $name to null so getName() tries to parse class name.
        };

        // Trigger the name resolution.
        $anon->getName();
    }

    // =========================================================================
    // escape()
    // =========================================================================

    public static function escapeProvider(): array
    {
        return [
            'plain string passthrough' => ['hello world', 'hello world'],
            'ampersand'                => ['foo & bar',   'foo &amp; bar'],
            'double-quote'             => ['"quoted"',    '&quot;quoted&quot;'],
            'less-than sign'           => ['<script>',   '&lt;script&gt;'],
            'null coalesces to empty'  => [null,          ''],
            'UTF-8 multi-byte'         => ['héllo',       'héllo'],
        ];
    }

    #[DataProvider('escapeProvider')]
    public function testEscape(mixed $input, string $expected): void
    {
        $view = $this->makeView();

        self::assertSame($expected, $view->escape($input));
    }

    // =========================================================================
    // Dynamic properties (AllowDynamicProperties)
    // =========================================================================

    public function testDynamicPropertySetAndGet(): void
    {
        $view = $this->makeView();

        $view->myProp = 'testValue';

        self::assertSame('testValue', $view->myProp);
    }

    // =========================================================================
    // get() — view-property path (no model registered)
    // =========================================================================

    public function testGetReturnsViewPropertyWhenNoModel(): void
    {
        $view = $this->makeView();

        $view->myData = 'hello';

        self::assertSame('hello', $view->get('myData'));
    }

    public function testGetReturnsDefaultWhenPropertyAbsent(): void
    {
        $view = $this->makeView();

        self::assertSame('fallback', $view->get('nonExistentProperty', 'fallback'));
    }

    public function testGetReturnsNullDefaultWhenPropertyAbsentAndNoDefault(): void
    {
        $view = $this->makeView();

        self::assertNull($view->get('nonExistentProperty'));
    }

    // =========================================================================
    // setModel / setDefaultModel / setDefaultModelName / get() via model
    // =========================================================================

    public function testSetModelAndGetByGetterMethod(): void
    {
        $view      = $this->makeView();
        $container = $this->makeContainer();
        $model     = new \ViewTestApp\Model\Item($container);

        $view->setModel('item', $model);
        $view->setDefaultModelName('item');

        // getFoo() exists on the model → returns 'foo-value'
        self::assertSame('foo-value', $view->get('foo'));
    }

    public function testSetModelAndGetByMagicCall(): void
    {
        $view      = $this->makeView();
        $container = $this->makeContainer();
        $model     = new \ViewTestApp\Model\Item($container);

        $view->setModel('item', $model);
        $view->setDefaultModelName('item');

        // bar() exists but there is no getBar() — falls through to $property() call.
        self::assertSame('bar-value', $view->get('bar'));
    }

    public function testSetDefaultModelUpdatesDefaultModelName(): void
    {
        $view      = $this->makeView();
        $container = $this->makeContainer();
        $model     = new \ViewTestApp\Model\Item($container);

        $view->setDefaultModel($model);

        self::assertSame('foo-value', $view->get('foo'));
    }

    public function testGetWithExplicitModelName(): void
    {
        $view      = $this->makeView();
        $container = $this->makeContainer();
        $model     = new \ViewTestApp\Model\Item($container);

        $view->setModel('item', $model);

        // Pass model name explicitly; no default model set.
        self::assertSame('foo-value', $view->get('foo', null, 'item'));
    }

    // =========================================================================
    // setLayout / getLayout
    // =========================================================================

    public function testSetLayoutSimpleName(): void
    {
        $view = $this->makeView();

        $previous = $view->setLayout('custom');

        self::assertSame('default', $previous, 'Should return previous layout');
        self::assertSame('custom', $view->getLayout());
    }

    public function testSetLayoutColonSeparatedForm(): void
    {
        $view = $this->makeView();

        $view->setLayout('myTemplate:myLayout');

        self::assertSame('myLayout', $view->getLayout());
        self::assertSame('myTemplate', $view->getLayoutTemplate());
    }

    public function testGetLayoutDefaultIsDefault(): void
    {
        $view = $this->makeView();

        self::assertSame('default', $view->getLayout());
    }

    // =========================================================================
    // task / doTask
    // =========================================================================

    public function testSetAndGetTask(): void
    {
        $view = $this->makeView();

        $result = $view->setTask('browse');

        self::assertSame($view, $result, 'setTask should return $this for chaining');
        self::assertSame('browse', $view->getTask());
    }

    public function testSetAndGetDoTask(): void
    {
        $view = $this->makeView();

        $result = $view->setDoTask('read');

        self::assertSame($view, $result, 'setDoTask should return $this for chaining');
        self::assertSame('read', $view->getDoTask());
    }

    // =========================================================================
    // setStrictTpl / setStrictLayout
    // =========================================================================

    public function testSetStrictTplAndStrictLayout(): void
    {
        $view = $this->makeView();

        // These are void methods; just assert they don't throw.
        $view->setStrictTpl(true);
        $view->setStrictLayout(true);

        // Verify via reflection (properties are protected but accessible via property
        // access on the object itself since PHP 8.0 does not deprecate ReflectionProperty::getValue).
        $rp = new \ReflectionProperty(View::class, 'strictTpl');
        self::assertTrue($rp->getValue($view));

        $rp2 = new \ReflectionProperty(View::class, 'strictLayout');
        self::assertTrue($rp2->getValue($view));
    }

    // =========================================================================
    // getEngine() — engine selection by file extension
    // =========================================================================

    public function testGetEngineForPhpFile(): void
    {
        $view = $this->makeView();

        $rm = new \ReflectionMethod(View::class, 'getEngine');

        $engine = $rm->invoke($view, '/some/path/template.php');

        self::assertInstanceOf(PhpEngine::class, $engine);
    }

    public function testGetEngineForBladeFile(): void
    {
        $view = $this->makeView();

        $rm = new \ReflectionMethod(View::class, 'getEngine');

        $engine = $rm->invoke($view, '/some/path/template.blade.php');

        self::assertInstanceOf(BladeEngine::class, $engine);
    }

    public function testGetEngineThrowsForUnknownExtension(): void
    {
        $view = $this->makeView();

        $rm = new \ReflectionMethod(View::class, 'getEngine');

        $this->expectException(RuntimeException::class);

        $rm->invoke($view, '/some/path/template.twig');
    }

    // =========================================================================
    // alias()
    // =========================================================================

    public function testAliasRegistersViewTemplateAlias(): void
    {
        $view = $this->makeView();

        $view->alias('Item/default', 'myAlias');

        $rp = new \ReflectionProperty(View::class, 'viewTemplateAliases');
        $aliases = $rp->getValue($view);

        self::assertSame('Item/default', $aliases['myAlias']);
    }

    // =========================================================================
    // loadTemplate() + loadAnyTemplate() against real .php fixtures
    // =========================================================================

    public function testLoadTemplateDefaultLayout(): void
    {
        $view = $this->makeView();
        $view->setDoTask('display');

        $output = $view->loadTemplate();

        self::assertSame('HELLO FROM DEFAULT', $output);
    }

    public function testLoadTemplateCustomLayout(): void
    {
        $view = $this->makeView();
        $view->setDoTask('display');
        $view->setLayout('custom');

        $output = $view->loadTemplate();

        self::assertSame('HELLO FROM CUSTOM', $output);
    }

    public function testLoadTemplateSubTemplate(): void
    {
        $view = $this->makeView();
        $view->setDoTask('display');

        $output = $view->loadTemplate('sub');

        self::assertSame('HELLO FROM DEFAULT_SUB', $output);
    }

    public function testLoadAnyTemplateWithForceParams(): void
    {
        $view = $this->makeView();

        // The fixture template echoes $this->testVar; inject via forceParams.
        // Actually the template uses $this->testVar which is a dynamic property.
        $view->testVar = 'INJECTED';

        $output = $view->loadAnyTemplate('Item/vars');

        self::assertSame('INJECTED', $output);
    }

    public function testLoadAnyTemplateWithCallback(): void
    {
        $view = $this->makeView();

        $output = $view->loadAnyTemplate('Item/default', [], function ($v, string $content): string {
            return strtolower($content);
        });

        self::assertSame('hello from default', $output);
    }

    public function testLoadAnyTemplateThrowsForMissingTemplate(): void
    {
        $view = $this->makeView();

        $this->expectException(\Exception::class);

        $view->loadAnyTemplate('Item/nonexistent_template_xyz');
    }

    // =========================================================================
    // display() — happy path
    // =========================================================================

    public function testDisplayEchoesOutputAndReturnsTrue(): void
    {
        $view = $this->makeView();
        $view->setDoTask('display');

        ob_start();
        $result = $view->display();
        $output = ob_get_clean();

        self::assertTrue($result);
        self::assertSame('HELLO FROM DEFAULT', $output);
    }

    // =========================================================================
    // renderEach() — raw | empty fallback
    // =========================================================================

    public function testRenderEachEmptyDataReturnsRawFallback(): void
    {
        $view = $this->makeView();

        $result = $view->renderEach('Item/default', [], 'item', 'raw|NO ITEMS');

        self::assertSame('NO ITEMS', $result);
    }

    public function testRenderEachWithDataRendersEachItem(): void
    {
        $view = $this->makeView();

        // Each item rendered via Item/default outputs 'HELLO FROM DEFAULT'.
        $result = $view->renderEach('Item/default', ['a', 'b'], 'item', 'raw|NO ITEMS');

        self::assertSame('HELLO FROM DEFAULTHELLO FROM DEFAULT', $result);
    }

    // =========================================================================
    // Section management (startSection / stopSection / yieldContent)
    // =========================================================================

    public function testStartAndStopSectionCapturesContent(): void
    {
        $view = $this->makeView();

        $view->startSection('mysection');
        echo 'section content';
        $view->stopSection();

        self::assertSame('section content', $view->yieldContent('mysection'));
    }

    public function testYieldContentReturnsDefaultWhenSectionAbsent(): void
    {
        $view = $this->makeView();

        self::assertSame('fallback default', $view->yieldContent('absent', 'fallback default'));
    }

    public function testStopSectionThrowsWhenStackEmpty(): void
    {
        $view = $this->makeView();

        // stopSection() pops an ob when the stack is empty. To prevent PHPUnit
        // from flagging a closed-output-buffer risky test, we open our own ob
        // first so stopSection() closes ours (not PHPUnit's).
        ob_start();

        try {
            $view->stopSection();
            ob_end_clean(); // clean up if (unexpectedly) no exception was thrown
            self::fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            // ob_get_clean() was already called by stopSection() on the buffer
            // we opened, so there is nothing left to clean up.
            self::assertNotEmpty($e->getMessage());
        }
    }

    public function testFlushSectionsClearsState(): void
    {
        $view = $this->makeView();

        $view->startSection('s1');
        echo 'data';
        $view->stopSection();

        $view->flushSections();

        self::assertSame('', $view->yieldContent('s1'));
    }

    // =========================================================================
    // Render counter
    // =========================================================================

    public function testIncrementAndDecrementRenderCounter(): void
    {
        $view = $this->makeView();

        self::assertTrue($view->doneRendering());

        $view->incrementRender();

        self::assertFalse($view->doneRendering());

        $view->decrementRender();

        self::assertTrue($view->doneRendering());
    }
}
