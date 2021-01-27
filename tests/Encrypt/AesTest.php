<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Encrypt;

use Awf\Encrypt\Aes;
use Awf\Tests\Helpers\AwfTestCase;

/**
 * @coversDefaultClass Awf\Encrypt\Aes
 *
 * @package Awf\Tests\Encrypt
 */
class AesTest extends AwfTestCase
{
	/** @var  Aes */
	protected $aes;

	/**
	 * @return  void
	 */
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);

		// Check if PHP has mcrypt installed
		if (function_exists('mcrypt_module_open'))
		{
			$this->aes = new Aes('x123456789012345678901234567890x');
		}
	}

	/**
	 * @cover Awf\Encrypt\Aes::IsSupported
	 *
	 * @return  void
	 */
	public function testIsSupported()
	{
		$functions_enabled = array(
			'mcrypt_get_key_size',
			'mcrypt_get_iv_size',
			'mcrypt_create_iv',
			'mcrypt_encrypt',
			'mcrypt_decrypt',
			'mcrypt_list_algorithms',
			'hash',
			'hash_algos',
			'base64_encode',
			'base64_decode'
		);

		$algorithms = array(
			'rijndael-128',
			'rijndael-192',
			'rijndael-256',
		);

		$hashAlgos = array(
			'sha256'
		);

		// Just for code coverage
		$this->assertNotNull(Aes::isSupported());

		// All prerequisites met = supported
		$this->assertTrue(Aes::isSupported());
	}

	/**
	 * @cover Awf\Encrypt\Aes
	 *
	 * @return  void
	 */
	public function testCryptProcess()
	{
		if (function_exists('mcrypt_module_open'))
		{
			// Regular string
			$str = 'THATISINSANE';

			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// UTF-8 data
			$str = 'Χρησιμοποιώντας μη λατινικούς χαρακτήρες';
			$es  = $this->aes->encryptString($str, false);
			$ds  = $this->aes->decryptString($es, false);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// Using an odd sized keystring (using sha256 to convert it to a key)
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog');
			$str = 'This is some very secret stuff that you are not supposed to transmit in clear text';
			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

		}
		else
		{
			$this->markTestSkipped('mcrypt is not supported on this system');
		}
	}

	/**
	 * @cover Awf\Encrypt\Aes
	 *
	 * @return  void
	 */
	public function testCryptProcess192()
	{
		if (function_exists('mcrypt_module_open'))
		{
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog', 192);

			// Regular string
			$str = 'THATISINSANE';

			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// UTF-8 data
			$str = 'Χρησιμοποιώντας μη λατινικούς χαρακτήρες';
			$es  = $this->aes->encryptString($str, false);
			$ds  = $this->aes->decryptString($es, false);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// Using an odd sized keystring (using sha256 to convert it to a key)
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog');
			$str = 'This is some very secret stuff that you are not supposed to transmit in clear text';
			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

		}
		else
		{
			$this->markTestSkipped('mcrypt is not supported on this system');
		}
	}

	/**
	 * @cover Awf\Encrypt\Aes
	 *
	 * @return  void
	 */
	public function testCryptProcess128()
	{
		if (function_exists('mcrypt_module_open'))
		{
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog', 128);

			// Regular string
			$str = 'THATISINSANE';

			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// UTF-8 data
			$str = 'Χρησιμοποιώντας μη λατινικούς χαρακτήρες';
			$es  = $this->aes->encryptString($str, false);
			$ds  = $this->aes->decryptString($es, false);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// Using an odd sized keystring (using sha256 to convert it to a key)
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog');
			$str = 'This is some very secret stuff that you are not supposed to transmit in clear text';
			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

		}
		else
		{
			$this->markTestSkipped('mcrypt is not supported on this system');
		}
	}

	/**
	 * @cover Awf\Encrypt\Aes
	 *
	 * @return  void
	 */
	public function testCryptProcessEcb()
	{
		if (function_exists('mcrypt_module_open'))
		{
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog', 256, 'ecb');

			// Regular string
			$str = 'THATISINSANE';

			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// UTF-8 data
			$str = 'Χρησιμοποιώντας μη λατινικούς χαρακτήρες';
			$es  = $this->aes->encryptString($str, false);
			$ds  = $this->aes->decryptString($es, false);
			$ds  = rtrim($ds, "\000");
			$this->assertNotEquals($str, $es);
			$this->assertEquals($str, $ds);

			// Using an odd sized keystring (using sha256 to convert it to a key)
			$this->aes = new Aes('The quick brown fox jumped over the lazy dog');
			$str = 'This is some very secret stuff that you are not supposed to transmit in clear text';
			$es  = $this->aes->encryptString($str, true);
			$ds  = $this->aes->decryptString($es, true);
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
