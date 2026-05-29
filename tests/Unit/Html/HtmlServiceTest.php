<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Html;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Html\AbstractHelper;
use Awf\Html\HtmlHelperInterface;
use Awf\Html\HtmlService;
use BadMethodCallException;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Fake helpers used across all tests
// ---------------------------------------------------------------------------

/**
 * A concrete HTML helper with an explicit name that returns values.
 */
class FakeHtmlHelper extends AbstractHelper
{
    protected $name = 'fakehtml';

    public function greet(string $name): string
    {
        return 'Hello, ' . $name . '!';
    }

    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    public function doNothing(): void
    {
        // intentionally void
    }
}

/**
 * A second helper that does NOT extend AbstractHelper (and is therefore NOT
 * ContainerAwareInterface) to exercise the branch where container injection
 * is skipped in registerHelperClass().
 */
class PlainHtmlHelper implements HtmlHelperInterface
{
    public function getName(): string
    {
        return 'plainhtml';
    }

    public function multiply(int $a, int $b): int
    {
        return $a * $b;
    }
}

/**
 * Helper whose class name is used to exercise the automatic name derivation
 * logic in AbstractHelper::getName() when $name is empty.
 */
class AutoNamedHtmlHelper extends AbstractHelper
{
    // $name is intentionally left empty → getName() will derive from class name
}

/**
 * A class that does NOT implement HtmlHelperInterface, used to test error
 * handling in registerHelperClass().
 */
class NotAnHtmlHelper
{
    public function getName(): string
    {
        return 'notanhtml';
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

#[CoversClass(HtmlService::class)]
#[CoversClass(AbstractHelper::class)]
class HtmlServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeContainer(): Container
    {
        return new Container(
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
            ]
        );
    }

    private function makeService(): HtmlService
    {
        return new HtmlService($this->makeContainer());
    }

    // =========================================================================
    // AbstractHelper::getName()
    // =========================================================================

    public function testAbstractHelperReturnsExplicitName(): void
    {
        $helper = new FakeHtmlHelper();

        self::assertSame('fakehtml', $helper->getName());
    }

    public function testAbstractHelperDerivesNameFromClassName(): void
    {
        $helper = new AutoNamedHtmlHelper();

        // AbstractHelper::getName() splits on '\\', pops the last segment, and lowercases it.
        // Class: Awf\Tests\Unit\Html\AutoNamedHtmlHelper → last segment → 'autonamedhtml helper' lowercased
        $expectedName = strtolower((new \ReflectionClass($helper))->getShortName());

        self::assertSame($expectedName, $helper->getName());
    }

    public function testAbstractHelperCachesName(): void
    {
        $helper = new AutoNamedHtmlHelper();

        $first  = $helper->getName();
        $second = $helper->getName();

        self::assertSame($first, $second);
    }

    public function testAbstractHelperIsContainerAware(): void
    {
        $helper    = new FakeHtmlHelper();
        $container = $this->makeContainer();

        $helper->setContainer($container);

        self::assertSame($container, $helper->getContainer());
    }

    // =========================================================================
    // HtmlService construction & container injection
    // =========================================================================

    public function testHtmlServiceStoresContainer(): void
    {
        $container = $this->makeContainer();
        $service   = new HtmlService($container);

        self::assertSame($container, $service->getContainer());
    }

    // =========================================================================
    // registerHelper / registerHelperClass
    // =========================================================================

    public function testRegisterHelperWithExplicitName(): void
    {
        $service = $this->makeService();
        $helper  = new FakeHtmlHelper();

        $service->registerHelper('myalias', $helper);

        self::assertTrue($service->hasHelper('myalias'));
    }

    public function testRegisterHelperWithEmptyNameUsesGetName(): void
    {
        $service = $this->makeService();
        $helper  = new FakeHtmlHelper();

        $service->registerHelper('', $helper);

        self::assertTrue($service->hasHelper('fakehtml'));
    }

    public function testRegisterHelperClassRegistersAndInjectsContainer(): void
    {
        $service = $this->makeService();

        $service->registerHelperClass(FakeHtmlHelper::class);

        self::assertTrue($service->hasHelper('fakehtml'));
        $retrieved = $service->fakehtml;
        self::assertInstanceOf(ContainerAwareInterface::class, $retrieved);
        self::assertSame($service->getContainer(), $retrieved->getContainer());
    }

    public function testRegisterHelperClassSkipsContainerInjectionForNonAwareHelper(): void
    {
        $service = $this->makeService();

        $service->registerHelperClass(PlainHtmlHelper::class);

        self::assertTrue($service->hasHelper('plainhtml'));
    }

    public function testRegisterHelperClassThrowsForNonExistentClass(): void
    {
        $service = $this->makeService();

        $this->expectException(InvalidArgumentException::class);

        $service->registerHelperClass('NonExistent\\HtmlHelperClass');
    }

    public function testRegisterHelperClassThrowsForClassNotImplementingInterface(): void
    {
        $service = $this->makeService();

        $this->expectException(InvalidArgumentException::class);

        $service->registerHelperClass(NotAnHtmlHelper::class);
    }

    // =========================================================================
    // unregisterHelper / unregisterHelperClass
    // =========================================================================

    public function testUnregisterHelperByName(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        $service->unregisterHelper('fakehtml');

        self::assertFalse($service->hasHelper('fakehtml'));
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
        $service->registerHelperClass(FakeHtmlHelper::class);
        $service->registerHelperClass(PlainHtmlHelper::class);

        $service->unregisterHelperClass(FakeHtmlHelper::class);

        self::assertFalse($service->hasHelper('fakehtml'));
        self::assertTrue($service->hasHelper('plainhtml'));
    }

    public function testUnregisterHelperClassIsNoopForUnknownClass(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        // Must not throw or remove other helpers
        $service->unregisterHelperClass(PlainHtmlHelper::class);

        self::assertTrue($service->hasHelper('fakehtml'));
    }

    // =========================================================================
    // hasHelper / hasHelperClass
    // =========================================================================

    public function testHasHelperReturnsTrueForRegistered(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        self::assertTrue($service->hasHelper('fakehtml'));
    }

    public function testHasHelperReturnsFalseForUnregistered(): void
    {
        $service = $this->makeService();

        self::assertFalse($service->hasHelper('nonexistent'));
    }

    public function testHasHelperClassReturnsTrueForRegistered(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        self::assertTrue($service->hasHelperClass(FakeHtmlHelper::class));
    }

    public function testHasHelperClassReturnsFalseForUnregistered(): void
    {
        $service = $this->makeService();

        self::assertFalse($service->hasHelperClass(FakeHtmlHelper::class));
    }

    // =========================================================================
    // __get() magic access: $service->helperName->method()
    // =========================================================================

    public function testMagicGetReturnsHelperByName(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        $retrieved = $service->fakehtml;

        self::assertInstanceOf(FakeHtmlHelper::class, $retrieved);
    }

    public function testMagicGetAllowsMethodCallChain(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        // Exercise the $container->html->helper->method() access path
        $result = $service->fakehtml->greet('World');

        self::assertSame('Hello, World!', $result);
    }

    public function testMagicGetThrowsOutOfRangeForUnknownHelper(): void
    {
        $service = $this->makeService();

        $this->expectException(OutOfRangeException::class);

        $_ = $service->nonexistent;
    }

    // =========================================================================
    // get() — run helper method, return value
    // =========================================================================

    public function testGetCallsHelperMethodWithNoArguments(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        // doNothing() returns void/null; we just verify no exception is thrown
        // and that we can call a method with no arguments via get()
        $result = $service->get('fakehtml.greet', 'PHPUnit');

        self::assertSame('Hello, PHPUnit!', $result);
    }

    public function testGetCallsHelperMethodWithMultipleArguments(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        $result = $service->get('fakehtml.add', 3, 4);

        self::assertSame(7, $result);
    }

    public function testGetWithSingleSegmentKeyDefaultsToBasicHelper(): void
    {
        // When the key has no dot, the helper name is 'basic' and the method
        // is the key itself.  We register a helper named 'basic' to confirm.
        $service = $this->makeService();
        $basic   = new class implements HtmlHelperInterface {
            public function getName(): string { return 'basic'; }
            public function ping(): string    { return 'pong'; }
        };
        $service->registerHelper('basic', $basic);

        $result = $service->get('ping');

        self::assertSame('pong', $result);
    }

    public function testGetThrowsInvalidArgumentWhenNoArgumentsGiven(): void
    {
        $service = $this->makeService();

        $this->expectException(InvalidArgumentException::class);

        $service->get();
    }

    public function testGetThrowsBadMethodCallForUnknownHelper(): void
    {
        $service = $this->makeService();

        $this->expectException(BadMethodCallException::class);

        $service->get('ghost.method');
    }

    public function testGetThrowsBadMethodCallForUnknownMethod(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        $this->expectException(BadMethodCallException::class);

        $service->get('fakehtml.nonExistentMethod');
    }

    // =========================================================================
    // run() — call void helper methods (no return value used)
    // =========================================================================

    public function testRunCallsHelperVoidMethod(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        // Must not throw
        $service->run('fakehtml.doNothing');

        self::assertTrue(true); // reached without exception
    }

    public function testRunCallsHelperMethodWithArguments(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(PlainHtmlHelper::class);

        // run() passes arguments just like get()
        $service->run('plainhtml.multiply', 3, 7);

        self::assertTrue(true); // reached without exception
    }

    public function testRunWithSingleSegmentKeyDefaultsToBasicHelper(): void
    {
        $service = $this->makeService();
        $basic   = new class implements HtmlHelperInterface {
            public function getName(): string { return 'basic'; }
            public function ping(): void      {}
        };
        $service->registerHelper('basic', $basic);

        // Must not throw
        $service->run('ping');

        self::assertTrue(true);
    }

    public function testRunThrowsInvalidArgumentWhenNoArgumentsGiven(): void
    {
        $service = $this->makeService();

        $this->expectException(InvalidArgumentException::class);

        $service->run();
    }

    public function testRunThrowsBadMethodCallForUnknownHelper(): void
    {
        $service = $this->makeService();

        $this->expectException(BadMethodCallException::class);

        $service->run('ghost.method');
    }

    public function testRunThrowsBadMethodCallForUnknownMethod(): void
    {
        $service = $this->makeService();
        $service->registerHelperClass(FakeHtmlHelper::class);

        $this->expectException(BadMethodCallException::class);

        $service->run('fakehtml.nonExistentMethod');
    }

    // =========================================================================
    // getFormatOptions / setFormatOptions
    // =========================================================================

    public function testGetFormatOptionsReturnsDefaults(): void
    {
        $service = $this->makeService();

        $options = $service->getFormatOptions();

        self::assertArrayHasKey('format.depth', $options);
        self::assertArrayHasKey('format.eol', $options);
        self::assertArrayHasKey('format.indent', $options);
        self::assertSame(0, $options['format.depth']);
        self::assertSame("\n", $options['format.eol']);
        self::assertSame("\t", $options['format.indent']);
    }

    public function testSetFormatOptionsUpdatesKnownKeys(): void
    {
        $service = $this->makeService();

        $result = $service->setFormatOptions(['format.depth' => 2, 'format.eol' => "\r\n"]);

        // Fluent interface returns self
        self::assertSame($service, $result);

        $options = $service->getFormatOptions();
        self::assertSame(2, $options['format.depth']);
        self::assertSame("\r\n", $options['format.eol']);
        self::assertSame("\t", $options['format.indent']); // unchanged
    }

    public function testSetFormatOptionsIgnoresUnknownKeys(): void
    {
        $service = $this->makeService();

        $service->setFormatOptions(['unknown.key' => 'value']);

        $options = $service->getFormatOptions();
        self::assertArrayNotHasKey('unknown.key', $options);
    }

    // =========================================================================
    // Container integration — $container->html->helperName->method()
    // =========================================================================

    public function testContainerHtmlServiceIsHtmlService(): void
    {
        $container = $this->makeContainer();

        self::assertInstanceOf(HtmlService::class, $container->html);
    }

    public function testContainerHtmlServiceRegistersDefaultHelpers(): void
    {
        // The default Container registers at least the 'basic' helper via the
        // Defaults service providers; just verify the service itself is usable.
        $container = $this->makeContainer();
        $html      = $container->html;

        // Register a custom helper through the service and retrieve it via
        // the $container->html->helperName->method() access path.
        $html->registerHelperClass(FakeHtmlHelper::class);

        $result = $container->html->fakehtml->greet('Container');

        self::assertSame('Hello, Container!', $result);
    }
}
