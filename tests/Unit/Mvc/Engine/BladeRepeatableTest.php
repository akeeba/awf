<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\Engine;

// Load the View stub so we can build real View instances.
require_once __DIR__ . '/../Fixtures/ViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Compiler\Blade;
use Awf\Mvc\Compiler\CompilerInterface;
use Awf\Mvc\Engine\BladeEngine;
use Awf\Mvc\Engine\CompilingEngine;
use Awf\Text\Language;
use Awf\Utils\HashHelper;
use PHPUnit\Framework\TestCase;

/**
 * A BladeEngine which records the paths passed to bustOpCache() and can be told to fail writing to the cache, so
 * that the stream wrapper fallback is exercised.
 */
class SpyBladeEngine extends BladeEngine
{
	/** @var string[] Paths passed to bustOpCache() */
	public static array $bustedPaths = [];

	/** @var bool Should putToCache() pretend writing failed? */
	public static bool $failCacheWrite = false;

	protected function bustOpCache($path)
	{
		self::$bustedPaths[] = $path;
	}

	protected function putToCache($path, $content)
	{
		if (self::$failCacheWrite)
		{
			return false;
		}

		return parent::putToCache($path, $content);
	}
}

/**
 * A Blade compiler reporting a different output format version.
 */
class FutureFormatBlade extends Blade
{
	public function getOutputFormatVersion(): string
	{
		return '99';
	}
}

/**
 * A third-party compiler which knows nothing about output format versions.
 */
class UnversionedCompiler implements CompilerInterface
{
	public function isCacheable(): bool
	{
		return true;
	}

	public function compile(string $path, array $forceParams = []): string
	{
		return (string) file_get_contents($path);
	}

	public function getFileExtension(): string
	{
		return 'blade.php';
	}
}

/**
 * End-to-end tests for @repeatable / @repeatableOverride / @yieldRepeatable through the real BladeEngine,
 * CompilingEngine cache and View, plus the compiled template cache identifier and OPcache busting.
 *
 * Regression: the compiled output of a parent template used to depend on whether a child template with a
 * @repeatableOverride had been compiled earlier by the same compiler instance. Whichever compile order populated the
 * on-disk cache, a later request got either a fatal "Value of type null is not callable" (parent rendered alone) or a
 * silently ignored override (child rendered).
 */
class BladeRepeatableTest extends TestCase
{
	private string $tmpDir;

	protected function setUp(): void
	{
		$_SERVER['HTTP_HOST']   = 'localhost';
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['SCRIPT_NAME'] = '/index.php';

		$this->tmpDir = sys_get_temp_dir() . '/awf_blade_repeatable_test_' . uniqid('', true);

		@mkdir($this->tmpDir . '/ViewTemplates/Repeat', 0777, true);

		$this->writeTemplate(
			'parent',
			"@repeatable('x', \$item)\nPARENT:{{ \$item }}\n@endrepeatable\n[@yieldRepeatable('x', 'A')]\n"
		);
		$this->writeTemplate(
			'child',
			"@extends('Repeat/parent')\n@repeatableOverride('x', \$item)\nCHILD:{{ \$item }}\n@endrepeatable\n"
		);

		SpyBladeEngine::$bustedPaths    = [];
		SpyBladeEngine::$failCacheWrite = false;
	}

	protected function tearDown(): void
	{
		$this->removeDirectory($this->tmpDir);

		SpyBladeEngine::$bustedPaths    = [];
		SpyBladeEngine::$failCacheWrite = false;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function removeDirectory(string $dir): void
	{
		if (!is_dir($dir))
		{
			return;
		}

		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($items as $item)
		{
			$item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
		}

		@rmdir($dir);
	}

	private function writeTemplate(string $name, string $content): string
	{
		$path = $this->templatePath($name);

		file_put_contents($path, $content);

		return $path;
	}

	private function templatePath(string $name): string
	{
		return $this->tmpDir . '/ViewTemplates/Repeat/' . $name . '.blade.php';
	}

	/**
	 * A fresh container stands for a fresh request: it has its own, shared Blade compiler instance.
	 */
	private function makeContainer(): Container
	{
		$ed = $this->createMock(EventDispatcher::class);
		$ed->method('trigger')->willReturn([]);

		$language = $this->createMock(Language::class);
		$language->method('text')->willReturnCallback(static fn(string $k) => $k);
		$language->method('sprintf')->willReturnCallback(static fn(string $k) => $k);

		$application = $this->createMock(Application::class);
		$application->method('getName')->willReturn('BladeRepeatableTestApp');
		$application->method('getTemplate')->willReturn('default');

		return new Container([
			'application_name'     => 'BladeRepeatableTestApp',
			'applicationNamespace' => '\\BladeRepeatableTestApp',
			'session_segment_name' => 'bladerepeatable_seg',
			'basePath'             => $this->tmpDir,
			'languagePath'         => $this->tmpDir,
			'temporaryPath'        => $this->tmpDir,
			'templatePath'         => $this->tmpDir,
			'sqlPath'              => $this->tmpDir,
			'filesystemBase'       => $this->tmpDir,
			'eventDispatcher'      => $ed,
			'language'             => $language,
			'input'                => new Input([]),
			'application'          => $application,
			'mvc_config'           => [
				'template_path' => $this->tmpDir,
			],
		]);
	}

	/**
	 * Render a template on a brand new View (as a separate page load / view would).
	 */
	private function render(Container $container, string $template): string
	{
		$view = new \ViewTestApp\View\Item\Html($container);

		return $this->normalise($view->loadAnyTemplate('Repeat/' . $template));
	}

	private function normalise(string $output): string
	{
		return preg_replace('/\s+/', '', $output);
	}

	/**
	 * The View wraps template exceptions in an ErrorException (once per template nesting level). Return the first
	 * RuntimeException in the chain, i.e. the one thrown by the compiled template code.
	 */
	private function findRuntimeException(\Throwable $e): ?\RuntimeException
	{
		for ($t = $e; $t !== null; $t = $t->getPrevious())
		{
			if ($t instanceof \RuntimeException)
			{
				return $t;
			}
		}

		return null;
	}

	private function invokeProtected(object $object, string $method, ...$args)
	{
		$rm = new \ReflectionMethod($object, $method);

		return $rm->invoke($object, ...$args);
	}

	private function setCompiler(CompilingEngine $engine, CompilerInterface $compiler): void
	{
		(new \ReflectionProperty(CompilingEngine::class, 'compiler'))->setValue($engine, $compiler);
	}

	// -------------------------------------------------------------------------
	// Compiled output must not depend on compile order
	// -------------------------------------------------------------------------

	public function testParentCompiledOutputDoesNotDependOnCompileOrder(): void
	{
		// Order 1: child, then parent, with one compiler instance.
		$blade1 = $this->makeContainer()->blade;
		$blade1->compile($this->templatePath('child'));
		$parentAfterChild = $blade1->compile($this->templatePath('parent'));

		// Order 2: parent, then child, with another compiler instance.
		$blade2            = $this->makeContainer()->blade;
		$parentAlone       = $blade2->compile($this->templatePath('parent'));
		$childAfterParent  = $blade2->compile($this->templatePath('child'));

		// Order 3: child alone.
		$childAlone = $this->makeContainer()->blade->compile($this->templatePath('child'));

		self::assertSame($parentAlone, $parentAfterChild, 'Parent compiled output must not depend on compile order');
		self::assertSame($childAlone, $childAfterParent, 'Child compiled output must not depend on compile order');
		self::assertStringNotContainsString('SKIP_', $parentAfterChild);
	}

	// -------------------------------------------------------------------------
	// Rendering: child compiled (and cached) first
	// -------------------------------------------------------------------------

	public function testChildFirstThenParentRendersCorrectly(): void
	{
		$container = $this->makeContainer();

		// The child is rendered first: the compiler sees the child, then the parent; both get cached.
		self::assertSame('[CHILD:A]', $this->render($container, 'child'));

		// Same request, parent rendered alone from the cache.
		self::assertSame('[PARENT:A]', $this->render($container, 'parent'));

		// A later request (new compiler instance) reusing the on-disk cache, in both orders.
		$later = $this->makeContainer();
		self::assertSame('[PARENT:A]', $this->render($later, 'parent'));
		self::assertSame('[CHILD:A]', $this->render($later, 'child'));
	}

	// -------------------------------------------------------------------------
	// Rendering: parent compiled (and cached) first
	// -------------------------------------------------------------------------

	public function testParentFirstThenChildRendersCorrectly(): void
	{
		$container = $this->makeContainer();

		self::assertSame('[PARENT:A]', $this->render($container, 'parent'));

		// Same request: the child must still win over the already cached parent definition.
		self::assertSame('[CHILD:A]', $this->render($container, 'child'));

		// A later request reusing the cache, in both orders.
		$later = $this->makeContainer();
		self::assertSame('[CHILD:A]', $this->render($later, 'child'));
		self::assertSame('[PARENT:A]', $this->render($later, 'parent'));
	}

	public function testOverrideWinsInMultiLevelInheritance(): void
	{
		$this->writeTemplate(
			'grandchild',
			"@extends('Repeat/child')\n@repeatableOverride('x', \$item)\nGRANDCHILD:{{ \$item }}\n@endrepeatable\n"
		);

		$container = $this->makeContainer();

		self::assertSame('[PARENT:A]', $this->render($container, 'parent'));
		self::assertSame('[CHILD:A]', $this->render($container, 'child'));

		// The child's override executes after the grandchild's override, so the child's wins (closest to the parent).
		// This matches the pre-existing semantics: @repeatableOverride is an unconditional assignment.
		self::assertSame('[CHILD:A]', $this->render($container, 'grandchild'));
	}

	// -------------------------------------------------------------------------
	// @yieldRepeatable error handling
	// -------------------------------------------------------------------------

	public function testYieldingAnUndefinedRepeatableThrowsAClearError(): void
	{
		$this->writeTemplate('undefined', "[@yieldRepeatable('notThere', 1)]\n");

		try
		{
			$this->render($this->makeContainer(), 'undefined');
		}
		catch (\Throwable $e)
		{
			$e = $this->findRuntimeException($e);

			self::assertNotNull($e);
			self::assertSame('Repeatable "notThere" is not defined', $e->getMessage());
			self::assertNull($e->getPrevious(), 'The undefined repeatable error must not wrap a PHP engine error');

			return;
		}

		self::fail('Yielding an undefined repeatable must throw a RuntimeException');
	}

	public function testExceptionInsideRepeatableIsWrapped(): void
	{
		$this->writeTemplate(
			'throwing',
			"@repeatable('boom')\n<?php throw new \\DomainException('Inner failure'); ?>\n@endrepeatable\n"
			. "[@yieldRepeatable('boom')]\n"
		);

		try
		{
			$this->render($this->makeContainer(), 'throwing');
		}
		catch (\Throwable $e)
		{
			$e = $this->findRuntimeException($e);

			self::assertNotNull($e);
			self::assertSame('Error calling repeatable "boom"', $e->getMessage());
			self::assertSame(500, $e->getCode());
			self::assertInstanceOf(\DomainException::class, $e->getPrevious());
			self::assertSame('Inner failure', $e->getPrevious()->getMessage());

			return;
		}

		self::fail('An exception thrown inside a repeatable must be wrapped in a RuntimeException');
	}

	// -------------------------------------------------------------------------
	// Cache identifier carries the compiler output format version
	// -------------------------------------------------------------------------

	public function testBladeReportsAnOutputFormatVersion(): void
	{
		$blade = $this->makeContainer()->blade;

		self::assertInstanceOf(\Awf\Mvc\Compiler\VersionedCompilerInterface::class, $blade);
		self::assertNotSame('', $blade->getOutputFormatVersion());
	}

	public function testIdentifierDiffersFromTheUnversionedIdentifier(): void
	{
		$engine = new BladeEngine(new \ViewTestApp\View\Item\Html($this->makeContainer()));
		$path   = $this->templatePath('parent');

		self::assertNotSame(HashHelper::sha1($path), $this->invokeProtected($engine, 'getIdentifier', $path));
	}

	public function testIdentifierChangesWithTheOutputFormatVersion(): void
	{
		$container = $this->makeContainer();
		$engine    = new BladeEngine(new \ViewTestApp\View\Item\Html($container));
		$path      = $this->templatePath('parent');

		$current = $this->invokeProtected($engine, 'getIdentifier', $path);

		$this->setCompiler($engine, new FutureFormatBlade($container));

		$future = $this->invokeProtected($engine, 'getIdentifier', $path);

		self::assertNotSame($current, $future);
		self::assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $future);
	}

	public function testUnversionedCompilerKeepsTheLegacyIdentifier(): void
	{
		$engine = new BladeEngine(new \ViewTestApp\View\Item\Html($this->makeContainer()));
		$path   = $this->templatePath('parent');

		$this->setCompiler($engine, new UnversionedCompiler());

		self::assertSame(HashHelper::sha1($path), $this->invokeProtected($engine, 'getIdentifier', $path));
	}

	public function testCacheFilesFromTheOldCompilerAreNotUsed(): void
	{
		$path   = $this->templatePath('parent');
		$oldId  = HashHelper::sha1($path);
		$poison = "<?php throw new \\LogicException('Stale compiled template used'); ?>";

		// Old compiler, modern two-level storage.
		$oldModern = sprintf(
			'%s/compiled_templates/%s/%s/%s.php', $this->tmpDir, substr($oldId, 0, 1), substr($oldId, 1, 1), $oldId
		);
		// Old compiler, legacy flat storage.
		$oldLegacy = sprintf('%s/compiled_templates/%s.php', $this->tmpDir, $oldId);

		@mkdir(dirname($oldModern), 0777, true);
		file_put_contents($oldModern, $poison);
		file_put_contents($oldLegacy, $poison);

		// Make the old cache files look fresher than the source.
		$future = time() + 3600;
		touch($oldModern, $future, $future);
		touch($oldLegacy, $future, $future);

		$container = $this->makeContainer();
		$engine    = new BladeEngine(new \ViewTestApp\View\Item\Html($container));
		$result    = $engine->get($path);

		self::assertNotSame($oldModern, $result['content']);
		self::assertNotSame($oldLegacy, $result['content']);
		self::assertStringNotContainsString('Stale compiled template used', file_get_contents($result['content']));
		self::assertSame('[PARENT:A]', $this->render($container, 'parent'));
	}

	public function testLegacyFlatCacheFileIsNotMigratedForAVersionedCompiler(): void
	{
		$container = $this->makeContainer();
		$engine    = new BladeEngine(new \ViewTestApp\View\Item\Html($container));
		$path      = $this->templatePath('parent');
		$poison    = "<?php throw new \\LogicException('Stale compiled template used'); ?>";

		// Even a flat (legacy storage scheme) file at the NEW identifier must not be migrated: only an unversioned
		// compiler can ever have written into the legacy flat layout.
		$legacyNewId = $this->invokeProtected($engine, 'getCachePath', $path, true);
		@mkdir(dirname($legacyNewId), 0777, true);
		// Make sure the migration's rename() into the two-level folder would succeed.
		@mkdir(dirname($this->invokeProtected($engine, 'getCachePath', $path)), 0777, true);
		file_put_contents($legacyNewId, $poison);
		$future = time() + 3600;
		touch($legacyNewId, $future, $future);

		$result = $engine->get($path);

		self::assertStringNotContainsString('Stale compiled template used', file_get_contents($result['content']));
	}

	// -------------------------------------------------------------------------
	// OPcache busting targets the compiled file, not the source template
	// -------------------------------------------------------------------------

	public function testOpCacheIsBustedForTheCompiledCacheFile(): void
	{
		$view   = new \ViewTestApp\View\Item\Html($this->makeContainer());
		$engine = new SpyBladeEngine($view);
		$path   = $this->templatePath('parent');

		$result = $engine->get($path);

		self::assertSame([$result['content']], SpyBladeEngine::$bustedPaths);
		self::assertNotContains($path, SpyBladeEngine::$bustedPaths);
	}

	public function testOpCacheIsBustedForTheStreamPathWhenTheCacheIsNotWritable(): void
	{
		if (!\Awf\Utils\Buffer::canRegisterWrapper())
		{
			self::markTestSkipped('Cannot register stream wrappers on this host.');
		}

		SpyBladeEngine::$failCacheWrite = true;

		$view   = new \ViewTestApp\View\Item\Html($this->makeContainer());
		$engine = new SpyBladeEngine($view);
		$path   = $this->templatePath('parent');

		$result = $engine->get($path);

		self::assertStringStartsWith('awf://', $result['content']);
		self::assertSame([$result['content']], SpyBladeEngine::$bustedPaths);
	}
}
