<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Timer;

use Awf\Timer\Timer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class TimerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the value of a private property via reflection.
     */
    private function getPrivate(object $obj, string $property): mixed
    {
        $rp = new ReflectionProperty($obj, $property);

        return $rp->getValue($obj);
    }

    /**
     * Sets the value of a private property via reflection.
     */
    private function setPrivate(object $obj, string $property, mixed $value): void
    {
        $rp = new ReflectionProperty($obj, $property);
        $rp->setValue($obj, $value);
    }

    // -------------------------------------------------------------------------
    // Constructor — max_exec_time calculation
    // -------------------------------------------------------------------------

    public static function constructorMaxExecProvider(): array
    {
        return [
            // [max_exec_time, runtime_bias, expectedMaxExec]
            'default 5s / 75%'     => [5,   75,  3.75],
            '10s / 50%'            => [10,  50,  5.0],
            '10s / 100%'           => [10, 100, 10.0],
            '0s / 75%'             => [0,   75,  0.0],
            '100s / 10%'           => [100,  10, 10.0],
            '60s / 80%'            => [60,   80, 48.0],
        ];
    }

    #[DataProvider('constructorMaxExecProvider')]
    public function testConstructorCalculatesMaxExecTime(
        int   $maxExecTime,
        int   $runtimeBias,
        float $expectedMaxExec
    ): void {
        $timer = new Timer($maxExecTime, $runtimeBias);

        $stored = $this->getPrivate($timer, 'max_exec_time');

        self::assertEqualsWithDelta($expectedMaxExec, $stored, 1e-9);
    }

    public function testConstructorRecordsStartTime(): void
    {
        $before = microtime(true);
        $timer  = new Timer();
        $after  = microtime(true);

        $startTime = $this->getPrivate($timer, 'start_time');

        self::assertGreaterThanOrEqual($before, $startTime);
        self::assertLessThanOrEqual($after, $startTime);
    }

    // -------------------------------------------------------------------------
    // getRunningTime
    // -------------------------------------------------------------------------

    public function testGetRunningTimeIsNonNegative(): void
    {
        $timer = new Timer(5, 75);

        self::assertGreaterThanOrEqual(0.0, $timer->getRunningTime());
    }

    public function testGetRunningTimeGrowsOverTime(): void
    {
        $timer = new Timer(5, 75);

        $first  = $timer->getRunningTime();
        usleep(5_000); // 5 ms — tiny but deterministic enough
        $second = $timer->getRunningTime();

        self::assertGreaterThan($first, $second);
    }

    public function testGetRunningTimeArithmetic(): void
    {
        $timer = new Timer(10, 100);

        // Inject a start time 3 seconds in the past
        $fakeStart = microtime(true) - 3.0;
        $this->setPrivate($timer, 'start_time', $fakeStart);

        $running = $timer->getRunningTime();

        // Should be approximately 3 seconds
        self::assertEqualsWithDelta(3.0, $running, 0.05);
    }

    // -------------------------------------------------------------------------
    // getTimeLeft
    // -------------------------------------------------------------------------

    public function testGetTimeLeftEqualsMaxExecMinusRunning(): void
    {
        $timer = new Timer(10, 100); // max_exec_time = 10.0

        // Pretend 4 seconds have elapsed
        $this->setPrivate($timer, 'start_time', microtime(true) - 4.0);

        $timeLeft = $timer->getTimeLeft();

        // Should be approximately 6 seconds
        self::assertEqualsWithDelta(6.0, $timeLeft, 0.05);
    }

    public function testGetTimeLeftIsNegativeAfterExpiry(): void
    {
        $timer = new Timer(5, 100); // max_exec_time = 5.0

        // Pretend 10 seconds have elapsed (past the limit)
        $this->setPrivate($timer, 'start_time', microtime(true) - 10.0);

        $timeLeft = $timer->getTimeLeft();

        self::assertLessThan(0.0, $timeLeft);
    }

    public function testGetTimeLeftIsPositiveAtStart(): void
    {
        $timer = new Timer(5, 75); // max_exec_time = 3.75

        self::assertGreaterThan(0.0, $timer->getTimeLeft());
    }

    public function testGetTimeLeftConsistentWithGetRunningTime(): void
    {
        $timer = new Timer(10, 100); // max_exec_time = 10.0

        $running  = $timer->getRunningTime();
        $timeLeft = $timer->getTimeLeft();

        // running + timeLeft should equal max_exec_time within a tiny tolerance
        $maxExec = $this->getPrivate($timer, 'max_exec_time');

        self::assertEqualsWithDelta($maxExec, $running + $timeLeft, 0.001);
    }

    // -------------------------------------------------------------------------
    // resetTime
    // -------------------------------------------------------------------------

    public function testResetTimeResetsStartTime(): void
    {
        $timer = new Timer(10, 100);

        // Age the timer by 5 seconds via reflection
        $this->setPrivate($timer, 'start_time', microtime(true) - 5.0);

        self::assertGreaterThan(4.0, $timer->getRunningTime(), 'Pre-condition: timer should be aged');

        $beforeReset = microtime(true);
        $timer->resetTime();
        $afterReset = microtime(true);

        $newStart = $this->getPrivate($timer, 'start_time');
        self::assertGreaterThanOrEqual($beforeReset, $newStart);
        self::assertLessThanOrEqual($afterReset, $newStart);
    }

    public function testResetTimeReducesRunningTime(): void
    {
        $timer = new Timer(10, 100);

        // Age the timer
        $this->setPrivate($timer, 'start_time', microtime(true) - 5.0);

        $runningBefore = $timer->getRunningTime();
        $timer->resetTime();
        $runningAfter = $timer->getRunningTime();

        self::assertLessThan($runningBefore, $runningAfter);
    }

    // -------------------------------------------------------------------------
    // __wakeup — serialisation / deserialisation
    // -------------------------------------------------------------------------

    public function testWakeupResetsStartTime(): void
    {
        $timer = new Timer(5, 75);

        // Age the timer by reflection before serialising
        $this->setPrivate($timer, 'start_time', microtime(true) - 100.0);

        $beforeWakeup = microtime(true);
        $serialised   = serialize($timer);
        $restored     = unserialize($serialised);
        $afterWakeup  = microtime(true);

        $restoredStart = $this->getPrivate($restored, 'start_time');
        self::assertGreaterThanOrEqual($beforeWakeup, $restoredStart);
        self::assertLessThanOrEqual($afterWakeup, $restoredStart);
    }

    public function testWakeupPreservesMaxExecTime(): void
    {
        $timer = new Timer(20, 50); // max_exec_time = 10.0

        $maxBefore = $this->getPrivate($timer, 'max_exec_time');

        $restored = unserialize(serialize($timer));

        $maxAfter = $this->getPrivate($restored, 'max_exec_time');
        self::assertSame($maxBefore, $maxAfter);
    }

    public function testWakeupRunningTimeIsSmall(): void
    {
        $timer = new Timer(10, 100);

        // Age before serialising
        $this->setPrivate($timer, 'start_time', microtime(true) - 50.0);

        $restored = unserialize(serialize($timer));

        // After wakeup, running time should be tiny (much less than 50 s)
        self::assertLessThan(1.0, $restored->getRunningTime());
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testZeroBiasYieldsZeroMaxExecTime(): void
    {
        $timer = new Timer(10, 0); // max_exec_time = 10 * 0 / 100 = 0.0

        $maxExec = $this->getPrivate($timer, 'max_exec_time');
        self::assertEquals(0, $maxExec);
    }

    public function testZeroBiasTimeLeftIsNegative(): void
    {
        $timer = new Timer(10, 0); // max_exec_time = 0.0 => immediately expired

        self::assertLessThanOrEqual(0.0, $timer->getTimeLeft());
    }

    public function testLargeBiasExceedsOriginalTime(): void
    {
        $timer = new Timer(10, 200); // max_exec_time = 10 * 200 / 100 = 20.0

        $maxExec = $this->getPrivate($timer, 'max_exec_time');
        self::assertEqualsWithDelta(20.0, $maxExec, 1e-9);
    }
}
