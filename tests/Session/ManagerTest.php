<?php
/**
 * @package        awf
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Session;

use Awf\Session\CsrfTokenFactory;
use Awf\Session\Manager;
use Awf\Session\Randval;
use Awf\Session\SegmentFactory;
use Awf\Tests\Stubs\Session\MockSessionHandler;
use Awf\Utils\Phpfunc;

/**
 * @coversDefaultTestClass \Awf\Session\Manager
 *
 * @package Awf\Tests\Session
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{
	/** @var  \Awf\Session\Manager  The session object */
	protected $session;

	protected function setUp()
	{
		$handler = new MockSessionHandler();
		session_set_save_handler(
			array($handler, 'open'),
			array($handler, 'close'),
			array($handler, 'read'),
			array($handler, 'write'),
			array($handler, 'destroy'),
			array($handler, 'gc')
		);
		$this->session = $this->newSession();
	}

	protected function newSession(array $cookies = array())
	{
		return new Manager(
			new SegmentFactory(),
			new CsrfTokenFactory(new Randval(new Phpfunc())),
			$cookies
		);
	}

	public function teardown()
	{
		session_unset();
		if (session_id() !== '')
		{
			@session_destroy();
		}
	}

	public function testStart()
	{
		$this->session->start();
		$this->assertTrue($this->session->isStarted());
	}

	public function testClear()
	{
		$this->assertFalse($this->session->isStarted());
		$this->session->start();

		// get a test segment and set some data
		$segment = $this->session->newSegment('test');
		$segment->foo = 'bar';
		$segment->baz = 'dib';

		$expect = array('test' => array('foo' => 'bar', 'baz' => 'dib'));
		$this->assertSame($expect, $_SESSION);

		// now clear it
		$this->session->clear();
		$this->assertSame(array(), $_SESSION);
	}

	public function testDestroy()
	{
		$this->assertFalse($this->session->isStarted());
		$this->session->start();

		// get a test segment and set some data
		$segment = $this->session->newSegment('test');
		$segment->foo = 'bar';
		$segment->baz = 'dib';

		$expect = array('test' => array('foo' => 'bar', 'baz' => 'dib'));
		$this->assertSame($expect, $_SESSION);

		// now destroy it
		$this->session->destroy();
		$this->assertFalse($this->session->isStarted());
	}

	/**
	 * @requires PHP 5.4
	 */
	public function testCommit()
	{
		$this->assertFalse($this->session->isStarted());

		$this->session->commit();
		$this->assertFalse($this->session->isStarted());
	}

	/**
	 * @requires PHP 5.4
	 */
	public function testCommitAndDestroy()
	{
		// get a test segment and set some data
		$this->session->destroy();
		$this->session->start();

		$segment = $this->session->newSegment('test');
		$segment->foo = 'bar';
		$segment->baz = 'dib';

		$expect = array('test' => array('foo' => 'bar', 'baz' => 'dib'));
		$this->assertSame($expect, $_SESSION);

		// This test will fail on PHP 5.3 due to the lack of session_status() and our approximation of its
		// results in getStatus()
		$this->session->commit();
		$this->assertFalse($this->session->isStarted());

		$this->session->destroy();
		$this->assertFalse($this->session->isStarted());

		$segment = $this->session->newSegment('test');
		$this->assertSame(array(), $_SESSION);
	}

	public function testNewSegment()
	{
		$this->session->start();
		$segment = $this->session->newSegment('test');
		$this->assertInstanceof('Awf\Session\Segment', $segment);
	}

	public function testGetCsrfToken()
	{
		$actual = $this->session->getCsrfToken();
		$expect = 'Awf\Session\CsrfToken';
		$this->assertInstanceOf($expect, $actual);
	}

	public function testisAvailable()
	{
		// should not look active
		$this->assertFalse($this->session->isAvailable());

		// fake a cookie
		$cookies = array(
			$this->session->getName() => 'fake-cookie-value',
		);
		$this->session = $this->newSession($cookies);

		// now it should look active
		$this->assertTrue($this->session->isAvailable());
	}

	public function testGetAndRegenerateId()
	{
		$this->session->start();
		$old_id = $this->session->getId();
		$this->session->regenerateId();
		$new_id = $this->session->getId();
		$this->assertTrue($old_id != $new_id);

		// check the csrf token as well
		$old_value = $this->session->getCsrfToken()->getValue();
		$this->session->regenerateId();
		$new_value = $this->session->getCsrfToken()->getValue();
		$this->assertTrue($old_value != $new_value);
	}

	public function testSetAndGetName()
	{
		$expect = 'new_name';
		$this->session->setName($expect);
		$actual = $this->session->getName();
		$this->assertSame($expect, $actual);
	}

	public function testSetAndGetSavePath()
	{
		$expect = '/new/save/path';
		$this->session->setSavePath($expect);
		$actual = $this->session->getSavePath();
		$this->assertSame($expect, $actual);
	}

	public function testSetAndGetCookieParams()
	{
		$expect = $this->session->getCookieParams();
		$expect['lifetime'] = '999';
		$this->session->setCookieParams($expect);
		$actual = $this->session->getCookieParams();
		$this->assertSame($expect, $actual);
	}

	public function testSetAndGetCacheExpire()
	{
		$expect = 123;
		$this->session->setCacheExpire($expect);
		$actual = $this->session->getCacheExpire();
		$this->assertSame($expect, $actual);
	}

	public function testSetAndGetCacheLimiter()
	{
		$expect = 'private_no_cache';
		$this->session->setCacheLimiter($expect);
		$actual = $this->session->getCacheLimiter();
		$this->assertSame($expect, $actual);
	}

	public function testGetStatus()
	{
		$expect = PHP_SESSION_NONE;
		$actual = $this->session->getStatus();
		$this->assertSame($expect, $actual);

		$expect = PHP_SESSION_ACTIVE;
		// Well, screw this. You can't test opening a session unless you tell PHP to shut up about headers having
		// already been sent.
		@$this->session->start();
		$actual = $this->session->getStatus();
		$this->assertSame($expect, $actual);
	}
}
 