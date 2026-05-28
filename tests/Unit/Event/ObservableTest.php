<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Event;

use Awf\Container\Container;
use Awf\Event\Dispatcher;
use Awf\Event\Observable;
use Awf\Event\Observer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Fake observer classes used by ObservableTest
// ---------------------------------------------------------------------------

/**
 * Observer with a single public method to handle one event.
 */
class ObservableTestObserverOne extends Observer
{
    public function onEventOne(string $value): string
    {
        return 'One:' . $value;
    }
}

/**
 * Observer with two public methods; also provides access to the subject reference.
 */
class ObservableTestObserverTwo extends Observer
{
    public function onEventOne(string $value): string
    {
        return 'Two:' . $value;
    }

    public function onEventTwo(int $n): int
    {
        return $n * 2;
    }

    public function getSubject(): Observable
    {
        return $this->subject;
    }
}

/**
 * Observer that pre-sets its $events list to avoid reflection.
 */
class ObservableTestPresetObserver extends Observer
{
    public function __construct(Observable &$subject)
    {
        $this->events = ['onPresetEvent'];
        parent::__construct($subject);
    }

    public function onPresetEvent(string $value): string
    {
        return 'Preset:' . $value;
    }

    /**
     * This public method is NOT in the preset list, so it must not be discovered.
     */
    public function shouldBeIgnored(): string
    {
        return 'ignored';
    }
}

/**
 * Observer with no public event methods.
 */
class ObservableTestEmptyObserver extends Observer {}

/**
 * Observer whose getObservableEvents returns a custom list (pre-set $events).
 */
class ObservableTestCustomEventsObserver extends Observer
{
    /** @var array<string> */
    protected $events = ['onCustom'];

    public function onCustom(string $v): string
    {
        return 'Custom:' . $v;
    }

    public function notAnEvent(): string
    {
        return 'not-an-event';
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

class ObservableTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function makeContainer(string $appName = 'test_observable'): Container
    {
        return new Container([
            'application_name'     => $appName,
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

    private function makeDispatcher(string $appName = 'test_observable'): Dispatcher
    {
        return new Dispatcher(self::makeContainer($appName));
    }

    // -------------------------------------------------------------------------
    // Observable interface contract: attach
    // -------------------------------------------------------------------------

    public function testObservableImplementsInterface(): void
    {
        $dispatcher = $this->makeDispatcher();

        self::assertInstanceOf(Observable::class, $dispatcher);
    }

    public function testAttachReturnsObservable(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        // Observer constructor already called attach; calling again returns self.
        $returned = $dispatcher->attach($observer);

        self::assertInstanceOf(Observable::class, $returned);
        self::assertSame($dispatcher, $returned);
    }

    public function testAttachRegistersObserverEvents(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestObserverOne($dispatcher);

        $results = $dispatcher->trigger('onEventOne', ['hello']);

        self::assertSame(['One:hello'], $results);
    }

    public function testAttachingMultipleObserversForSameEvent(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestObserverOne($dispatcher);
        new ObservableTestObserverTwo($dispatcher);

        $results = $dispatcher->trigger('onEventOne', ['world']);

        self::assertCount(2, $results);
        self::assertContains('One:world', $results);
        self::assertContains('Two:world', $results);
    }

    public function testAttachIsIdempotentForSameObserverInstance(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        // Explicitly attach the same instance again.
        $dispatcher->attach($observer);
        $dispatcher->attach($observer);

        $results = $dispatcher->trigger('onEventOne', ['x']);

        self::assertCount(1, $results, 'Duplicate attach must not result in multiple calls');
    }

    // -------------------------------------------------------------------------
    // Observable interface contract: detach
    // -------------------------------------------------------------------------

    public function testDetachReturnsObservable(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        $returned = $dispatcher->detach($observer);

        self::assertInstanceOf(Observable::class, $returned);
        self::assertSame($dispatcher, $returned);
    }

    public function testDetachRemovesObserverFromEventDispatch(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        $dispatcher->detach($observer);

        $results = $dispatcher->trigger('onEventOne', ['x']);

        self::assertSame([], $results);
    }

    public function testDetachOnlyRemovesTheDetachedObserver(): void
    {
        $dispatcher = $this->makeDispatcher();
        $obs1       = new ObservableTestObserverOne($dispatcher);
        $obs2       = new ObservableTestObserverTwo($dispatcher);

        $dispatcher->detach($obs1);

        $results = $dispatcher->trigger('onEventOne', ['check']);

        self::assertCount(1, $results);
        self::assertSame('Two:check', $results[0]);
    }

    public function testDetachUnregisteredObserverIsNoop(): void
    {
        $dispatcherA = $this->makeDispatcher('noop_obs_a');
        $dispatcherB = $this->makeDispatcher('noop_obs_b');
        $observer    = new ObservableTestObserverOne($dispatcherA);

        // Detach from a dispatcher it was never attached to — must not throw.
        $returned = $dispatcherB->detach($observer);

        self::assertSame($dispatcherB, $returned);
    }

    public function testDetachAndReattach(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        $dispatcher->detach($observer);
        $dispatcher->attach($observer);

        $results = $dispatcher->trigger('onEventOne', ['reattach']);

        self::assertSame(['One:reattach'], $results);
    }

    // -------------------------------------------------------------------------
    // Observable interface contract: trigger
    // -------------------------------------------------------------------------

    public function testTriggerReturnsArrayOfResults(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestObserverTwo($dispatcher);

        $results = $dispatcher->trigger('onEventTwo', [5]);

        self::assertIsArray($results);
        self::assertSame([10], $results);
    }

    public function testTriggerWithNoObserversReturnsEmptyArray(): void
    {
        $dispatcher = $this->makeDispatcher();

        $results = $dispatcher->trigger('onEventOne', ['x']);

        self::assertSame([], $results);
    }

    public function testTriggerWithUnknownEventReturnsEmptyArray(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestObserverOne($dispatcher);

        $results = $dispatcher->trigger('onNonExistent', ['x']);

        self::assertSame([], $results);
    }

    public function testTriggerIsCaseInsensitive(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestObserverOne($dispatcher);

        $upperResults = $dispatcher->trigger('ONEVENTONE', ['case']);
        $mixedResults = $dispatcher->trigger('OnEventOne', ['case']);

        self::assertSame(['One:case'], $upperResults);
        self::assertSame(['One:case'], $mixedResults);
    }

    public function testTriggerWithZeroArguments(): void
    {
        $dispatcher = $this->makeDispatcher();

        $observer = new class($dispatcher) extends Observer {
            public function onNoParam(): string
            {
                return 'noparam';
            }
        };

        $results = $dispatcher->trigger('onNoParam');

        self::assertSame(['noparam'], $results);
    }

    public static function argumentCountProvider(): array
    {
        return [
            '1 arg'  => [['a'], 'a'],
            '2 args' => [['a', 'b'], 'ab'],
            '3 args' => [['a', 'b', 'c'], 'abc'],
            '4 args' => [['a', 'b', 'c', 'd'], 'abcd'],
            '5 args' => [['a', 'b', 'c', 'd', 'e'], 'abcde'],
            '6 args (call_user_func_array branch)' => [['a', 'b', 'c', 'd', 'e', 'f'], 'abcdef'],
        ];
    }

    #[DataProvider('argumentCountProvider')]
    public function testTriggerPassesCorrectNumberOfArguments(array $args, string $expected): void
    {
        $dispatcher = $this->makeDispatcher();

        $observer = new class($dispatcher) extends Observer {
            public function onconcatargs(string ...$parts): string
            {
                return implode('', $parts);
            }
        };

        $results = $dispatcher->trigger('onconcatargs', $args);

        self::assertSame([$expected], $results);
    }

    // -------------------------------------------------------------------------
    // Observer: constructor attaches to subject
    // -------------------------------------------------------------------------

    public function testObserverConstructorAttachesToSubject(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverTwo($dispatcher);

        // The subject stored inside the Observer must be the same dispatcher.
        self::assertSame($dispatcher, $observer->getSubject());
    }

    public function testObserverConstructorMakesDispatcherRecogniseIt(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        self::assertTrue($dispatcher->hasObserver($observer));
        self::assertTrue($dispatcher->hasObserverClass(ObservableTestObserverOne::class));
    }

    // -------------------------------------------------------------------------
    // Observer: getObservableEvents — reflection-based discovery
    // -------------------------------------------------------------------------

    public function testGetObservableEventsReturnsPublicMethods(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverTwo($dispatcher);

        $events = $observer->getObservableEvents();

        // onEventOne, onEventTwo, and getSubject are all public methods.
        self::assertContains('onEventOne', $events);
        self::assertContains('onEventTwo', $events);
    }

    public function testGetObservableEventsExcludesGetObservableEventsItself(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        $events = $observer->getObservableEvents();

        self::assertNotContains('getObservableEvents', $events);
    }

    public function testGetObservableEventsExcludesConstructor(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        $events = $observer->getObservableEvents();

        self::assertNotContains('__construct', $events);
    }

    public function testGetObservableEventsReturnsEmptyArrayForObserverWithNoMethods(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestEmptyObserver($dispatcher);

        $events = $observer->getObservableEvents();

        self::assertIsArray($events);
        self::assertEmpty($events);
    }

    public function testGetObservableEventsCachedOnSecondCall(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestObserverOne($dispatcher);

        $first  = $observer->getObservableEvents();
        $second = $observer->getObservableEvents();

        // Identical result; same array reference proves caching (no re-reflection).
        self::assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // Observer: getObservableEvents — pre-set $events list
    // -------------------------------------------------------------------------

    public function testPresetEventsSkipsReflection(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestPresetObserver($dispatcher);

        $events = $observer->getObservableEvents();

        self::assertSame(['onPresetEvent'], $events);
        self::assertNotContains('shouldBeIgnored', $events);
    }

    public function testPresetEventsAreDispatchedCorrectly(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestPresetObserver($dispatcher);

        $results = $dispatcher->trigger('onPresetEvent', ['val']);

        self::assertSame(['Preset:val'], $results);
    }

    public function testCustomEventsPropertySkipsReflection(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestCustomEventsObserver($dispatcher);

        $events = $observer->getObservableEvents();

        self::assertSame(['onCustom'], $events);
        self::assertNotContains('notAnEvent', $events);
    }

    public function testCustomEventsObserverOnlyDispatchesListedEvents(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestCustomEventsObserver($dispatcher);

        $customResult    = $dispatcher->trigger('onCustom', ['test']);
        $notEventResult  = $dispatcher->trigger('notAnEvent', []);

        self::assertSame(['Custom:test'], $customResult);
        self::assertSame([], $notEventResult);
    }

    // -------------------------------------------------------------------------
    // Observer: empty observer still attaches but fires no events
    // -------------------------------------------------------------------------

    public function testEmptyObserverAttachesWithoutError(): void
    {
        $dispatcher = $this->makeDispatcher();
        $observer   = new ObservableTestEmptyObserver($dispatcher);

        self::assertTrue($dispatcher->hasObserver($observer));
    }

    public function testEmptyObserverDoesNotReceiveAnyEvent(): void
    {
        $dispatcher = $this->makeDispatcher();
        new ObservableTestEmptyObserver($dispatcher);

        $results = $dispatcher->trigger('onFoo', ['x']);

        self::assertSame([], $results);
    }
}
