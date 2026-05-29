<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Container;

use Awf\Container\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
	/**
	 * A complete set of scalar values that prevents the constructor from emitting any
	 * E_USER_WARNING / E_USER_NOTICE. Tests that need to exercise the fallback logic
	 * deliberately omit individual keys (and suppress the resulting diagnostics).
	 *
	 * @return array<string, mixed>
	 */
	private function fullScalars(): array
	{
		return [
			'application_name'     => 'unittest',
			'applicationNamespace' => '\\UnitTest',
			'session_segment_name' => 'unittest_segment',
			'filesystemBase'       => '/var/www/example',
			'basePath'             => '/var/www/example/UnitTest',
			'templatePath'         => '/var/www/example/templates',
			'languagePath'         => '/var/www/example/languages',
			'temporaryPath'        => '/var/www/example/tmp',
			'sqlPath'              => '/var/www/example/sql',
		];
	}

	// -------------------------------------------------------------------------
	// Construction & scalar values
	// -------------------------------------------------------------------------

	public function testConstructWithExplicitScalarsStoresThem(): void
	{
		$container = new Container($this->fullScalars());

		$this->assertSame('unittest', $container->application_name);
		$this->assertSame('\\UnitTest', $container->applicationNamespace);
		$this->assertSame('unittest_segment', $container->session_segment_name);
		$this->assertSame('/var/www/example', $container->filesystemBase);
	}

	public function testDefaultConstantPrefixIsApath(): void
	{
		$container = new Container($this->fullScalars());

		$this->assertSame('APATH_', $container->constantPrefix);
	}

	public function testCustomConstantPrefixWins(): void
	{
		$values               = $this->fullScalars();
		$values['constantPrefix'] = 'FOOBAR_';

		$container = new Container($values);

		$this->assertSame('FOOBAR_', $container->constantPrefix);
	}

	public function testDefaultScalarDefaultsArePresent(): void
	{
		$container = new Container($this->fullScalars());

		$this->assertTrue($container->autoloadHelpers);
		$this->assertSame([], $container->helperList);
		$this->assertNull($container->helperPath);
		$this->assertNull($container->mediaQueryKey);
	}

	// -------------------------------------------------------------------------
	// Path management — explicit values from constructor
	// -------------------------------------------------------------------------

	public function testPathsResolveFromConstructorValues(): void
	{
		$container = new Container($this->fullScalars());

		$this->assertSame('/var/www/example/UnitTest', $container->basePath);
		$this->assertSame('/var/www/example/templates', $container->templatePath);
		$this->assertSame('/var/www/example/languages', $container->languagePath);
		$this->assertSame('/var/www/example/tmp', $container->temporaryPath);
		$this->assertSame('/var/www/example/sql', $container->sqlPath);
	}

	public function testExplicitPathsOverrideConstantFallback(): void
	{
		// Even when constants are defined, explicitly-passed paths must win.
		$this->ensureConstantsDefined();

		$values                 = $this->fullScalars();
		$values['constantPrefix'] = 'AWFTEST_';
		$values['templatePath'] = '/explicit/templates';

		$container = new Container($values);

		$this->assertSame('/explicit/templates', $container->templatePath);
	}

	// -------------------------------------------------------------------------
	// Path management — PHP constant fallback (constantPrefix based)
	// -------------------------------------------------------------------------

	public function testConstantFallbackForPaths(): void
	{
		$this->ensureConstantsDefined();

		// Provide the scalars that have no constant fallback so we avoid warnings
		// for those, but omit the path scalars so the constant fallback kicks in.
		$container = @new Container(
			[
				'constantPrefix'       => 'AWFTEST_',
				'application_name'     => 'unittest',
				'applicationNamespace' => '\\UnitTest',
				'session_segment_name' => 'unittest_segment',
			]
		);

		$this->assertSame('/awftest/base', $container->filesystemBase);
		$this->assertSame('/awftest/base/Unittest', $container->basePath);
		$this->assertSame('/awftest/themes', $container->templatePath);
		$this->assertSame('/awftest/translation', $container->languagePath);
		$this->assertSame('/awftest/tmp', $container->temporaryPath);
		$this->assertSame('/awftest/sql', $container->sqlPath);
	}

	public function testBasePathConstantFallbackCapitalisesApplicationName(): void
	{
		$this->ensureConstantsDefined();

		$container = @new Container(
			[
				'constantPrefix'       => 'AWFTEST_',
				'application_name'     => 'mylowercaseapp',
				'applicationNamespace' => '\\MyApp',
				'session_segment_name' => 'seg',
			]
		);

		// basePath = AWFTEST_BASE . '/' . ucfirst(application_name)
		$this->assertSame('/awftest/base/Mylowercaseapp', $container->basePath);
	}

	// -------------------------------------------------------------------------
	// Service registration — lazy, NOT resolved
	// -------------------------------------------------------------------------

	public static function defaultServicesProvider(): array
	{
		return [
			['application'],
			['mvcFactory'],
			['appConfig'],
			['blade'],
			['db'],
			['dispatcher'],
			['eventDispatcher'],
			['fileSystem'],
			['input'],
			['mailer'],
			['router'],
			['session'],
			['segment'],
			['userManager'],
			['dateFactory'],
			['languageFactory'],
			['language'],
			['html'],
			['helper'],
		];
	}

	#[DataProvider('defaultServicesProvider')]
	public function testDefaultServiceIsRegistered(string $serviceKey): void
	{
		$container = new Container($this->fullScalars());

		$this->assertContains($serviceKey, $container->keys());
		$this->assertTrue($container->offsetExists($serviceKey));
	}

	#[DataProvider('defaultServicesProvider')]
	public function testDefaultServiceIsRegisteredAsLazyProvider(string $serviceKey): void
	{
		$container = new Container($this->fullScalars());

		// raw() must return the unresolved service provider object, never a resolved
		// instance. Crucially this never triggers the service closure (no db/session/etc).
		// The default providers are invokable objects under Awf\Container\Defaults.
		$raw = $container->raw($serviceKey);

		$this->assertIsObject($raw);
		$this->assertTrue(method_exists($raw, '__invoke'));
		$this->assertStringStartsWith('Awf\\', $raw::class);
	}

	// -------------------------------------------------------------------------
	// Service overrides — custom values win over defaults
	// -------------------------------------------------------------------------

	public function testCustomServiceOverrideWinsOverDefault(): void
	{
		$marker = new \stdClass();
		$marker->iAmTheOverride = true;

		$values       = $this->fullScalars();
		$values['db'] = static function () use ($marker) {
			return $marker;
		};

		$container = new Container($values);

		// The default DatabaseProvider must have been replaced by our closure.
		$this->assertSame($marker, $container->db);
	}

	public function testCustomScalarOverrideWinsOverDefault(): void
	{
		$values                   = $this->fullScalars();
		$values['autoloadHelpers'] = false;
		$values['helperList']     = ['Foo', 'Bar'];

		$container = new Container($values);

		$this->assertFalse($container->autoloadHelpers);
		$this->assertSame(['Foo', 'Bar'], $container->helperList);
	}

	// -------------------------------------------------------------------------
	// Pimple behaviour inherited by Container
	// -------------------------------------------------------------------------

	public function testAccessingUndefinedIdentifierThrows(): void
	{
		$container = new Container($this->fullScalars());

		$this->expectException(\InvalidArgumentException::class);

		$container->offsetGet('thisDoesNotExist');
	}

	public function testRawOnUndefinedIdentifierThrows(): void
	{
		$container = new Container($this->fullScalars());

		$this->expectException(\InvalidArgumentException::class);

		$container->raw('thisDoesNotExist');
	}

	// -------------------------------------------------------------------------
	// __call magic method (factory invocation)
	// -------------------------------------------------------------------------

	public function testCallInvokesStoredCallable(): void
	{
		// We must register a protected callable so Pimple does not treat it as a service.
		$container = new Container($this->fullScalars());
		$container['adder'] = $container->protect(
			static function (int $a, int $b): int {
				return $a + $b;
			}
		);

		$this->assertSame(5, $container->adder(2, 3));
	}

	public function testCallOnNonCallableThrowsBadMethodCall(): void
	{
		$container = new Container($this->fullScalars());

		// 'application_name' is a plain string, not callable.
		$this->expectException(\BadMethodCallException::class);

		$container->application_name('foo');
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Define the AWFTEST_* constants used by the constant-fallback tests, once.
	 * Uses a dedicated prefix that never collides with a real application's APATH_*.
	 */
	private function ensureConstantsDefined(): void
	{
		$constants = [
			'AWFTEST_BASE'        => '/awftest/base',
			'AWFTEST_THEMES'      => '/awftest/themes',
			'AWFTEST_TRANSLATION' => '/awftest/translation',
			'AWFTEST_TMP'         => '/awftest/tmp',
			'AWFTEST_SQL'         => '/awftest/sql',
		];

		foreach ($constants as $name => $value)
		{
			if (!defined($name))
			{
				define($name, $value);
			}
		}
	}
}
