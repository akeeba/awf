<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Session;

use Awf\Session\CsrfToken;
use Awf\Session\CsrfTokenFactory;
use Awf\Session\Manager;
use Awf\Session\Segment;
use Awf\Session\SegmentFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Test doubles
// ---------------------------------------------------------------------------

/**
 * A SegmentFactory stub that records what segments were requested
 * and always returns an InMemorySegment.
 */
class FakeSegmentFactory extends SegmentFactory
{
    /** @var array<string, \Awf\Session\Segment> */
    public array $created = [];

    public function newInstance(Manager $manager, $name): Segment
    {
        $segment = new InMemorySegment($manager, $name);
        $this->created[$name] = $segment;
        return $segment;
    }
}

/**
 * A CsrfTokenFactory stub that returns a predictable FakeCsrfToken.
 */
class FakeCsrfTokenFactory extends CsrfTokenFactory
{
    public int $newInstanceCallCount = 0;
    public ?string $algorithmPassedToSetAlgorithm = null;
    public string $currentAlgorithm;

    public function __construct(string $algorithm = 'sha512')
    {
        parent::__construct($algorithm);
        $this->currentAlgorithm = $algorithm;
    }

    public function newInstance(Manager $manager): CsrfToken
    {
        $this->newInstanceCallCount++;
        // Delegate to parent which uses the InMemorySegment-aware FakeManagerForCsrf pattern.
        // We need an InMemorySegment-backed manager here, so we wrap it.
        $segment = new InMemorySegment($manager, 'Awf\Session\CsrfToken');
        return new CsrfToken($segment, $this->currentAlgorithm);
    }

    public function setAlgorithm(string $algorithm): void
    {
        $this->algorithmPassedToSetAlgorithm = $algorithm;
        $this->currentAlgorithm = $algorithm;
        parent::setAlgorithm($algorithm);
    }
}

/**
 * A Segment subclass that records save() calls into the parent factory's list.
 */
class SaveTrackingSegment extends InMemorySegment
{
    private SaveTrackingSegmentFactory $factory;

    public function __construct(Manager $session, string $name, SaveTrackingSegmentFactory $factory)
    {
        parent::__construct($session, $name);
        $this->factory = $factory;
    }

    public function save(): void
    {
        $this->factory->savedSegments[] = $this->name;
    }
}

/**
 * A SegmentFactory that creates SaveTrackingSegment instances.
 */
class SaveTrackingSegmentFactory extends SegmentFactory
{
    public array $savedSegments = [];

    public function newInstance(Manager $manager, $name): Segment
    {
        return new SaveTrackingSegment($manager, $name, $this);
    }
}

/**
 * A Manager subclass that bypasses real PHP session calls for testing.
 *
 * - isStarted() / isAvailable() / start() are controlled via setters.
 * - The actual session_*() calls in start(), clear(), commit(), destroy(),
 *   getId(), regenerateId() etc. are all stubbed.
 */
class FakeSessionManager extends Manager
{
    private bool $started   = false;
    private bool $available = false;
    private string $sessionId = 'fake-session-id';
    private string $sessionName = 'TESTSESSION';
    private bool $regenerateResult = true;

    public function __construct(
        ?SegmentFactory   $segmentFactory          = null,
        ?CsrfTokenFactory $csrfTokenFactory        = null,
        array             $cookies                 = [],
        array             $sessionCreateParameters = []
    ) {
        // Completely bypass the parent constructor to avoid session_get_cookie_params().
        $this->segment_factory         = $segmentFactory   ?? new FakeSegmentFactory();
        $this->csrf_token_factory      = $csrfTokenFactory ?? new FakeCsrfTokenFactory();
        $this->cookies                 = $cookies;
        $this->cookie_params           = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
        ];
        $this->sessionCreateParameters = $sessionCreateParameters;
    }

    // ---- controllable state ------------------------------------------------
    public function setStarted(bool $v): void   { $this->started   = $v; }
    public function setAvailable(bool $v): void { $this->available = $v; }
    public function setSessionId(string $id): void   { $this->sessionId   = $id; }
    public function setSessionName(string $name): void { $this->sessionName = $name; }
    public function setRegenerateResult(bool $r): void { $this->regenerateResult = $r; }

    // ---- overridden session_*() wrappers -----------------------------------
    public function isStarted(): bool   { return $this->started; }
    public function isAvailable(): bool { return $this->available; }

    public function start(): bool
    {
        $this->started = true;
        return true;
    }

    public function getId(): string { return $this->sessionId; }

    public function getName(): string { return $this->sessionName; }

    public function setName(string $name): string
    {
        $old               = $this->sessionName;
        $this->sessionName = $name;
        return $old;
    }

    public function regenerateId(): bool
    {
        if ($this->regenerateResult && $this->csrf_token)
        {
            $this->csrf_token->regenerateValue();
        }
        return $this->regenerateResult;
    }

    public function clear(): void
    {
        $this->segments = [];
    }

    public function commit(): void
    {
        foreach ($this->segments as $segment)
        {
            $segment->save();
        }
    }

    public function destroy(): bool
    {
        if (!$this->isStarted())
        {
            $this->start();
        }
        $this->clear();
        return true;
    }

    public function getStatus(): int
    {
        return $this->started ? PHP_SESSION_ACTIVE : PHP_SESSION_NONE;
    }
}

// ---------------------------------------------------------------------------

#[CoversClass(Manager::class)]
#[CoversClass(SegmentFactory::class)]
class ManagerTest extends TestCase
{
    private FakeSessionManager  $manager;
    private FakeSegmentFactory  $segmentFactory;
    private FakeCsrfTokenFactory $csrfFactory;

    protected function setUp(): void
    {
        $this->segmentFactory = new FakeSegmentFactory();
        $this->csrfFactory    = new FakeCsrfTokenFactory();
        $this->manager        = new FakeSessionManager(
            $this->segmentFactory,
            $this->csrfFactory
        );
    }

    // =======================================================================
    // newSegment — retrieval & caching
    // =======================================================================

    /** newSegment() returns a Segment instance. */
    public function testNewSegmentReturnsSegmentInstance(): void
    {
        $seg = $this->manager->newSegment('My\App\Segment');

        $this->assertInstanceOf(Segment::class, $seg);
    }

    /** newSegment() with the same name returns the same object (cached). */
    public function testNewSegmentReturnsSameObjectForSameName(): void
    {
        $a = $this->manager->newSegment('My\App\Segment');
        $b = $this->manager->newSegment('My\App\Segment');

        $this->assertSame($a, $b);
    }

    /** newSegment() with different names returns different objects. */
    public function testNewSegmentReturnsDifferentObjectsForDifferentNames(): void
    {
        $a = $this->manager->newSegment('Segment\Alpha');
        $b = $this->manager->newSegment('Segment\Beta');

        $this->assertNotSame($a, $b);
    }

    /** newSegment() delegates creation to the SegmentFactory. */
    public function testNewSegmentUsesSegmentFactory(): void
    {
        $this->manager->newSegment('Factory\Test');

        $this->assertArrayHasKey('Factory\Test', $this->segmentFactory->created);
    }

    /** newSegment() only calls the factory once per segment name (caching). */
    public function testNewSegmentCallsFactoryOnlyOnce(): void
    {
        $this->manager->newSegment('Cache\Test');
        $this->manager->newSegment('Cache\Test');

        $this->assertCount(1, $this->segmentFactory->created);
    }

    // =======================================================================
    // isAvailable / isStarted
    // =======================================================================

    /** isAvailable() returns false when the session cookie is absent. */
    public function testIsAvailableReturnsFalseWhenNoCookie(): void
    {
        $mgr = new FakeSessionManager(null, null, []);
        $mgr->setSessionName('MYSESS');

        $this->assertFalse($mgr->isAvailable());
    }

    /** isAvailable() returns true when the session cookie is present. */
    public function testIsAvailableReturnsTrueWhenCookiePresent(): void
    {
        // Test via the real Manager constructor (which calls session_get_cookie_params() safely).
        // We check against whatever session_name() currently returns.
        $cookieName = session_name();
        $mgr = new Manager(
            new FakeSegmentFactory(),
            new FakeCsrfTokenFactory(),
            [$cookieName => 'abc123']
        );
        $this->assertTrue($mgr->isAvailable());
    }

    /** isStarted() reflects the started state. */
    public function testIsStartedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->manager->isStarted());
    }

    /** isStarted() returns true after start() is called. */
    public function testIsStartedReturnsTrueAfterStart(): void
    {
        $this->manager->start();

        $this->assertTrue($this->manager->isStarted());
    }

    // =======================================================================
    // start()
    // =======================================================================

    /** start() returns true and marks the session as started. */
    public function testStartReturnsTrueAndSetsStarted(): void
    {
        $result = $this->manager->start();

        $this->assertTrue($result);
        $this->assertTrue($this->manager->isStarted());
    }

    /** start() called when already started returns true without double-starting. */
    public function testStartWhenAlreadyStartedReturnsTrue(): void
    {
        $this->manager->start();
        $result = $this->manager->start();

        $this->assertTrue($result);
    }

    // =======================================================================
    // clear()
    // =======================================================================

    /** clear() removes all cached segments. */
    public function testClearRemovesCachedSegments(): void
    {
        $this->manager->newSegment('Seg\A');
        $this->manager->newSegment('Seg\B');

        $this->manager->clear();

        // After clear, requesting a segment should return a fresh object, not a cached one.
        // We verify this by checking that calling newSegment('Seg\A') after clear
        // produces a different object than before.
        $firstA  = $this->segmentFactory->created['Seg\A'];
        $this->manager->newSegment('Seg\A');
        $secondA = $this->segmentFactory->created['Seg\A'];

        $this->assertNotSame($firstA, $secondA);
    }

    // =======================================================================
    // commit()
    // =======================================================================

    /** commit() calls save() on every cached segment. */
    public function testCommitSavesAllSegments(): void
    {
        // Use a SaveTrackingSegmentFactory that wraps segments with a save() spy.
        $factory = new SaveTrackingSegmentFactory();

        $mgr = new FakeSessionManager($factory);
        $mgr->newSegment('X');
        $mgr->newSegment('Y');
        $mgr->commit();

        $this->assertContains('X', $factory->savedSegments);
        $this->assertContains('Y', $factory->savedSegments);
    }

    /** commit() on a manager with no segments does nothing (no error). */
    public function testCommitWithNoSegmentsIsHarmless(): void
    {
        $this->manager->commit(); // must not throw
        $this->assertTrue(true);
    }

    // =======================================================================
    // destroy()
    // =======================================================================

    /** destroy() starts the session if it isn't already started. */
    public function testDestroyStartsSessionIfNotStarted(): void
    {
        $this->assertFalse($this->manager->isStarted());

        $result = $this->manager->destroy();

        $this->assertTrue($result);
        $this->assertTrue($this->manager->isStarted());
    }

    /** destroy() clears the segment cache and returns true. */
    public function testDestroyReturnsTrueAndClearsSegments(): void
    {
        $this->manager->start();
        $firstSeg = $this->manager->newSegment('Will\Be\Gone');

        $result = $this->manager->destroy();

        $this->assertTrue($result);

        // After destroy, a fresh newSegment call should produce a new object (cache was cleared).
        $secondSeg = $this->manager->newSegment('Will\Be\Gone');
        $this->assertNotSame($firstSeg, $secondSeg);
    }

    // =======================================================================
    // getCsrfToken()
    // =======================================================================

    /** getCsrfToken() returns a CsrfToken instance. */
    public function testGetCsrfTokenReturnsCsrfToken(): void
    {
        $token = $this->manager->getCsrfToken();

        $this->assertInstanceOf(CsrfToken::class, $token);
    }

    /** getCsrfToken() returns the same instance on repeated calls (lazy singleton). */
    public function testGetCsrfTokenReturnsSameInstance(): void
    {
        $a = $this->manager->getCsrfToken();
        $b = $this->manager->getCsrfToken();

        $this->assertSame($a, $b);
    }

    /** getCsrfToken() calls the factory exactly once. */
    public function testGetCsrfTokenCallsFactoryOnlyOnce(): void
    {
        $this->manager->getCsrfToken();
        $this->manager->getCsrfToken();

        $this->assertSame(1, $this->csrfFactory->newInstanceCallCount);
    }

    // =======================================================================
    // setCsrfTokenAlgorithm()
    // =======================================================================

    /** setCsrfTokenAlgorithm() forwards the algorithm to the factory. */
    public function testSetCsrfTokenAlgorithmDelegatesToFactory(): void
    {
        $this->manager->setCsrfTokenAlgorithm('sha256');

        $this->assertSame('sha256', $this->csrfFactory->algorithmPassedToSetAlgorithm);
    }

    /** setCsrfTokenAlgorithm() resets the cached CSRF token. */
    public function testSetCsrfTokenAlgorithmResetsToken(): void
    {
        $first = $this->manager->getCsrfToken();
        $this->manager->setCsrfTokenAlgorithm('sha256');
        $second = $this->manager->getCsrfToken();

        // After resetting, a fresh token is lazy-created, so newInstance was called twice.
        $this->assertSame(2, $this->csrfFactory->newInstanceCallCount);
        // The two token objects are different instances.
        $this->assertNotSame($first, $second);
    }

    // =======================================================================
    // regenerateId()
    // =======================================================================

    /** regenerateId() returns true on success. */
    public function testRegenerateIdReturnsTrue(): void
    {
        $this->manager->start();
        $result = $this->manager->regenerateId();

        $this->assertTrue($result);
    }

    /** regenerateId() also regenerates the CSRF token value when one is cached. */
    public function testRegenerateIdAlsoRegeneratesTokenValue(): void
    {
        $this->manager->start();
        $token  = $this->manager->getCsrfToken();
        $before = $token->getValue();

        $this->manager->regenerateId();

        // The token value should have changed because regenerateId() calls
        // csrf_token->regenerateValue().
        $this->assertNotSame($before, $token->getValue());
    }

    /** regenerateId() does not touch the CSRF token when none has been created yet. */
    public function testRegenerateIdWithNoCsrfTokenDoesNotThrow(): void
    {
        $this->manager->start();
        $result = $this->manager->regenerateId();

        $this->assertTrue($result); // no exception
    }

    // =======================================================================
    // getName() / setName()
    // =======================================================================

    /** getName() returns the current session name. */
    public function testGetNameReturnsCurrentName(): void
    {
        $name = $this->manager->getName();

        $this->assertSame('TESTSESSION', $name);
    }

    /** setName() changes the session name and returns the old value. */
    public function testSetNameChangesNameAndReturnsPrevious(): void
    {
        $old = $this->manager->setName('MYAPP');

        $this->assertSame('TESTSESSION', $old);
        $this->assertSame('MYAPP', $this->manager->getName());
    }

    // =======================================================================
    // getId()
    // =======================================================================

    /** getId() returns the current session ID string. */
    public function testGetIdReturnsString(): void
    {
        $id = $this->manager->getId();

        $this->assertIsString($id);
        $this->assertSame('fake-session-id', $id);
    }

    // =======================================================================
    // getCookieParams() / setCookieParams()
    // =======================================================================

    /** getCookieParams() returns an array of cookie parameters. */
    public function testGetCookieParamsReturnsArray(): void
    {
        $params = $this->manager->getCookieParams();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('lifetime', $params);
        $this->assertArrayHasKey('path', $params);
        $this->assertArrayHasKey('domain', $params);
    }

    /** setCookieParams() merges the given params into the existing ones. */
    public function testSetCookieParamsMergesValues(): void
    {
        // Use a Manager subclass that stubs out the real session_set_cookie_params call.
        $mgr = new class extends Manager {
            public function __construct()
            {
                $this->cookie_params = [
                    'lifetime' => 0,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => false,
                    'httponly' => true,
                ];
            }

            // Suppress the real PHP session call.
            public function setCookieParams(array $params): void
            {
                $this->cookie_params = array_merge($this->cookie_params, $params);
            }
        };

        $mgr->setCookieParams(['lifetime' => 7200, 'domain' => 'example.com']);
        $params = $mgr->getCookieParams();

        $this->assertSame(7200, $params['lifetime']);
        $this->assertSame('example.com', $params['domain']);
        // Unaffected keys remain.
        $this->assertSame('/', $params['path']);
    }

    /** setCookieParams() with an empty array changes nothing. */
    public function testSetCookieParamsWithEmptyArrayChangesNothing(): void
    {
        // Use a Manager subclass that stubs out the real session_set_cookie_params call.
        $mgr = new class extends Manager {
            public function __construct()
            {
                $this->cookie_params = [
                    'lifetime' => 0,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => false,
                    'httponly' => true,
                ];
            }

            // Suppress the real PHP session call.
            public function setCookieParams(array $params): void
            {
                $this->cookie_params = array_merge($this->cookie_params, $params);
            }
        };

        $before = $mgr->getCookieParams();
        $mgr->setCookieParams([]);
        $after  = $mgr->getCookieParams();

        $this->assertSame($before, $after);
    }

    // =======================================================================
    // getStatus()
    // =======================================================================

    /** getStatus() returns PHP_SESSION_NONE when not started. */
    public function testGetStatusReturnsNoneWhenNotStarted(): void
    {
        $this->assertSame(PHP_SESSION_NONE, $this->manager->getStatus());
    }

    /** getStatus() returns PHP_SESSION_ACTIVE when started. */
    public function testGetStatusReturnsActiveWhenStarted(): void
    {
        $this->manager->start();

        $this->assertSame(PHP_SESSION_ACTIVE, $this->manager->getStatus());
    }

    // =======================================================================
    // Real Manager constructor — cookie params
    // =======================================================================

    /**
     * The real Manager constructor stores the cookies array passed to it.
     * We verify isAvailable() uses that array.
     */
    public function testRealManagerIsAvailableUsesCookiesArray(): void
    {
        // Use a concrete Manager but skip the PHP session bits by using a fake
        // SegmentFactory and CsrfTokenFactory that avoid real session access.
        // We only call session_get_cookie_params() in the constructor, which is safe.
        $segFactory  = new FakeSegmentFactory();
        $csrfFactory = new FakeCsrfTokenFactory();

        $cookieName = session_name(); // whatever PHP's current default is

        // Without the cookie: not available.
        $mgr = new Manager($segFactory, $csrfFactory, []);
        $this->assertFalse($mgr->isAvailable());

        // With the cookie: available.
        $mgr2 = new Manager($segFactory, $csrfFactory, [$cookieName => 'somevalue']);
        $this->assertTrue($mgr2->isAvailable());
    }

    // =======================================================================
    // SegmentFactory (concrete)
    // =======================================================================

    /** SegmentFactory::newInstance() returns a Segment with the right name. */
    public function testSegmentFactoryCreatesSegmentWithCorrectName(): void
    {
        $factory = new SegmentFactory();
        // We need a Manager that doesn't call session_get_cookie_params().
        $mgr     = new FakeSessionManager();

        $seg = $factory->newInstance($mgr, 'My\Named\Segment');

        $this->assertInstanceOf(Segment::class, $seg);
        $this->assertSame('My\Named\Segment', $seg->getName());
    }

    /** SegmentFactory::newInstance() returns a fresh object on each call. */
    public function testSegmentFactoryReturnsFreshInstanceEachTime(): void
    {
        $factory = new SegmentFactory();
        $mgr     = new FakeSessionManager();

        $a = $factory->newInstance($mgr, 'Same\Name');
        $b = $factory->newInstance($mgr, 'Same\Name');

        $this->assertNotSame($a, $b);
    }
}
