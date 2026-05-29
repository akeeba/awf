<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Autoloader;

use Awf\Autoloader\Autoloader;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see Autoloader}.
 *
 * Each test creates a **fresh** Autoloader instance (using `new Autoloader()`)
 * and registers it with spl_autoload_register only when explicitly testing
 * loadClass/register behaviour.  The instance is always unregistered in
 * tearDown so the real autoloader stack is not polluted.
 *
 * Fixture structure (relative to Fixtures/):
 *   AcmeBase/Foo/Sample.php   – Acme\Foo\Sample  (prefix Acme\ → AcmeBase/)
 *   OtherBase/Bar/Widget.php  – Other\Bar\Widget (prefix Other\ → OtherBase/)
 *   FallbackNs/Thing.php      – FallbackNs\Thing (fallback root → Fixtures/)
 */
#[IgnoreDeprecations]
class AutoloaderTest extends TestCase
{
    private Autoloader $autoloader;

    /** Fixture base path – all dummy class files live under this directory. */
    private string $fixtureBase;

    protected function setUp(): void
    {
        $this->autoloader  = new Autoloader();
        $this->fixtureBase = __DIR__ . '/Fixtures';
    }

    protected function tearDown(): void
    {
        // Always unregister so we never leak into the global SPL stack.
        $this->autoloader->unregister();
    }

    // =========================================================================
    // getPrefixes / getFallbackDirs – initial state
    // =========================================================================

    public function testGetPrefixesInitiallyEmpty(): void
    {
        self::assertSame([], $this->autoloader->getPrefixes());
    }

    public function testGetFallbackDirsInitiallyEmpty(): void
    {
        self::assertSame([], $this->autoloader->getFallbackDirs());
    }

    // =========================================================================
    // addMap – root namespace (fallback dirs)
    // =========================================================================

    public function testAddMapRootNamespaceAppends(): void
    {
        $this->autoloader->addMap('', '/path/a');
        $this->autoloader->addMap('', '/path/b');

        self::assertSame(['/path/a', '/path/b'], $this->autoloader->getFallbackDirs());
    }

    public function testAddMapRootNamespacePrepends(): void
    {
        $this->autoloader->addMap('', '/path/a');
        $this->autoloader->addMap('', '/path/b', true);

        self::assertSame(['/path/b', '/path/a'], $this->autoloader->getFallbackDirs());
    }

    public function testAddMapRootNamespaceAcceptsArray(): void
    {
        $this->autoloader->addMap('', ['/path/a', '/path/b']);

        self::assertSame(['/path/a', '/path/b'], $this->autoloader->getFallbackDirs());
    }

    // =========================================================================
    // addMap – named prefix (new registration)
    // =========================================================================

    public function testAddMapRegistersNewPrefix(): void
    {
        $this->autoloader->addMap('Acme\\', '/path/acme');

        self::assertSame(['Acme\\' => ['/path/acme']], $this->autoloader->getPrefixes());
    }

    public function testAddMapRegistersNewPrefixWithArrayPaths(): void
    {
        $this->autoloader->addMap('Acme\\', ['/path/a', '/path/b']);

        self::assertSame(['Acme\\' => ['/path/a', '/path/b']], $this->autoloader->getPrefixes());
    }

    public function testAddMapAppendsToExistingPrefix(): void
    {
        $this->autoloader->addMap('Acme\\', '/path/a');
        $this->autoloader->addMap('Acme\\', '/path/b');

        self::assertSame(['Acme\\' => ['/path/a', '/path/b']], $this->autoloader->getPrefixes());
    }

    public function testAddMapPrependsToExistingPrefix(): void
    {
        $this->autoloader->addMap('Acme\\', '/path/a');
        $this->autoloader->addMap('Acme\\', '/path/b', true);

        self::assertSame(['Acme\\' => ['/path/b', '/path/a']], $this->autoloader->getPrefixes());
    }

    public function testAddMapReturnsThisForChaining(): void
    {
        $result = $this->autoloader->addMap('Acme\\', '/path/acme');

        self::assertSame($this->autoloader, $result);
    }

    public function testAddMapThrowsOnMissingTrailingSeparatorForNewPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->autoloader->addMap('Acme', '/path/acme');
    }

    // =========================================================================
    // setMap – replace
    // =========================================================================

    public function testSetMapReplacesRootFallback(): void
    {
        $this->autoloader->addMap('', '/path/old');
        $this->autoloader->setMap('', '/path/new');

        self::assertSame(['/path/new'], $this->autoloader->getFallbackDirs());
    }

    public function testSetMapReplacesExistingPrefix(): void
    {
        $this->autoloader->addMap('Acme\\', '/path/a');
        $this->autoloader->setMap('Acme\\', '/path/b');

        self::assertSame(['Acme\\' => ['/path/b']], $this->autoloader->getPrefixes());
    }

    public function testSetMapRegistersNewPrefix(): void
    {
        $this->autoloader->setMap('Acme\\', '/path/acme');

        self::assertSame(['Acme\\' => ['/path/acme']], $this->autoloader->getPrefixes());
    }

    public function testSetMapThrowsOnMissingTrailingSeparator(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->autoloader->setMap('Acme', '/path/acme');
    }

    // =========================================================================
    // findFile – PSR-4 prefix resolution
    //
    // Fixture: AcmeBase/Foo/Sample.php for class Acme\Foo\Sample
    //   → addMap('Acme\\', $fixtureBase . '/AcmeBase')
    //   → class Acme\Foo\Sample strips 'Acme\' (4 chars) → Foo/Sample.php
    //   → resolved path: $fixtureBase/AcmeBase/Foo/Sample.php ✓
    // =========================================================================

    public function testFindFileReturnsPathForRegisteredPrefix(): void
    {
        $this->autoloader->addMap('Acme\\', $this->fixtureBase . '/AcmeBase');

        $result = $this->autoloader->findFile('Acme\\Foo\\Sample');

        self::assertNotFalse($result, 'findFile should locate the fixture class file');
        self::assertStringEndsWith(
            'Foo' . DIRECTORY_SEPARATOR . 'Sample.php',
            $result
        );
    }

    public function testFindFileReturnsFalseForUnknownClass(): void
    {
        $this->autoloader->addMap('Acme\\', $this->fixtureBase . '/AcmeBase');

        $result = $this->autoloader->findFile('Acme\\NonExistent\\Class_');

        self::assertFalse($result);
    }

    public function testFindFileTriesAllDirsForPrefix(): void
    {
        // Register a non-existent dir first; the real one should still be found.
        $this->autoloader->addMap('Acme\\', ['/no/such/dir', $this->fixtureBase . '/AcmeBase']);

        $result = $this->autoloader->findFile('Acme\\Foo\\Sample');

        self::assertNotFalse($result);
    }

    public function testFindFileReturnsCorrectPathForDeepClass(): void
    {
        // Fixture: OtherBase/Bar/Widget.php for class Other\Bar\Widget
        $this->autoloader->addMap('Other\\', $this->fixtureBase . '/OtherBase');

        $result = $this->autoloader->findFile('Other\\Bar\\Widget');

        self::assertNotFalse($result, 'Deep class should be found');
        self::assertStringEndsWith(
            'Bar' . DIRECTORY_SEPARATOR . 'Widget.php',
            $result
        );
    }

    // =========================================================================
    // findFile – fallback dir resolution
    //
    // Fixture: FallbackNs/Thing.php for class FallbackNs\Thing
    //   → addMap('', $fixtureBase)  (fallback)
    //   → full logical path: FallbackNs/Thing.php appended to $fixtureBase ✓
    // =========================================================================

    public function testFindFileFallbackDirResolvesClassWithoutPrefix(): void
    {
        $this->autoloader->addMap('', $this->fixtureBase);

        $result = $this->autoloader->findFile('FallbackNs\\Thing');

        self::assertNotFalse($result);
        self::assertStringEndsWith(
            'FallbackNs' . DIRECTORY_SEPARATOR . 'Thing.php',
            $result
        );
    }

    public function testFindFileFallbackDirReturnsFalseWhenNotFound(): void
    {
        $this->autoloader->addMap('', $this->fixtureBase);

        $result = $this->autoloader->findFile('NoNs\\Missing');

        self::assertFalse($result);
    }

    // =========================================================================
    // findFile – leading backslash stripped (PHP 5.3 compat quirk)
    // =========================================================================

    public function testFindFileStripsLeadingBackslash(): void
    {
        $this->autoloader->addMap('Acme\\', $this->fixtureBase . '/AcmeBase');

        $result = $this->autoloader->findFile('\\Acme\\Foo\\Sample');

        self::assertNotFalse($result, 'Leading backslash should be stripped and class should be found');
    }

    // =========================================================================
    // findFile – no matching prefix and no fallback
    // =========================================================================

    public function testFindFileReturnsFalseWhenNoMappingsExist(): void
    {
        $result = $this->autoloader->findFile('Some\\Class');

        self::assertFalse($result);
    }

    // =========================================================================
    // loadClass
    // =========================================================================

    public function testLoadClassReturnsTrueAndIncludesFile(): void
    {
        $this->autoloader->addMap('Acme\\', $this->fixtureBase . '/AcmeBase');

        $result = $this->autoloader->loadClass('Acme\\Foo\\Sample');

        self::assertTrue($result);
        self::assertTrue(class_exists('Acme\\Foo\\Sample', false));
    }

    public function testLoadClassReturnsFalseForUnknownClass(): void
    {
        $this->autoloader->addMap('Acme\\', $this->fixtureBase . '/AcmeBase');

        $result = $this->autoloader->loadClass('Acme\\DoesNot\\Exist');

        self::assertFalse($result);
    }

    // =========================================================================
    // register / unregister
    // =========================================================================

    public function testRegisterAddsSplAutoloader(): void
    {
        $before = count(spl_autoload_functions() ?: []);
        $this->autoloader->register();
        $after = count(spl_autoload_functions() ?: []);

        self::assertGreaterThan($before, $after, 'Registering should add one entry to the SPL stack');
    }

    public function testUnregisterRemovesSplAutoloader(): void
    {
        $this->autoloader->register();
        $before = count(spl_autoload_functions() ?: []);

        $this->autoloader->unregister();
        $after = count(spl_autoload_functions() ?: []);

        self::assertLessThan($before, $after, 'Unregistering should remove the entry from the SPL stack');
    }

    public function testRegisterWithPrependPlacesAutoloaderFirst(): void
    {
        $this->autoloader->register(true);
        $functions = spl_autoload_functions();

        // The first callable on the stack should belong to our autoloader.
        $first = $functions[0];
        self::assertIsArray($first);
        self::assertSame($this->autoloader, $first[0]);
    }

    // =========================================================================
    // getInstance – deprecated singleton
    // =========================================================================

    public function testGetInstanceReturnsAutoloaderInstance(): void
    {
        $triggered = false;

        set_error_handler(
            static function (int $errno) use (&$triggered): bool {
                if ($errno === E_USER_DEPRECATED) {
                    $triggered = true;
                }

                return true; // suppress – do not escalate to exception
            },
            E_USER_DEPRECATED
        );

        try {
            $instance = Autoloader::getInstance();
        } finally {
            restore_error_handler();
        }

        self::assertInstanceOf(Autoloader::class, $instance);
        self::assertTrue($triggered, 'getInstance() must trigger E_USER_DEPRECATED');
    }

    public function testGetInstanceTriggersMeaningfulDeprecationMessage(): void
    {
        $message = '';

        set_error_handler(
            static function (int $errno, string $errstr) use (&$message): bool {
                if ($errno === E_USER_DEPRECATED) {
                    $message = $errstr;
                }

                return true;
            },
            E_USER_DEPRECATED
        );

        try {
            Autoloader::getInstance();
        } finally {
            restore_error_handler();
        }

        self::assertStringContainsStringIgnoringCase('deprecated', $message);
    }

    public function testGetInstanceReturnsSameInstanceTwice(): void
    {
        set_error_handler(static fn() => true, E_USER_DEPRECATED);

        try {
            $a = Autoloader::getInstance();
            $b = Autoloader::getInstance();
        } finally {
            restore_error_handler();
        }

        self::assertSame($a, $b);
    }
}
