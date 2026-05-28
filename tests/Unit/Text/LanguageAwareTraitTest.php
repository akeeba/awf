<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Text;

use Awf\Container\Container;
use Awf\Text\Language;
use Awf\Text\LanguageAwareTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Awf\Text\LanguageAwareTrait.
 *
 * A throwaway anonymous class that uses the trait is instantiated for each
 * test so every test begins with a clean, uninitialized state.
 */
class LanguageAwareTraitTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /** Returns a fresh object that uses LanguageAwareTrait. */
    private function makeConsumer(): object
    {
        return new class {
            use LanguageAwareTrait;
        };
    }

    /** Returns a minimal Language stub. */
    private function makeLanguage(): Language
    {
        $container = $this->createMock(Container::class);

        return $this->getMockBuilder(Language::class)
            ->setConstructorArgs([$container])
            ->onlyMethods([])
            ->getMock();
    }

    // -------------------------------------------------------------------------
    // getLanguage — before any setLanguage call
    // -------------------------------------------------------------------------

    /**
     * getLanguage() returns null when no Language has been injected yet,
     * because the private property is never initialised by the trait.
     */
    public function testGetLanguageReturnsNullBeforeAnySet(): void
    {
        $consumer = $this->makeConsumer();

        // The return type hint says Language, but the property starts as null.
        self::assertNull($consumer->getLanguage());
    }

    // -------------------------------------------------------------------------
    // setLanguage / getLanguage round-trip
    // -------------------------------------------------------------------------

    public function testSetAndGetLanguageRoundTrip(): void
    {
        $consumer = $this->makeConsumer();
        $language = $this->makeLanguage();

        $consumer->setLanguage($language);

        self::assertSame($language, $consumer->getLanguage());
    }

    public function testSetLanguageOverwritesPreviousValue(): void
    {
        $consumer   = $this->makeConsumer();
        $language1  = $this->makeLanguage();
        $language2  = $this->makeLanguage();

        $consumer->setLanguage($language1);
        $consumer->setLanguage($language2);

        self::assertSame($language2, $consumer->getLanguage());
        self::assertNotSame($language1, $consumer->getLanguage());
    }

    // -------------------------------------------------------------------------
    // Independence of separate consumer instances
    // -------------------------------------------------------------------------

    public function testEachConsumerInstanceHasItsOwnLanguage(): void
    {
        $consumer1 = $this->makeConsumer();
        $consumer2 = $this->makeConsumer();

        $language = $this->makeLanguage();
        $consumer1->setLanguage($language);

        // consumer2 was never given a Language, so it must still be null.
        self::assertNull($consumer2->getLanguage());
        // consumer1 must still hold its own reference.
        self::assertSame($language, $consumer1->getLanguage());
    }

    // -------------------------------------------------------------------------
    // setLanguage returns void (no fluent interface)
    // -------------------------------------------------------------------------

    public function testSetLanguageReturnsNull(): void
    {
        $consumer = $this->makeConsumer();
        $language = $this->makeLanguage();

        $result = $consumer->setLanguage($language);

        self::assertNull($result);
    }
}
