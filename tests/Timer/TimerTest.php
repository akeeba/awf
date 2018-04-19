<?php
/**
 * @package		awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Timer;

use Awf\Timer\Timer;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * Tests the Timer class
 *
 * @package Awf\Tests\Timer
 *
 * @coversDefaultClass Awf\Timer\Timer
 */
class TimerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers ::__construct
	 */
	public function testConstruct()
	{
		$timer = new Timer(10, 10);

		$this->assertEquals(
			1,
			$this->getObjectAttribute($timer, 'max_exec_time'),
			'Line: ' . __LINE__ . '.'
		);

		$now = microtime(true);

		$this->assertLessThanOrEqual(
			$now,
			$this->getObjectAttribute($timer, 'start_time'),
			'Line: ' . __LINE__ . '.'
		);

		return $timer;
	}

	/**
	 * @covers ::__wakeup
	 */
	public function testWakeup()
	{
		$timer = new Timer(1, 100);

		$originalMicrotime = $this->getObjectAttribute($timer, 'start_time');

		$serialised = serialize($timer);
		unset($timer);

		$newMicrotime = microtime(true);
		$newTimer = unserialize($serialised);

		$this->assertGreaterThanOrEqual(
			$newMicrotime,
			$this->getObjectAttribute($newTimer, 'start_time'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers ::getTimeLeft()
	 */
	public function testGetTimeLeft()
	{
		$timer = new Timer(1, 100);

		$this->assertGreaterThanOrEqual(
			'0.9',
			$timer->getTimeLeft(),
			'Line: ' . __LINE__ . '.'
		);

		$originalMicrotime = $this->getObjectAttribute($timer, 'start_time');
		ReflectionHelper::setValue($timer, 'start_time', $originalMicrotime - 1);

		$this->assertLessThanOrEqual(
			0,
			$timer->getTimeLeft(),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers ::getRunningTime
	 */
	public function testGetRunningTime()
	{
		$timer = new Timer(1, 100);

		$this->assertGreaterThanOrEqual(
			'0',
			$timer->getRunningTime(),
			'Line: ' . __LINE__ . '.'
		);

		$originalMicrotime = $this->getObjectAttribute($timer, 'start_time');
		ReflectionHelper::setValue($timer, 'start_time', $originalMicrotime - 1);

		$this->assertGreaterThanOrEqual(
			1,
			$timer->getRunningTime(),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers ::resetTime
	 */
	public function testResetTime()
	{
		$timer = new Timer(1, 100);
		$originalMicrotime = $this->getObjectAttribute($timer, 'start_time');
		$timer->resetTime();

		$this->assertGreaterThanOrEqual(
			$originalMicrotime,
			$this->getObjectAttribute($timer, 'start_time'),
			'Line: ' . __LINE__ . '.'
		);
	}
}
