<?php
/**
 * @package		awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Session;


use Awf\Session\CsrfToken;
use Awf\Session\CsrfTokenFactory;
use Awf\Session\Manager;
use Awf\Session\Randval;
use Awf\Session\SegmentFactory;
use Awf\Tests\Stubs\Session\MockPhpfunc;
use Awf\Utils\Phpfunc;

class CsrfTokenTest extends \PHPUnit_Framework_TestCase
{
	/** @var  Manager */
	protected $session;

	/** @var  CsrfToken */
	protected $csrf_token;

	protected $name = __CLASS__;

	/** @var  Phpfunc */
	protected $phpfunc;

	protected function setUp()
	{
		$this->phpfunc = new MockPhpfunc();

		$this->session = new Manager(
			new SegmentFactory(),
			new CsrfTokenFactory(new Randval($this->phpfunc)),
			$_COOKIE
		);
	}

	public function teardown()
	{
		session_unset();
		if (session_id() !== '') {
			session_destroy();
		}
	}

	public function testLaziness()
	{
		if ($this->session->isStarted())
		{
			$this->session->destroy();
		}

		$this->assertFalse($this->session->isStarted());
		$token = $this->session->getCsrfToken();
		$this->assertTrue($this->session->isStarted());
	}

	public function testGetAndRegenerateValue()
	{
		$token = $this->session->getCsrfToken();

		$old = $token->getValue();
		$this->assertTrue($old != '');

		// with openssl
		$this->phpfunc->setExtensions(array('openssl'));
		$token->regenerateValue();
		$openssl = $token->getValue();
		$this->assertTrue($old != $openssl);

		// with mcrypt
		$this->phpfunc->setExtensions(array('mcrypt'));
		$token->regenerateValue();
		$mcrypt = $token->getValue();
		$this->assertTrue($old != $openssl && $old != $mcrypt);

		// with nothing (we use a pure PHP implementation)
		$this->phpfunc->setExtensions(array());
		$token->regenerateValue();
		$purephp = $token->getValue();
		$this->assertTrue($old != $openssl && $old != $mcrypt && $old != $purephp);
	}

	public function testIsValid()
	{
		$token = $this->session->getCsrfToken();
		$value = $token->getValue();

		$this->assertTrue($token->isValid($value));
		$token->regenerateValue();
		$this->assertFalse($token->isValid($value));
	}
}
