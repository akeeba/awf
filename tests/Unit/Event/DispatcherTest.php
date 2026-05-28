<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Event;

use Awf\Container\Container;
use Awf\Event\Dispatcher;
use Awf\Event\Observer;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Fake observer classes used as test doubles
// ---------------------------------------------------------------------------

/**
 * Observer that handles a single event "onFoo" and records all calls.
 */
class FakeObserverA extends Observer
{
    /** @var array<int, array<mixed>> */
    public array $fooCalls = [];

    public function onFoo(mixed ...$args): string
    {
        $this->fooCalls[] = $args;

        return 'A:' . implode(',', $args);
    }
}

/**
 * Second observer that also handles "onFoo" and additionally "onBar".
 */
class FakeObserverB extends Observer
{
    /** @var array<int, array<mixed>> */
    public array $fooCalls = [];

    /** @var array<int, array<mixed>> */
    public array $barCalls = [];

    public function onFoo(mixed ...$args): string
    {
        $this->fooCalls[] = $args;

        return 'B:' . implode(',', $args);
    }

    public function onBar(string $x): string
    {
        $this->barCalls[] = [$x];

        return 'Bar:' . $x;
    }
}

/**
 * Observer that returns null from its event handler (used for chainHandle tests).
 */
class FakeObserverNull extends Observer
{
    public function onChain(): ?string
    {
        return null;
    }
}

/**
 * Observer that returns a non-null value from its event handler.
 */
class FakeObserverNonNull extends Observer
{
    public function onChain(): string
    {
        return 'handled';
    }
}

/**
 * Observer that has no public event methods beyond the inherited ones.
 */
class FakeObserverNoEvents extends Observer {}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

class DispatcherTest extends TestCase
{
    /** Minimal Container configuration shared by all tests. */
    private static function makeContainer(): Container
    {
        return new Container([
            'application_name'     => 'test_dispatcher',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'test_segment',
            'filesystemBase'       => '/tmp',
            'basePath'             => '/tmp/test',
            'templatePath'         => '/tmp/test/templates',
            'languagePath'         => '/tmp/test/languages',
            'temporaryPath'        => '/tmp/test/tmp',
            'sqlPath'              => '/tmp/test/sql',
        ]);
    }

    private function makeDispatcher(): Dispatcher
    {
        return new Dispatcher(self::makeContainer());
    }

    // -------------------------------------------------------------------------
    // Constructor / Container
    // -------------------------------------------------------------------------

    public function testConstructorStoresContainer(): void
    {
        $container  = self::makeContainer();
        $dispatcher = new Dispatcher($container);

        self::assertSame($container, $dispatcher->getContainer());
    }

    // -------------------------------------------------------------------------
    // attach / hasObserver / hasObserverClass
    // -------------------------------------------------------------------------

    public function testAttachRegistersObserver(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        self::assertTrue($dispatcher->hasObserver($observer));
        self::assertTrue($dispatcher->hasObserverClass(FakeObserverA::class));
    }

    public function testAttachReturnsSelf(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        // attach() is called by the Observer constructor; calling it again
        // should still return the same dispatcher instance (self)
        $result = $dispatcher->attach($observer);

        self::assertSame($dispatcher, $result);
    }

    public function testAttachIdempotent(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        // Second explicit attach of the same instance must not duplicate registrations.
        $dispatcher->attach($observer);

        $results = $dispatcher->trigger('onFoo', ['x']);

        self::assertCount(1, $results, 'Observer must not be called twice after idempotent attach');
    }

    public function testHasObserverReturnsFalseWhenNotAttached(): void
    {
        $dispatcher = $this->makeDispatcher();
        $other      = $this->makeDispatcher();
        $observer   = new FakeObserverA($other);   // attached to a different dispatcher

        self::assertFalse($dispatcher->hasObserver($observer));
    }

    public function testHasObserverClassReturnsFalseForUnregisteredClass(): void
    {
        $dispatcher = $this->makeDispatcher();

        self::assertFalse($dispatcher->hasObserverClass(FakeObserverA::class));
    }

    // -------------------------------------------------------------------------
    // detach
    // -------------------------------------------------------------------------

    public function testDetachRemovesObserver(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        $dispatcher->detach($observer);

        self::assertFalse($dispatcher->hasObserver($observer));
    }

    public function testDetachReturnsSelf(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        $result = $dispatcher->detach($observer);

        self::assertSame($dispatcher, $result);
    }

    public function testDetachUnregisteredObserverIsNoop(): void
    {
        $dispatcher = $this->makeDispatcher();
        $other      = $this->makeDispatcher();
        $observer   = new FakeObserverA($other);

        // Must not throw; must return self
        $result = $dispatcher->detach($observer);

        self::assertSame($dispatcher, $result);
    }

    public function testDetachedObserverNoLongerReceivesEvents(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        $dispatcher->detach($observer);

        $results = $dispatcher->trigger('onFoo', ['hello']);

        self::assertSame([], $results);
        self::assertCount(0, $observer->fooCalls);
    }

    // -------------------------------------------------------------------------
    // trigger — basic fan-out
    // -------------------------------------------------------------------------

    public function testTriggerCallsAllObserversForEvent(): void
    {
        $dispatcher = $this->makeDispatcher();
        $a          = new FakeObserverA($dispatcher);
        $b          = new FakeObserverB($dispatcher);

        $results = $dispatcher->trigger('onFoo', ['hello']);

        self::assertCount(2, $results);
        self::assertContains('A:hello', $results);
        self::assertContains('B:hello', $results);
    }

    public function testTriggerReturnEmptyArrayForUnknownEvent(): void
    {
        $dispatcher = $this->makeDispatcher();

        $results = $dispatcher->trigger('onNonExistent');

        self::assertSame([], $results);
    }

    public function testTriggerIsCaseInsensitive(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        // Trigger with uppercase; the observer method is named "onFoo"
        $results = $dispatcher->trigger('ONFOO', ['x']);

        self::assertCount(1, $results);
        self::assertSame('A:x', $results[0]);
    }

    public function testTriggerWithNoArguments(): void
    {
        $dispatcher = $this->makeDispatcher();

        $called = false;

        // Use an anonymous inline observer by subclassing Observer here
        $observer = new class($dispatcher) extends Observer {
            public bool $called = false;

            public function onNoArgs(): string
            {
                $this->called = true;

                return 'noargs';
            }
        };

        $results = $dispatcher->trigger('onNoArgs');

        self::assertTrue($observer->called);
        self::assertSame(['noargs'], $results);
    }

    public function testTriggerPassesMultipleArguments(): void
    {
        $dispatcher = $this->makeDispatcher();

        $observer = new class($dispatcher) extends Observer {
            public array $received = [];

            public function onMulti(string $a, string $b, string $c): string
            {
                $this->received = [$a, $b, $c];

                return $a . $b . $c;
            }
        };

        $results = $dispatcher->trigger('onMulti', ['x', 'y', 'z']);

        self::assertSame(['x', 'y', 'z'], $observer->received);
        self::assertSame(['xyz'], $results);
    }

    public function testTriggerWithSixPlusArguments(): void
    {
        // Six arguments exercises the default/call_user_func_array branch.
        $dispatcher = $this->makeDispatcher();

        $observer = new class($dispatcher) extends Observer {
            public array $received = [];

            public function onSixArgs(
                string $a,
                string $b,
                string $c,
                string $d,
                string $e,
                string $f
            ): string {
                $this->received = [$a, $b, $c, $d, $e, $f];

                return $a . $b . $c . $d . $e . $f;
            }
        };

        $results = $dispatcher->trigger('onSixArgs', ['a', 'b', 'c', 'd', 'e', 'f']);

        self::assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $observer->received);
        self::assertSame(['abcdef'], $results);
    }

    public function testTriggerOnlyDispatchesEventObservers(): void
    {
        $dispatcher = $this->makeDispatcher();
        $b          = new FakeObserverB($dispatcher);

        // Trigger onBar; FakeObserverB handles it, but it should not trigger onFoo
        $results = $dispatcher->trigger('onBar', ['test']);

        self::assertSame(['Bar:test'], $results);
        self::assertCount(0, $b->fooCalls);
    }

    // -------------------------------------------------------------------------
    // Observer auto-detection via reflection (getObservableEvents)
    // -------------------------------------------------------------------------

    public function testObserverWithNoEventsRegistersNoneAndTriggersNothing(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverNoEvents($dispatcher);

        // Should still be attached as an observer object
        self::assertTrue($dispatcher->hasObserver($observer));

        // But no events should be dispatched to it
        $results = $dispatcher->trigger('onFoo', ['x']);

        self::assertSame([], $results);
    }

    public function testGetObservableEventsExcludesConstructorAndGetObservableEvents(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        $events = $observer->getObservableEvents();

        self::assertNotContains('__construct', $events);
        self::assertNotContains('getObservableEvents', $events);
    }

    public function testGetObservableEventsCachedAfterFirstCall(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new FakeObserverA($dispatcher);

        $first  = $observer->getObservableEvents();
        $second = $observer->getObservableEvents();

        self::assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // chainHandle
    // -------------------------------------------------------------------------

    public function testChainHandleReturnsFirstNonNullResult(): void
    {
        $dispatcher = $this->makeDispatcher();
        $nullObs    = new FakeObserverNull($dispatcher);
        $nonNull    = new FakeObserverNonNull($dispatcher);

        $result = $dispatcher->chainHandle('onChain');

        self::assertSame('handled', $result);
    }

    public function testChainHandleReturnsNullWhenAllObserversReturnNull(): void
    {
        $dispatcher = $this->makeDispatcher();
        $nullObs    = new FakeObserverNull($dispatcher);

        $result = $dispatcher->chainHandle('onChain');

        self::assertNull($result);
    }

    public function testChainHandleReturnsNullForUnknownEvent(): void
    {
        $dispatcher = $this->makeDispatcher();

        $result = $dispatcher->chainHandle('onNonExistent');

        self::assertNull($result);
    }

    public function testChainHandlePassesArguments(): void
    {
        $dispatcher = $this->makeDispatcher();

        $observer = new class($dispatcher) extends Observer {
            public function onChainArgs(string $a, string $b): string
            {
                return $a . '+' . $b;
            }
        };

        $result = $dispatcher->chainHandle('onChainArgs', ['foo', 'bar']);

        self::assertSame('foo+bar', $result);
    }

    // -------------------------------------------------------------------------
    // getInstance (static)
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsSameInstanceForSameAppName(): void
    {
        $container = self::makeContainer();

        $d1 = Dispatcher::getInstance($container);
        $d2 = Dispatcher::getInstance($container);

        self::assertSame($d1, $d2);
    }

    public function testGetInstanceReturnsDifferentInstanceForDifferentAppName(): void
    {
        $c1 = new Container(array_merge($this->minimalConfig(), ['application_name' => 'app_one']));
        $c2 = new Container(array_merge($this->minimalConfig(), ['application_name' => 'app_two']));

        $d1 = Dispatcher::getInstance($c1);
        $d2 = Dispatcher::getInstance($c2);

        self::assertNotSame($d1, $d2);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function minimalConfig(): array
    {
        return [
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'test_segment',
            'filesystemBase'       => '/tmp',
            'basePath'             => '/tmp/test',
            'templatePath'         => '/tmp/test/templates',
            'languagePath'         => '/tmp/test/languages',
            'temporaryPath'        => '/tmp/test/tmp',
            'sqlPath'              => '/tmp/test/sql',
        ];
    }
}
