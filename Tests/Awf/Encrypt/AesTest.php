<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Encrypt;


use Awf\Encrypt\Aes;

/**
 * @coversDefaultClass Awf\Encrypt\Aes
 *
 * @package Tests\Awf\Encrypt
 */
class AesTest extends \PHPUnit_Framework_TestCase
{
	/** @var  Aes */
	protected $aes;

	/**
	 * @return  void
	 */
	protected function setUp()
	{
		// Check if PHP has mcrypt installed
		if (function_exists('mcrypt_module_open'))
		{
			$this->aes = new Aes('x123456789012345678901234567890x');
		}
	}

	/**
	 * @cover Awf\Encrypt\Aes
	 *
	 * [testCryptProcess description]
	 *
	 * @return  void
	 */
	public function testCryptProcess()
	{
		if (function_exists('mcrypt_module_open'))
		{
			// Only run test when PHP has mcrypt installed
			$str = 'THATISINSANE';

			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			$str = 'Χρησιμοποιώντας μη λατινικούς χαρακτήρες';
			$es  = $this->aes->encryptString($str, false);
			$ds  = $this->aes->decryptString($es, false);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);
		}
		else
		{
			$this->markTestSkipped('mcrypt is not supported on this system');
		}
	}

}
 