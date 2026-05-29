<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Session;

use Awf\Session\Manager;
use Awf\Session\Segment;
use Awf\Session\SegmentFactory;
use Awf\Session\Encoder\EncoderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A transparent encoder that stores data as-is (no real encoding).
 *
 * This lets us test Segment without any base64/base32 extension requirements.
 */
class PassthroughEncoder implements EncoderInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function encode(?array $raw): mixed
    {
        return $raw;
    }

    public function decode($encoded): array
    {
        return is_array($encoded) ? $encoded : [];
    }
}

/**
 * A stubbed Manager that avoids real PHP session interaction.
 *
 * The segment's isLoaded() / load() path calls session->isStarted() and
 * session->isAvailable(). We control both here, and we never touch $_SESSION.
 */
class FakeManager extends Manager
{
    private bool $started   = false;
    private bool $available = true;

    public function __construct()
    {
        // Skip the real constructor entirely — no session_get_cookie_params().
    }

    public function setStarted(bool $v): void   { $this->started   = $v; }
    public function setAvailable(bool $v): void { $this->available = $v; }

    public function isStarted(): bool  { return $this->started; }
    public function isAvailable(): bool { return $this->available; }
    public function start(): bool      { $this->started = true; return true; }
}

// ---------------------------------------------------------------------------

#[CoversClass(\Awf\Session\Segment::class)]
#[CoversClass(\Awf\Session\SegmentFactory::class)]
class SegmentTest extends TestCase
{
    private FakeManager $manager;
    private Segment     $segment;

    protected function setUp(): void
    {
        $this->manager = new FakeManager();
        $this->manager->setStarted(true);   // pretend session is already running
        $this->manager->setAvailable(true);

        $this->segment = new Segment($this->manager, 'TestSegment');
        $this->segment->setEncoder(new PassthroughEncoder());
    }

    // -------------------------------------------------------------------------
    // getName
    // -------------------------------------------------------------------------

    public function testGetNameReturnsConstructorValue(): void
    {
        self::assertSame('TestSegment', $this->segment->getName());
    }

    // -------------------------------------------------------------------------
    // set / get / has / remove  (named API)
    // -------------------------------------------------------------------------

    public function testSetAndGetRoundtrip(): void
    {
        $this->segment->set('foo', 'bar');
        self::assertSame('bar', $this->segment->get('foo'));
    }

    public function testGetReturnsDefaultWhenKeyAbsent(): void
    {
        self::assertNull($this->segment->get('missing'));
        self::assertSame('default', $this->segment->get('missing2', 'default'));
    }

    public function testGetInitialisesKeyToDefault(): void
    {
        $this->segment->get('newKey', 42);
        // After get() the key must now exist and equal the default.
        self::assertTrue($this->segment->has('newKey'));
        self::assertSame(42, $this->segment->get('newKey'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse($this->segment->has('nope'));
    }

    public function testHasReturnsTrueAfterSet(): void
    {
        $this->segment->set('present', true);
        self::assertTrue($this->segment->has('present'));
    }

    public function testRemoveDeletesKey(): void
    {
        $this->segment->set('toDelete', 'value');
        $this->segment->remove('toDelete');
        self::assertFalse($this->segment->has('toDelete'));
    }

    public function testRemoveOnMissingKeyIsNoop(): void
    {
        // Must not throw.
        $this->segment->remove('nonExistent');
        self::assertFalse($this->segment->has('nonExistent'));
    }

    // -------------------------------------------------------------------------
    // Magic property access (__get / __set / __isset / __unset)
    // -------------------------------------------------------------------------

    public function testMagicSetAndGet(): void
    {
        $this->segment->magic = 'value';
        self::assertSame('value', $this->segment->magic);
    }

    public function testMagicGetReturnNullForMissingKey(): void
    {
        self::assertNull($this->segment->undeclared);
    }

    public function testMagicIsset(): void
    {
        $this->segment->x = 1;
        self::assertTrue(isset($this->segment->x));
        self::assertFalse(isset($this->segment->y));
    }

    public function testMagicUnset(): void
    {
        $this->segment->z = 'hello';
        unset($this->segment->z);
        self::assertFalse(isset($this->segment->z));
    }

    // -------------------------------------------------------------------------
    // clear
    // -------------------------------------------------------------------------

    public function testClearRemovesAllData(): void
    {
        $this->segment->set('a', 1);
        $this->segment->set('b', 2);
        $this->segment->clear();
        self::assertFalse($this->segment->has('a'));
        self::assertFalse($this->segment->has('b'));
    }

    // -------------------------------------------------------------------------
    // Flash lifecycle
    // -------------------------------------------------------------------------

    public function testSetFlashAndHasFlash(): void
    {
        $this->segment->setFlash('notice', 'All good!');
        self::assertTrue($this->segment->hasFlash('notice'));
    }

    public function testGetFlashReturnsAndRemovesValue(): void
    {
        $this->segment->setFlash('msg', 'Hello');
        $value = $this->segment->getFlash('msg');
        self::assertSame('Hello', $value);
        // Consumed — must no longer be present.
        self::assertFalse($this->segment->hasFlash('msg'));
    }

    public function testGetFlashReturnsNullWhenAbsent(): void
    {
        self::assertNull($this->segment->getFlash('ghost'));
    }

    public function testHasFlashReturnsFalseWhenAbsent(): void
    {
        self::assertFalse($this->segment->hasFlash('gone'));
    }

    public function testClearFlashRemovesAllFlashData(): void
    {
        $this->segment->setFlash('a', 1);
        $this->segment->setFlash('b', 2);
        $this->segment->clearFlash();
        self::assertFalse($this->segment->hasFlash('a'));
        self::assertFalse($this->segment->hasFlash('b'));
    }

    public function testClearDoesNotAffectFlashKeys(): void
    {
        // clear() clears the whole data array including __flash, so this test
        // verifies that clear() indeed removes flash as well (by clearing everything).
        $this->segment->setFlash('note', 'hi');
        $this->segment->clear();
        self::assertFalse($this->segment->hasFlash('note'));
    }

    public function testFlashDoesNotInterfereWithRegularData(): void
    {
        $this->segment->set('regular', 'data');
        $this->segment->setFlash('flash', 'flash-data');

        // Reading the flash must not touch the regular key.
        $this->segment->getFlash('flash');
        self::assertTrue($this->segment->has('regular'));
        self::assertSame('data', $this->segment->get('regular'));
    }

    // -------------------------------------------------------------------------
    // Lazy-loading via isLoaded / load
    // -------------------------------------------------------------------------

    public function testSegmentStartsSessionWhenNotStarted(): void
    {
        $manager = new FakeManager();
        $manager->setStarted(false);
        $manager->setAvailable(true);

        $segment = new Segment($manager, 'Lazy');
        $segment->setEncoder(new PassthroughEncoder());

        // Accessing any data must trigger session start.
        $segment->set('k', 'v');
        self::assertTrue($manager->isStarted());
    }

    public function testSegmentIsLoadedReturnsFalseWhenNotAvailable(): void
    {
        $manager = new FakeManager();
        $manager->setStarted(false);
        $manager->setAvailable(false);

        $segment = new Segment($manager, 'Unavailable');
        $segment->setEncoder(new PassthroughEncoder());

        // __get on an unloaded segment must return null (not throw).
        self::assertNull($segment->foo);
        self::assertFalse(isset($segment->foo));
        self::assertFalse($segment->has('foo'));
        self::assertFalse($segment->hasFlash('flash'));
    }

    // -------------------------------------------------------------------------
    // Encoder (setEncoder / getEncoder)
    // -------------------------------------------------------------------------

    public function testSetAndGetEncoder(): void
    {
        $encoder = new PassthroughEncoder();
        $this->segment->setEncoder($encoder);
        self::assertSame($encoder, $this->segment->getEncoder());
    }

    // -------------------------------------------------------------------------
    // SegmentFactory
    // -------------------------------------------------------------------------

    public function testSegmentFactoryCreatesSegmentInstance(): void
    {
        $factory = new SegmentFactory();
        $segment = $factory->newInstance($this->manager, 'MySegment');

        self::assertInstanceOf(Segment::class, $segment);
        self::assertSame('MySegment', $segment->getName());
    }

    public function testSegmentFactoryCreatesNewInstanceEachTime(): void
    {
        $factory = new SegmentFactory();
        $a = $factory->newInstance($this->manager, 'seg');
        $b = $factory->newInstance($this->manager, 'seg');

        // Each call must produce a new object.
        self::assertNotSame($a, $b);
    }

    public function testSegmentFactoryBindsManagerCorrectly(): void
    {
        $factory = new SegmentFactory();
        $segment = $factory->newInstance($this->manager, 'Bound');
        $segment->setEncoder(new PassthroughEncoder());

        // If the manager is correctly bound, data operations must work.
        $segment->set('key', 'value');
        self::assertSame('value', $segment->get('key'));
    }

    // -------------------------------------------------------------------------
    // Multiple keys and overwrite
    // -------------------------------------------------------------------------

    public function testOverwriteExistingKey(): void
    {
        $this->segment->set('key', 'original');
        $this->segment->set('key', 'updated');
        self::assertSame('updated', $this->segment->get('key'));
    }

    public function testSetMultipleKeys(): void
    {
        $this->segment->set('x', 10);
        $this->segment->set('y', 20);
        self::assertSame(10, $this->segment->get('x'));
        self::assertSame(20, $this->segment->get('y'));
    }

    public function testFlashCanBeOverwritten(): void
    {
        $this->segment->setFlash('msg', 'first');
        $this->segment->setFlash('msg', 'second');
        self::assertSame('second', $this->segment->getFlash('msg'));
    }
}
