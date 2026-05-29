<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Helper;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Helper\AbstractHelper;
use Awf\Helper\HelperInterface;
use Awf\Helper\HelperService;
use BadMethodCallException;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Fake helpers used across all tests
// ---------------------------------------------------------------------------

/**
 * Minimal helper with an explicit name.
 */
class FakeHelper extends AbstractHelper
{
	protected $helperPrefix = 'fake';

	public function greet(string $name): string
	{
		return 'Hello, ' . $name . '!';
	}

	public function doNothing(): void
	{
		// intentionally void
	}
}

/**
 * A second helper that is NOT ContainerAware to exercise the branch where
 * container injection is skipped.
 */
class PlainHelper implements HelperInterface
{
	public function getName(): string
	{
		return 'plain';
	}

	public function multiply(int $a, int $b): int
	{
		return $a * $b;
	}
}

/**
 * Helper whose class name is used to test automatic name derivation in
 * AbstractHelper::getName() when helperPrefix is empty.
 */
class AutoNamedHelper extends AbstractHelper
{
	// helperPrefix is intentionally left empty so getName() auto-derives it
}

/**
 * A class that does NOT implement HelperInterface, used to test error handling.
 */
class NotAHelper
{
	public function getName(): string
	{
		return 'notahelper';
	}
}

// ---------------------------------------------------------------------------
// Helpers for building a minimal Container
// ---------------------------------------------------------------------------

class HelperServiceTest extends TestCase
{
	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a minimal Container with autoloadHelpers disabled so no filesystem
	 * scanning occurs during construction.
	 */
	private function makeContainer(array $extra = []): Container
	{
		return new Container(
			array_merge(
				[
					'application_name'     => 'unittest',
					'applicationNamespace' => '\\UnitTest',
					'session_segment_name' => 'unittest_segment',
					'filesystemBase'       => '/dev/null',
					'basePath'             => '/dev/null',
					'templatePath'         => '/dev/null',
					'languagePath'         => '/dev/null',
					'temporaryPath'        => '/tmp',
					'sqlPath'              => '/dev/null',
					'autoloadHelpers'      => false,
				],
				$extra
			)
		);
	}

	/** Build a HelperService with no auto-loaded helpers. */
	private function makeService(array $extra = []): HelperService
	{
		return new HelperService($this->makeContainer($extra));
	}

	// =========================================================================
	// AbstractHelper::getName()
	// =========================================================================

	public function testAbstractHelperReturnsExplicitPrefix(): void
	{
		$helper = new FakeHelper();

		self::assertSame('fake', $helper->getName());
	}

	public function testAbstractHelperDerivesNameFromClassName(): void
	{
		$helper = new AutoNamedHelper();

		// Class is AutoNamedHelper → last segment lowercased → 'autonamedhelper'
		self::assertSame('autonamedhelper', $helper->getName());
	}

	public function testAbstractHelperCachesName(): void
	{
		$helper = new AutoNamedHelper();

		$first  = $helper->getName();
		$second = $helper->getName();

		self::assertSame($first, $second);
	}

	public function testAbstractHelperIsContainerAware(): void
	{
		$helper    = new FakeHelper();
		$container = $this->makeContainer();

		$helper->setContainer($container);

		self::assertSame($container, $helper->getContainer());
	}

	// =========================================================================
	// HelperService construction & container injection
	// =========================================================================

	public function testHelperServiceStoresContainer(): void
	{
		$container = $this->makeContainer();
		$service   = new HelperService($container);

		self::assertSame($container, $service->getContainer());
	}

	public function testAutoloadHelpersSkippedWhenFlagIsFalse(): void
	{
		// Container has autoloadHelpers = false and no helperPath/helperList.
		// No helpers must be registered.
		$service = $this->makeService(['autoloadHelpers' => false]);

		self::assertFalse($service->hasHelper('fake'));
	}

	public function testAutoloadHelpersWithHelperListRegistersClasses(): void
	{
		$container = $this->makeContainer(
			[
				'autoloadHelpers' => true,
				'helperList'      => [FakeHelper::class],
			]
		);

		$service = new HelperService($container);

		self::assertTrue($service->hasHelper('fake'));
	}

	public function testAutoloadHelpersIgnoresNonExistentClassesGracefully(): void
	{
		$container = $this->makeContainer(
			[
				'autoloadHelpers' => true,
				'helperList'      => ['NoSuchClass\\ThatDoesNotExist'],
			]
		);

		// Must not throw
		$service = new HelperService($container);

		self::assertFalse($service->hasHelper('NoSuchClass\\ThatDoesNotExist'));
	}

	// =========================================================================
	// registerHelper / registerHelperClass
	// =========================================================================

	public function testRegisterHelperWithExplicitName(): void
	{
		$service = $this->makeService();
		$helper  = new FakeHelper();

		$service->registerHelper('myalias', $helper);

		self::assertTrue($service->hasHelper('myalias'));
	}

	public function testRegisterHelperWithEmptyNameUsesGetName(): void
	{
		$service = $this->makeService();
		$helper  = new FakeHelper();

		$service->registerHelper('', $helper);

		self::assertTrue($service->hasHelper('fake'));
	}

	public function testRegisterHelperClassRegistersAndInjectsContainer(): void
	{
		$service = $this->makeService();

		$service->registerHelperClass(FakeHelper::class);

		self::assertTrue($service->hasHelper('fake'));
		$retrieved = $service->get('fake');
		self::assertInstanceOf(ContainerAwareInterface::class, $retrieved);
		// The container must have been injected
		self::assertSame($service->getContainer(), $retrieved->getContainer());
	}

	public function testRegisterHelperClassSkipsContainerInjectionForNonAwareHelper(): void
	{
		$service = $this->makeService();

		$service->registerHelperClass(PlainHelper::class);

		self::assertTrue($service->hasHelper('plain'));
	}

	public function testRegisterHelperClassThrowsForNonExistentClass(): void
	{
		$service = $this->makeService();

		$this->expectException(InvalidArgumentException::class);

		$service->registerHelperClass('NonExistent\\FakeClass');
	}

	public function testRegisterHelperClassThrowsForClassNotImplementingInterface(): void
	{
		$service = $this->makeService();

		$this->expectException(InvalidArgumentException::class);

		$service->registerHelperClass(NotAHelper::class);
	}

	// =========================================================================
	// unregisterHelper / unregisterHelperClass
	// =========================================================================

	public function testUnregisterHelperByName(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		$service->unregisterHelper('fake');

		self::assertFalse($service->hasHelper('fake'));
	}

	public function testUnregisterHelperByNameIsNoopForUnknownName(): void
	{
		$service = $this->makeService();

		// Must not throw
		$service->unregisterHelper('nonexistent');

		self::assertFalse($service->hasHelper('nonexistent'));
	}

	public function testUnregisterHelperClassByClass(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);
		$service->registerHelperClass(PlainHelper::class);

		$service->unregisterHelperClass(FakeHelper::class);

		self::assertFalse($service->hasHelper('fake'));
		self::assertTrue($service->hasHelper('plain'));
	}

	public function testUnregisterHelperClassIsNoopForUnknownClass(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		// Must not throw or remove other helpers
		$service->unregisterHelperClass(PlainHelper::class);

		self::assertTrue($service->hasHelper('fake'));
	}

	// =========================================================================
	// hasHelper / hasHelperClass
	// =========================================================================

	public function testHasHelperReturnsTrueForRegistered(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		self::assertTrue($service->hasHelper('fake'));
	}

	public function testHasHelperReturnsFalseForUnregistered(): void
	{
		$service = $this->makeService();

		self::assertFalse($service->hasHelper('nonexistent'));
	}

	public function testHasHelperClassReturnsTrueForRegistered(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		self::assertTrue($service->hasHelperClass(FakeHelper::class));
	}

	public function testHasHelperClassReturnsFalseForUnregistered(): void
	{
		$service = $this->makeService();

		self::assertFalse($service->hasHelperClass(FakeHelper::class));
	}

	// =========================================================================
	// get()
	// =========================================================================

	public function testGetReturnsRegisteredHelperInstance(): void
	{
		$service = $this->makeService();
		$helper  = new FakeHelper();

		$service->registerHelper('fake', $helper);

		self::assertSame($helper, $service->get('fake'));
	}

	public function testGetReturnsSameInstanceOnRepeatedCalls(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		$first  = $service->get('fake');
		$second = $service->get('fake');

		self::assertSame($first, $second);
	}

	public function testGetThrowsOutOfRangeForUnknownHelper(): void
	{
		$service = $this->makeService();

		$this->expectException(OutOfRangeException::class);

		$service->get('nonexistent');
	}

	// =========================================================================
	// __get() magic
	// =========================================================================

	public function testMagicGetAccessesHelperByName(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		$retrieved = $service->fake;

		self::assertInstanceOf(FakeHelper::class, $retrieved);
	}

	public function testMagicGetThrowsOutOfRangeForUnknownHelper(): void
	{
		$service = $this->makeService();

		$this->expectException(OutOfRangeException::class);

		// Silence the PHP notice for undefined property; we only care about the exception
		$_ = $service->nonexistent;
	}

	// =========================================================================
	// run()
	// =========================================================================

	public function testRunCallsHelperMethodAndReturnsValue(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		$result = $service->run('fake.greet', 'World');

		self::assertSame('Hello, World!', $result);
	}

	public function testRunVoidMethodReturnsNull(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		$result = $service->run('fake.doNothing');

		self::assertNull($result);
	}

	public function testRunThrowsInvalidArgumentForMissingDot(): void
	{
		$service = $this->makeService();

		$this->expectException(InvalidArgumentException::class);

		$service->run('noDotHere');
	}

	public function testRunThrowsOutOfRangeForUnknownHelper(): void
	{
		$service = $this->makeService();

		$this->expectException(OutOfRangeException::class);

		$service->run('ghost.method');
	}

	public function testRunThrowsBadMethodCallForUnknownMethod(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(FakeHelper::class);

		$this->expectException(BadMethodCallException::class);

		$service->run('fake.nonExistentMethod');
	}

	public function testRunPassesMultipleArgumentsToMethod(): void
	{
		$service = $this->makeService();
		$service->registerHelperClass(PlainHelper::class);

		$result = $service->run('plain.multiply', 3, 7);

		self::assertSame(21, $result);
	}
}
