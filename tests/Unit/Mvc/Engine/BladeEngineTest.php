<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\Engine;

// Load the View stub so we can build a minimal View for the engine constructor.
require_once __DIR__ . '/../Fixtures/ViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Compiler\Blade;
use Awf\Mvc\Engine\AbstractEngine;
use Awf\Mvc\Engine\BladeEngine;
use Awf\Mvc\Engine\CompilingEngine;
use Awf\Mvc\Engine\EngineInterface;
use Awf\Text\Language;
use Awf\Utils\HashHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\Engine\BladeEngine (and its parent CompilingEngine).
 *
 * Covers:
 *  - Class hierarchy
 *  - Happy path: .blade.php is compiled and the cache file is written under tmp/
 *  - Return value structure of get()
 *  - Cache-hit path: a second call to get() reuses the existing cache
 *  - Recompilation: cache is stale when the source is newer
 *  - getPrecompiledPath() logic
 */
class BladeEngineTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Properties
    // -------------------------------------------------------------------------

    /** Temporary directory for this test run */
    private string $tmpDir;

    /** The data directory that holds blade fixtures */
    private string $fixtureDir;

    // -------------------------------------------------------------------------
    // Set-up / tear-down
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        // Uri::base() reads these keys; avoid "Undefined array key" warnings.
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        // Each test gets a fresh, isolated tmp directory.
        $this->tmpDir     = sys_get_temp_dir() . '/awf_blade_engine_test_' . uniqid('', true);
        $this->fixtureDir = __DIR__ . '/../_data/engine';

        @mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Remove the tmp directory tree created for this test.
        $this->removeDirectory($this->tmpDir);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    private function makeContainer(?string $tmpDir = null): Container
    {
        $tmpDir = $tmpDir ?? $this->tmpDir;

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(static fn(string $k) => $k);

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('BladeEngineTestApp');
        $application->method('getTemplate')->willReturn('default');

        return new Container([
            'application_name'     => 'BladeEngineTestApp',
            'applicationNamespace' => '\\BladeEngineTestApp',
            'session_segment_name' => 'bladetestapp_seg',
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

    private function makeView(?Container $container = null): \ViewTestApp\View\Item\Html
    {
        return new \ViewTestApp\View\Item\Html($container ?? $this->makeContainer());
    }

    private function makeEngine(?Container $container = null): BladeEngine
    {
        return new BladeEngine($this->makeView($container));
    }

    /**
     * Returns the expected cache path for a given source path, given the
     * two-level subfolder scheme used by CompilingEngine::getCachePath().
     */
    private function expectedCachePath(string $sourcePath, string $tmpDir): string
    {
        $id = HashHelper::sha1($sourcePath);

        return sprintf(
            '%s/compiled_templates/%s/%s/%s.php',
            $tmpDir,
            substr($id, 0, 1),
            substr($id, 1, 1),
            $id
        );
    }

    // -------------------------------------------------------------------------
    // Class hierarchy
    // -------------------------------------------------------------------------

    public function testExtendsAbstractEngine(): void
    {
        self::assertInstanceOf(AbstractEngine::class, $this->makeEngine());
    }

    public function testExtendsCompilingEngine(): void
    {
        self::assertInstanceOf(CompilingEngine::class, $this->makeEngine());
    }

    public function testImplementsEngineInterface(): void
    {
        self::assertInstanceOf(EngineInterface::class, $this->makeEngine());
    }

    // -------------------------------------------------------------------------
    // get() — return value structure (happy path)
    // -------------------------------------------------------------------------

    public function testGetReturnsArray(): void
    {
        $engine = $this->makeEngine();
        $path   = $this->fixtureDir . '/static.blade.php';
        $result = $engine->get($path);

        self::assertIsArray($result);
    }

    public function testGetReturnValueHasExpectedKeys(): void
    {
        $engine = $this->makeEngine();
        $path   = $this->fixtureDir . '/static.blade.php';
        $result = $engine->get($path);

        self::assertCount(3, $result);
        self::assertArrayHasKey('type', $result);
        self::assertArrayHasKey('content', $result);
        self::assertArrayHasKey('original', $result);
    }

    public function testGetReturnsTypeEqualToPath(): void
    {
        $engine = $this->makeEngine();
        $path   = $this->fixtureDir . '/static.blade.php';
        $result = $engine->get($path);

        self::assertSame('path', $result['type']);
    }

    public function testGetReturnsOriginalEqualToSourcePath(): void
    {
        $engine = $this->makeEngine();
        $path   = $this->fixtureDir . '/static.blade.php';
        $result = $engine->get($path);

        self::assertSame($path, $result['original']);
    }

    // -------------------------------------------------------------------------
    // Compilation: cache file is written under tmp/
    // -------------------------------------------------------------------------

    public function testGetWritesCacheFile(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $result    = $engine->get($path);
        $cachePath = $result['content'];

        self::assertFileExists($cachePath);
    }

    public function testCacheFileIsUnderTemporaryPath(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $result    = $engine->get($path);
        $cachePath = $result['content'];

        self::assertStringStartsWith($this->tmpDir . '/compiled_templates/', $cachePath);
    }

    public function testCacheFilePathMatchesExpectedScheme(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $result    = $engine->get($path);
        $expected  = $this->expectedCachePath($path, $this->tmpDir);

        self::assertSame($expected, $result['content']);
    }

    public function testCacheFileContainsCompiledPhp(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $result     = $engine->get($path);
        $cacheContents = file_get_contents($result['content']);

        // The compiled file must start with the path comment header.
        self::assertStringContainsString('<?php', $cacheContents);
        self::assertStringContainsString($path, $cacheContents);
    }

    public function testCacheFileDoesNotContainBladeCommentSyntax(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $result        = $engine->get($path);
        $cacheContents = file_get_contents($result['content']);

        // Blade comments ({{-- ... --}}) must have been compiled away.
        self::assertStringNotContainsString('{{--', $cacheContents);
        self::assertStringNotContainsString('--}}', $cacheContents);
    }

    // -------------------------------------------------------------------------
    // Cache hit: second call reuses existing cache
    // -------------------------------------------------------------------------

    public function testSecondGetCallReusesCache(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $first  = $engine->get($path);
        $second = $engine->get($path);

        self::assertSame($first['content'], $second['content']);
        self::assertSame($first['original'], $second['original']);
    }

    public function testCachedFileIsNotRewrittenOnSecondCall(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        $first   = $engine->get($path);
        $mtime1  = filemtime($first['content']);

        // Sleep 1 second to allow mtime to differ if the file were re-written.
        sleep(1);

        $second  = $engine->get($path);
        $mtime2  = filemtime($second['content']);

        self::assertSame($mtime1, $mtime2, 'Cache file must not be re-written on a cache hit');
    }

    // -------------------------------------------------------------------------
    // Recompilation when source is newer than cache
    // -------------------------------------------------------------------------

    public function testStaleSourceTriggersRecompilation(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);
        $path      = $this->fixtureDir . '/static.blade.php';

        // First compilation
        $first     = $engine->get($path);
        $cachePath = $first['content'];
        self::assertFileExists($cachePath);

        // Make the cache appear older than the source by touching it into the past.
        $pastTime = time() - 3600;
        touch($cachePath, $pastTime, $pastTime);
        clearstatcache(true, $cachePath);

        // Now make the source appear newer (touch it to the current time).
        touch($path);
        clearstatcache(true, $path);

        $second = $engine->get($path);

        // Cache file must have been re-written (mtime must be newer than $pastTime).
        $newMtime = filemtime($second['content']);
        self::assertGreaterThan($pastTime, $newMtime, 'Cache must be refreshed when source is newer');
    }

    // -------------------------------------------------------------------------
    // BladeEngine stores the Blade compiler
    // -------------------------------------------------------------------------

    public function testBladeCompilerIsAssigned(): void
    {
        $engine = $this->makeEngine();

        $rp = new \ReflectionProperty(CompilingEngine::class, 'compiler');
        $compiler = $rp->getValue($engine);

        self::assertInstanceOf(Blade::class, $compiler);
    }

    // -------------------------------------------------------------------------
    // getPrecompiledPath() — returns false for path outside basePath
    // -------------------------------------------------------------------------

    public function testGetPrecompiledPathReturnsFalseForExternalPath(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);

        // A path that is not under basePath (= $this->tmpDir) should return false.
        $result = $engine->getPrecompiledPath('/some/completely/external/path/view.blade.php');

        self::assertFalse($result);
    }

    public function testGetPrecompiledPathReturnsFalseForNonExistentFile(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);

        $result = $engine->getPrecompiledPath($this->tmpDir . '/does-not-exist.blade.php');

        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // getPrecompiledPath() — computes a path for a file inside basePath
    // -------------------------------------------------------------------------

    public function testGetPrecompiledPathReturnsStringForFileInsideBasePath(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);

        // Create a real file inside basePath so realpath() can resolve it.
        $viewsDir = $this->tmpDir . '/ViewTemplates/Item';
        @mkdir($viewsDir, 0777, true);
        $bladeFile = $viewsDir . '/default.blade.php';
        file_put_contents($bladeFile, '{{-- stub --}}');

        $result = $engine->getPrecompiledPath($bladeFile);

        self::assertIsString($result);
        self::assertStringContainsString('PrecompiledTemplates', $result);
    }

    public function testGetPrecompiledPathEndsWithPhpExtension(): void
    {
        $container = $this->makeContainer();
        $engine    = $this->makeEngine($container);

        $viewsDir = $this->tmpDir . '/ViewTemplates/Item';
        @mkdir($viewsDir, 0777, true);
        $bladeFile = $viewsDir . '/default.blade.php';
        file_put_contents($bladeFile, '{{-- stub --}}');

        $result = $engine->getPrecompiledPath($bladeFile);

        self::assertStringEndsWith('.php', (string) $result);
        // The compiled extension (blade.php) must have been replaced by .php only.
        self::assertStringNotContainsString('blade.php', basename((string) $result));
    }
}
