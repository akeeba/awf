<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\Engine;

// Load the View stub so we can build a minimal View for the engine constructor.
require_once __DIR__ . '/../Fixtures/ViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Engine\AbstractEngine;
use Awf\Mvc\Engine\EngineInterface;
use Awf\Mvc\Engine\PhpEngine;
use Awf\Mvc\View;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\Engine\PhpEngine.
 *
 * PhpEngine is intentionally thin: its get() method simply returns the path
 * wrapped in an array with type=>'path'.  All rendering happens later in
 * View::loadTemplate(), which includes the file directly.  These tests verify
 * the contract of the get() return value and the class hierarchy.
 */
class PhpEngineTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Set-up / tear-down
    // -------------------------------------------------------------------------

    /** @var array<string,mixed> Saved $_SERVER keys */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        // Uri::base() reads these keys; avoid "Undefined array key" warnings.
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

    private function makeContainer(): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(static fn(string $k) => $k);

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('ViewTestApp');
        $application->method('getTemplate')->willReturn('default');

        return new Container([
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
            'input'                => new Input([]),
            'application'          => $application,
            'mvc_config'           => [
                'template_path' => $tmpDir,
            ],
        ]);
    }

    private function makeView(): \ViewTestApp\View\Item\Html
    {
        return new \ViewTestApp\View\Item\Html($this->makeContainer());
    }

    private function makeEngine(): PhpEngine
    {
        return new PhpEngine($this->makeView());
    }

    // -------------------------------------------------------------------------
    // Class hierarchy
    // -------------------------------------------------------------------------

    public function testExtendsAbstractEngine(): void
    {
        self::assertInstanceOf(AbstractEngine::class, $this->makeEngine());
    }

    public function testImplementsEngineInterface(): void
    {
        self::assertInstanceOf(EngineInterface::class, $this->makeEngine());
    }

    // -------------------------------------------------------------------------
    // get() — return value structure
    // -------------------------------------------------------------------------

    public function testGetReturnsArray(): void
    {
        $result = $this->makeEngine()->get('/some/template.php');

        self::assertIsArray($result);
    }

    public function testGetReturnsTypeEqualToPath(): void
    {
        $result = $this->makeEngine()->get('/some/template.php');

        self::assertArrayHasKey('type', $result);
        self::assertSame('path', $result['type']);
    }

    public function testGetReturnsContentEqualToInputPath(): void
    {
        $path   = '/var/www/html/views/item/tmpl/default.php';
        $result = $this->makeEngine()->get($path);

        self::assertArrayHasKey('content', $result);
        self::assertSame($path, $result['content']);
    }

    public function testGetReturnsOriginalEqualToInputPath(): void
    {
        $path   = '/var/www/html/views/item/tmpl/default.php';
        $result = $this->makeEngine()->get($path);

        self::assertArrayHasKey('original', $result);
        self::assertSame($path, $result['original']);
    }

    public function testGetContentAndOriginalAreTheSame(): void
    {
        $path   = '/any/template.php';
        $result = $this->makeEngine()->get($path);

        self::assertSame($result['content'], $result['original']);
    }

    // -------------------------------------------------------------------------
    // get() — edge cases
    // -------------------------------------------------------------------------

    public function testGetWithEmptyPath(): void
    {
        $result = $this->makeEngine()->get('');

        self::assertSame('path', $result['type']);
        self::assertSame('', $result['content']);
        self::assertSame('', $result['original']);
    }

    public function testGetWithWindowsStylePath(): void
    {
        $path   = 'C:\\Users\\user\\views\\default.php';
        $result = $this->makeEngine()->get($path);

        self::assertSame($path, $result['content']);
        self::assertSame($path, $result['original']);
    }

    public function testGetWithRelativePath(): void
    {
        $path   = 'views/item/default.php';
        $result = $this->makeEngine()->get($path);

        self::assertSame($path, $result['content']);
        self::assertSame($path, $result['original']);
    }

    public function testGetWithPathContainingSpaces(): void
    {
        $path   = '/path with spaces/my template.php';
        $result = $this->makeEngine()->get($path);

        self::assertSame($path, $result['content']);
        self::assertSame($path, $result['original']);
    }

    // -------------------------------------------------------------------------
    // get() — forceParams is accepted but has no effect on the return value
    // -------------------------------------------------------------------------

    public function testGetIgnoresForceParams(): void
    {
        $path         = '/tmpl/default.php';
        $engine       = $this->makeEngine();
        $withoutParams = $engine->get($path);
        $withParams    = $engine->get($path, ['foo' => 'bar', 'baz' => 42]);

        self::assertSame($withoutParams, $withParams);
    }

    // -------------------------------------------------------------------------
    // Data-provider driven: various real-world-style paths
    // -------------------------------------------------------------------------

    public static function pathProvider(): array
    {
        return [
            'absolute unix path'      => ['/var/www/html/views/item/tmpl/default.php'],
            'absolute unix path 2'    => ['/srv/app/templates/item/default.php'],
            'path with dot segments'  => ['/app/../views/item/default.php'],
            'only filename'           => ['default.php'],
            'path without extension'  => ['/tmpl/default'],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testGetPassesPathThroughUnmodified(string $path): void
    {
        $result = $this->makeEngine()->get($path);

        self::assertSame('path', $result['type']);
        self::assertSame($path, $result['content']);
        self::assertSame($path, $result['original']);
    }

    // -------------------------------------------------------------------------
    // Return value contains exactly the expected keys
    // -------------------------------------------------------------------------

    public function testGetReturnValueHasExactlyThreeKeys(): void
    {
        $result = $this->makeEngine()->get('/tmpl/default.php');

        self::assertCount(3, $result);
        self::assertArrayHasKey('type', $result);
        self::assertArrayHasKey('content', $result);
        self::assertArrayHasKey('original', $result);
    }

    // -------------------------------------------------------------------------
    // View is stored in the engine (inherited from AbstractEngine)
    // -------------------------------------------------------------------------

    public function testEngineStoresViewFromConstructor(): void
    {
        $view   = $this->makeView();
        $engine = new PhpEngine($view);

        $rp    = new \ReflectionProperty(AbstractEngine::class, 'view');
        $stored = $rp->getValue($engine);

        self::assertSame($view, $stored);
    }
}
