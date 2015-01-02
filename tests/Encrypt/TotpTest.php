<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Encrypt;

use Awf\Encrypt\Totp;
use Awf\Tests\Helpers\AwfTestCase;

/**
 * @coversDefaultClass Awf\Encrypt\Totp
 *
 * @package Awf\Tests\Encrypt
 */
class TotpTest extends AwfTestCase
{
	/** @var  Totp */
	protected $totp;

	/**
	 * @return  void
	 */
	protected function setUp()
	{
		parent::setUp(false);

		// VARS: $timeStep = 30, $passCodeLength = 6, $secretLength = 10, $base32=null
		$timeStep 				= 30;
		$passCodeLength 		= 6;
		$secretLength 			= 10;
		$this->secretLength 	= $secretLength;

		$this->totp = new Totp($timeStep, $passCodeLength, $secretLength);
	}

	/**
	 * @return  void
	 */
	public function testGetPeriod()
	{
		// Time as I wrote the test 1375000339 -> 45833344
		$this->assertEquals(45833344, $this->totp->getPeriod(1375000339));
	}

	/**
	 * @return  void
	 */
	public function testGetcode()
	{
		$this->assertEquals(567377, $this->totp->getCode('KREECVCJKNKE6VCBJRGFSU2FINJEKVA', 1375000339));
	}

	/**
	 * @return  void
	 */
	public function testGetUrl()
	{
		$this->assertEquals(
			'https://chart.googleapis.com/chart?chs=200x200&chld=Q|2&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FJohnnieWalker%40example.com%3Fsecret%3DKREECVCJKNKE6VCBJRGFSU2FINJEKVA',
			$this->totp->getUrl('JohnnieWalker', 'example.com', 'KREECVCJKNKE6VCBJRGFSU2FINJEKVA')
		);
	}

	public function testGenerateSecret()
	{
		$secret1 = $this->totp->generateSecret();
		$secret2 = $this->totp->generateSecret();

		$this->assertNotEquals(
			$secret1,
			$secret2
		);
	}

	public function testCheckCode()
	{
		$secret = '4FDAGLLSP6BIVU5H';
		$time = 1375000339;

		$code = $this->totp->getCode($secret, $time);
		$codePrev = $this->totp->getCode($secret, $time - 30);
		$codeNext = $this->totp->getCode($secret, $time + 30);

		$this->assertTrue(
			$this->totp->checkCode($secret, $code, $time)
		);

		$this->assertTrue(
			$this->totp->checkCode($secret, $codePrev, $time)
		);

		$this->assertTrue(
			$this->totp->checkCode($secret, $codeNext, $time)
		);
	}
}
