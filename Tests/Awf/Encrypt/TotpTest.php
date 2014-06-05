<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Encrypt;

use Awf\Encrypt\Totp;

/**
 * @coversDefaultClass Awf\Encrypt\Totp
 *
 * @package Tests\Awf\Encrypt
 */
class TotpTest extends \PHPUnit_Framework_TestCase
{
	/** @var  Totp */
	protected $totp;

	/**
	 * @return  void
	 */
	protected function setUp()
	{
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
}
 