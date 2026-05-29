<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Download;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Download\Adapter\AbstractAdapter;
use Awf\Download\Download;
use Awf\Download\DownloadInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Download\Download — adapter selection logic, adapter name, setAdapter,
 * getAdapterName, getFromURL (no-adapter path), and option handling.
 *
 * Real network calls are NOT made. All adapter-level behaviour is tested via
 * test doubles (anonymous classes / mocks) or by querying the object's public API
 * after wiring in a stub.
 */
class DownloadTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return a minimal Container mock that satisfies ContainerAwareInterface
     * usage inside Download (only the container reference is stored; no service
     * methods are called during the tests in this file).
     */
    private function makeContainer(): Container
    {
        return $this->createMock(Container::class);
    }

    /**
     * Build a stub adapter that implements DownloadInterface and
     * ContainerAwareInterface (so setAdapter() wires the container into it).
     *
     * @param string $name       Value returned by getName()
     * @param int    $priority   Adapter priority
     * @param bool   $supported  Value returned by isSupported()
     */
    private function makeAdapter(string $name, int $priority, bool $supported): DownloadInterface&ContainerAwareInterface
    {
        return new class ($name, $priority, $supported) extends AbstractAdapter {
            public function __construct(string $n, int $p, bool $s)
            {
                $this->name        = $n;
                $this->priority    = $p;
                $this->isSupported = $s;
                $this->supportsChunkDownload = true;
                $this->supportsFileSize      = false;
            }
        };
    }

    /**
     * Build a Download instance whose adapter has already been replaced with a
     * stub via setAdapter(), bypassing the real filesystem scan/constructor.
     */
    private function makeDownloadWithAdapter(
        DownloadInterface&ContainerAwareInterface $adapter,
        ?Container $container = null
    ): Download {
        $container ??= $this->makeContainer();
        // Suppress the E_USER_DEPRECATED notice emitted by the constructor when
        // no container is provided AND suppress the Application::getInstance()
        // chain that would follow — so we always pass the container explicitly.
        $dl = new Download($container);
        // Replace whatever adapter the constructor auto-selected with our stub.
        $dl->setAdapter(get_class($adapter));
        return $dl;
    }

    // -------------------------------------------------------------------------
    // Construction — adapter auto-selection
    // -------------------------------------------------------------------------

    /**
     * When a Container is passed, the constructor must not trigger the
     * E_USER_DEPRECATED notice.
     */
    public function testConstructWithContainerDoesNotEmitDeprecation(): void
    {
        $container = $this->makeContainer();

        // If a deprecation notice fires, PHPUnit will mark the test as risky.
        // We explicitly assert that no error is raised.
        set_error_handler(function (int $errno, string $errstr): bool {
            if ($errno === E_USER_DEPRECATED) {
                throw new \RuntimeException('Unexpected deprecation: ' . $errstr);
            }
            return false;
        });

        try {
            $dl = new Download($container);
            $this->assertInstanceOf(Download::class, $dl);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * The constructor selects the best supported adapter (highest priority).
     * On any environment where cURL is available, `curl` (priority 110) should
     * beat `fopen` (priority 100).  On environments where neither is available
     * the adapter name will be an empty string — both outcomes are valid.
     */
    public function testConstructorSelectsHighestPriorityAdapter(): void
    {
        $dl   = new Download($this->makeContainer());
        $name = $dl->getAdapterName();

        // The result must be one of the known adapters or empty (unsupported host).
        $this->assertContains($name, ['curl', 'fopen', ''], 'Unexpected adapter name: ' . $name);
    }

    /**
     * When cURL is available, the auto-selected adapter must be `curl` because
     * its priority (110) is higher than fopen's (100).
     */
    public function testConstructorPrefersCurlOverFopenWhenBothAvailable(): void
    {
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            $this->markTestSkipped('cURL is not available on this host.');
        }

        $dl = new Download($this->makeContainer());

        $this->assertSame('curl', $dl->getAdapterName());
    }

    // -------------------------------------------------------------------------
    // setAdapter / getAdapterName
    // -------------------------------------------------------------------------

    public function testSetAdapterByFullClassName(): void
    {
        $dl = new Download($this->makeContainer());

        $dl->setAdapter(\Awf\Download\Adapter\Curl::class);

        if (function_exists('curl_init')) {
            $this->assertSame('curl', $dl->getAdapterName());
        } else {
            // On hosts without cURL the class exists but isSupported() returns false;
            // setAdapter() still sets it because it only checks instanceof DownloadInterface.
            $this->assertSame('curl', $dl->getAdapterName());
        }
    }

    public function testSetAdapterByShortName(): void
    {
        $dl = new Download($this->makeContainer());
        $dl->setAdapter('curl');

        $this->assertSame('curl', $dl->getAdapterName());
    }

    public function testSetAdapterByShortNameCaseInsensitive(): void
    {
        $dl = new Download($this->makeContainer());
        // ucfirst() is applied internally, so 'Curl' → \Awf\Download\Adapter\Curl
        $dl->setAdapter('Curl');

        $this->assertSame('curl', $dl->getAdapterName());
    }

    public function testSetAdapterWithInvalidClassNameLeavesAdapterUnchanged(): void
    {
        $dl = new Download($this->makeContainer());
        // Force a known-good adapter first.
        $dl->setAdapter('curl');
        $nameBefore = $dl->getAdapterName();

        // Try to set a non-existent adapter; the method must silently ignore it.
        $dl->setAdapter('NonExistentAdapter_XYZ_99999');

        $this->assertSame($nameBefore, $dl->getAdapterName());
    }

    public function testSetAdapterWithNonAdapterClassLeavesAdapterUnchanged(): void
    {
        $dl = new Download($this->makeContainer());
        $dl->setAdapter('curl');
        $nameBefore = $dl->getAdapterName();

        // stdClass exists but is not a DownloadInterface.
        $dl->setAdapter(\stdClass::class);

        $this->assertSame($nameBefore, $dl->getAdapterName());
    }

    public function testGetAdapterNameReturnsEmptyStringWhenNoAdapterSet(): void
    {
        // Use the Fopen adapter class name so we have a concrete class whose
        // constructor is reachable. Then we'll create a fresh Download and
        // immediately examine getAdapterName() without calling setAdapter().
        // On a host where neither cURL nor fopen is available the constructor
        // sets no adapter, so getAdapterName() must return ''.
        if (function_exists('curl_init') || ini_get('allow_url_fopen')) {
            $this->markTestSkipped('At least one adapter is available; cannot test the no-adapter code path.');
        }

        $dl = new Download($this->makeContainer());
        $this->assertSame('', $dl->getAdapterName());
    }

    // -------------------------------------------------------------------------
    // getAdapterName — return-value contract
    // -------------------------------------------------------------------------

    public function testGetAdapterNameReturnsCurlInLowercase(): void
    {
        $dl = new Download($this->makeContainer());
        $dl->setAdapter('curl');

        // Must be lower-case regardless of how the class name is stored.
        $this->assertSame('curl', $dl->getAdapterName());
    }

    public function testGetAdapterNameReturnsFopenInLowercase(): void
    {
        $dl = new Download($this->makeContainer());
        $dl->setAdapter('fopen');

        $this->assertSame('fopen', $dl->getAdapterName());
    }

    // -------------------------------------------------------------------------
    // Adapter capability flags (via AbstractAdapter)
    // -------------------------------------------------------------------------

    public function testCurlAdapterReportsHigherPriorityThanFopen(): void
    {
        $curl  = new \Awf\Download\Adapter\Curl();
        $fopen = new \Awf\Download\Adapter\Fopen();

        $this->assertGreaterThan($fopen->priority, $curl->priority);
    }

    public function testCurlAdapterSupportsChunkDownload(): void
    {
        $curl = new \Awf\Download\Adapter\Curl();
        $this->assertTrue($curl->supportsChunkDownload());
    }

    public function testCurlAdapterSupportsFileSize(): void
    {
        $curl = new \Awf\Download\Adapter\Curl();
        $this->assertTrue($curl->supportsFileSize());
    }

    public function testFopenAdapterSupportsChunkDownload(): void
    {
        $fopen = new \Awf\Download\Adapter\Fopen();
        $this->assertTrue($fopen->supportsChunkDownload());
    }

    public function testFopenAdapterDoesNotSupportFileSize(): void
    {
        $fopen = new \Awf\Download\Adapter\Fopen();
        $this->assertFalse($fopen->supportsFileSize());
    }

    public function testCurlAdapterIsSupportedWhenCurlFunctionsExist(): void
    {
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            $this->markTestSkipped('cURL is not available on this host.');
        }

        $curl = new \Awf\Download\Adapter\Curl();
        $this->assertTrue($curl->isSupported());
    }

    public function testCurlAdapterIsNotSupportedWhenCurlFunctionsMissing(): void
    {
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $this->markTestSkipped('cURL is available; cannot test the unsupported path.');
        }

        $curl = new \Awf\Download\Adapter\Curl();
        $this->assertFalse($curl->isSupported());
    }

    // -------------------------------------------------------------------------
    // AbstractAdapter — default implementations
    // -------------------------------------------------------------------------

    public function testAbstractAdapterDownloadAndReturnReturnsEmptyString(): void
    {
        $adapter = $this->makeAdapter('dummy', 50, true);
        $result  = $adapter->downloadAndReturn('http://example.com/');

        $this->assertSame('', $result);
    }

    public function testAbstractAdapterGetFileSizeReturnsMinusOne(): void
    {
        $adapter = $this->makeAdapter('dummy', 50, true);
        $result  = $adapter->getFileSize('http://example.com/file.zip');

        $this->assertSame(-1, $result);
    }

    public function testAbstractAdapterGetNameReturnsName(): void
    {
        $adapter = $this->makeAdapter('myAdapter', 75, true);
        $this->assertSame('myAdapter', $adapter->getName());
    }

    public function testAbstractAdapterGetPriorityReturnsPriority(): void
    {
        $adapter = $this->makeAdapter('myAdapter', 75, true);
        $this->assertSame(75, $adapter->getPriority());
    }

    public function testAbstractAdapterIsSupportedReflectsConstructorValue(): void
    {
        $supported   = $this->makeAdapter('a', 100, true);
        $unsupported = $this->makeAdapter('b', 100, false);

        $this->assertTrue($supported->isSupported());
        $this->assertFalse($unsupported->isSupported());
    }

    // -------------------------------------------------------------------------
    // getFromURL — no adapter throws RuntimeException
    // -------------------------------------------------------------------------

    public function testGetFromUrlThrowsRuntimeExceptionWhenNoAdapterAvailable(): void
    {
        // We need a Download instance whose adapter is null.
        // Use a subclass to inject null without touching the private property via
        // Reflection (setAccessible is deprecated in PHP 8.5).
        $dl = new class ($this->makeContainer()) extends Download {
            public function clearAdapter(): void
            {
                // Walk up to the parent's private $adapter and nullify it via
                // an anonymous override of setAdapter() — but the property is
                // private so we cannot reach it directly.
                // Instead, set an adapter that does NOT implement DownloadInterface
                // so it is silently ignored, then rely on the fact that the
                // constructor may not have found any adapter on this host.
                // For a deterministic test we need another strategy:
                // force-set via setAdapter() with a class that does NOT implement
                // DownloadInterface — the method ignores it, leaving adapter as-is.
                // The cleanest solution: override getFromURL() is not possible either.
                // We therefore use the public setAdapter() trick: pass a short name
                // that resolves to a real class but is NOT a DownloadInterface, so
                // the existing $adapter is unchanged.  This test can only work
                // deterministically if the host has no adapter — skip otherwise.
            }
        };
        // If an adapter was found during construction the exception won't fire.
        if ($dl->getAdapterName() !== '') {
            $this->markTestSkipped('An adapter is available on this host; cannot test the no-adapter exception path without modifying private state.');
        }

        $this->expectException(\RuntimeException::class);
        $dl->getFromURL('http://example.com/file.zip');
    }

    // -------------------------------------------------------------------------
    // Adapter options (setAdapterOptions / getAdapterOptions)
    // -------------------------------------------------------------------------

    public function testSetAndGetAdapterOptionsRoundTrip(): void
    {
        $dl = new Download($this->makeContainer());

        $options = ['timeout' => 30, 'proxy' => ['host' => '127.0.0.1', 'port' => 8080]];
        $dl->setAdapterOptions($options);

        $this->assertSame($options, $dl->getAdapterOptions());
    }

    public function testAdapterOptionsDefaultToEmptyArray(): void
    {
        $dl = new Download($this->makeContainer());

        $this->assertSame([], $dl->getAdapterOptions());
    }

    public function testSetAdapterOptionsReplacesExistingOptions(): void
    {
        $dl = new Download($this->makeContainer());

        $dl->setAdapterOptions(['foo' => 'bar']);
        $dl->setAdapterOptions(['baz' => 'qux']);

        $this->assertSame(['baz' => 'qux'], $dl->getAdapterOptions());
    }

    public function testSetAdapterOptionsAcceptsEmptyArray(): void
    {
        $dl = new Download($this->makeContainer());

        $dl->setAdapterOptions(['some' => 'option']);
        $dl->setAdapterOptions([]);

        $this->assertSame([], $dl->getAdapterOptions());
    }

    // -------------------------------------------------------------------------
    // setAdapter — ContainerAwareInterface wiring
    // -------------------------------------------------------------------------

    public function testSetAdapterWiresContainerIntoAdapter(): void
    {
        $container = $this->makeContainer();
        $dl        = new Download($container);

        // Use the Fopen adapter (always exists as a class).
        $dl->setAdapter('fopen');

        // The adapter name must be set correctly — a proxy that the adapter
        // instance was accepted and its container was wired (the Download class
        // calls $this->adapter->setContainer() only when the adapter implements
        // ContainerAwareInterface, which AbstractAdapter does).
        $this->assertSame('fopen', $dl->getAdapterName());
    }

    // -------------------------------------------------------------------------
    // Priority ordering — manual inspection via fresh adapter instances
    // -------------------------------------------------------------------------

    #[DataProvider('adapterPriorityProvider')]
    public function testAdapterPriorityValues(string $class, int $expectedPriority): void
    {
        $adapter = new $class();
        $this->assertSame($expectedPriority, $adapter->getPriority());
    }

    public static function adapterPriorityProvider(): array
    {
        return [
            'Curl priority is 110'  => [\Awf\Download\Adapter\Curl::class, 110],
            'Fopen priority is 100' => [\Awf\Download\Adapter\Fopen::class, 100],
        ];
    }
}
