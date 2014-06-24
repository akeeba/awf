<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Application;

use Awf\Application\TransparentAuthentication;
use Awf\Encrypt\Aes;
use Awf\Encrypt\Totp;
use Awf\Tests\Helpers\ApplicationTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * Class TransparentAuthenticationTest
 *
 * @package Awf\Tests\Application
 *
 * @coversDefaultClass \Awf\Application\TransparentAuthentication
 */
class TransparentAuthenticationTest extends ApplicationTestCase
{
	/** @var  TransparentAuthentication */
	protected $auth;

	/** @var  string The TOTP key */
	protected static $totpKey = 'OBXARH6TY6L6YT64';

	public function testConstruct()
	{
		$auth = new TransparentAuthentication(static::$container);

		$this->assertEquals(static::$container, ReflectionHelper::getValue($auth, 'container'));
	}

	public function testAddAuthenticationMethod()
	{
		$this->auth->setAuthenticationMethods(array(
			TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext
		));

		// Try adding an existing method
		$this->auth->addAuthenticationMethod(TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext);

		$this->assertEquals(array(
			TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext,
		), ReflectionHelper::getValue($this->auth, 'authenticationMethods'));

		// Try adding a non-existent method
		$this->auth->addAuthenticationMethod(TransparentAuthentication::Auth_HTTPBasicAuth_TOTP);

		$this->assertEquals(array(
			TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext,
			TransparentAuthentication::Auth_HTTPBasicAuth_TOTP
		), ReflectionHelper::getValue($this->auth, 'authenticationMethods'));

		// Try adding an existing method again
		$this->auth->addAuthenticationMethod(TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext);

		$this->assertEquals(array(
			TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext,
			TransparentAuthentication::Auth_HTTPBasicAuth_TOTP
		), ReflectionHelper::getValue($this->auth, 'authenticationMethods'));
	}

	public function testRemoveAuthenticationMethod()
	{
		$this->auth->setAuthenticationMethods(array(
			TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext,
			TransparentAuthentication::Auth_HTTPBasicAuth_TOTP,
		));

		// Try removing a non-existing method
		$this->auth->removeAuthenticationMethod(TransparentAuthentication::Auth_QueryString_Plaintext);

		$this->assertEquals(array(
			TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext,
			TransparentAuthentication::Auth_HTTPBasicAuth_TOTP,
		), ReflectionHelper::getValue($this->auth, 'authenticationMethods'));

		// Try removing an existing method
		$this->auth->removeAuthenticationMethod(TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext);

		$this->assertEquals(array(
			1 => TransparentAuthentication::Auth_HTTPBasicAuth_TOTP
		), ReflectionHelper::getValue($this->auth, 'authenticationMethods'));
	}

	/**
	 * @dataProvider GetTestGetTransparentAuthenticationCredentials
	 *
	 * @backupGlobals
	 */
	public function testGetTransparentAuthenticationCredentials($inputData, $serverGlobals, $shouldSucceed)
	{
		// Clear input data
		static::$container->input->setData(array());

		// Push input data if so defined
		if (!is_null($inputData))
		{
			static::$container->input->setData($inputData);
		}

		// Override server globals if necessary
		if (!is_null($serverGlobals))
		{
			foreach ($serverGlobals as $k => $v)
			{
				$_SERVER[$k] = $v;
			}
		}

		// Decode the transparent authentication information
		$result = $this->auth->getTransparentAuthenticationCredentials();

		if ($shouldSucceed)
		{
			$this->assertInternalType('array', $result);
			$this->assertArrayHasKey('username', $result);
			$this->assertArrayHasKey('password', $result);
			$this->assertEquals('awftest', $result['username']);
			$this->assertEquals('dummy', $result['password']);
		}
		else
		{
			$this->assertNull($result);
		}
	}

	public function GetTestGetTransparentAuthenticationCredentials()
	{
		// Let's do some TOTP encoding
		$totp = new Totp();
		$otp = $totp->getCode(static::$totpKey);
		$cryptoKey = hash('sha256', static::$totpKey . $otp);
		$aes = new Aes($cryptoKey);

		$plainText_right = json_encode(array('username' => 'awftest', 'password' => 'dummy'));
		$plainText_missingPassword = json_encode(array('username' => 'awftest'));
		$plainText_missingUsername = json_encode(array('password' => 'dummy'));
		$plainText_crap = 'crap_data';
		$encoded_right = $aes->encryptString($plainText_right);
		$encoded_missingPassword = $aes->encryptString($plainText_missingPassword);
		$encoded_missingUsername = $aes->encryptString($plainText_missingUsername);
		$encoded_crap = $aes->encryptString($plainText_crap);

		$otp = $totp->getCode(static::$totpKey, time() - 86400);
		$cryptoKey = hash('sha256', static::$totpKey . $otp);
		$aes = new Aes($cryptoKey);
		$encodedOutdated = $aes->encryptString($plainText_right);

		// Input data, server globals, do I expect correct username/password
		return array(
			// HTTP Basic Auth, plaintext
			array(
				null,
				array(
					'PHP_AUTH_USER' => 'awftest',
					'PHP_AUTH_PW'   => 'dummy',
				),
				true
			),
			// HTTP Basic Auth, missing username
			array(
				null,
				array(
					'PHP_AUTH_PW' => 'dummy',
				),
				false
			),
			// HTTP Basic Auth, missing password
			array(
				null,
				array(
					'PHP_AUTH_USER' => 'awftest',
				),
				false
			),

			// Query string, plaintext
			array(
				array(
					'testAuth' => json_encode(array(
						'username' => 'awftest',
						'password' => 'dummy'
					))
				),
				null,
				true
			),
			// Query string, missing username
			array(
				array(
					'testAuth' => json_encode(array(
						'password' => 'dummy'
					))
				),
				null,
				false
			),
			// Query string, missing password
			array(
				array(
					'testAuth' => json_encode(array(
						'username' => 'awftest',
					))
				),
				null,
				false
			),
			// Query string, crap string
			array(
				array(
					'testAuth' => 'stupid_string_is_no_good_json_data'
				),
				null,
				false
			),

			// Split query string
			array(
				array(
					'awfUsername'	=> 'awftest',
					'awfPassword'	=> 'dummy',
				),
				null,
				true
			),
			// Split query string, missing username
			array(
				array(
					'awfPassword'	=> 'dummy',
				),
				null,
				false
			),
			// Split query string, missing password
			array(
				array(
					'awfUsername'	=> 'awftest',
				),
				null,
				false
			),
			// Split query string, junk fed
			array(
				array(
					'junk'	=> 'food',
				),
				null,
				false
			),
			// HTTP Basic Auth, TOTP, correct
			array(
				null,
				array(
					'PHP_AUTH_USER'	=> 'awfuser',
					'PHP_AUTH_PW'	=> $encoded_right,
				),
				true
			),
			// HTTP Basic Auth, TOTP, missing username
			array(
				null,
				array(
					'PHP_AUTH_USER'	=> 'awfuser',
					'PHP_AUTH_PW'	=> $encoded_missingUsername,
				),
				false
			),
			// HTTP Basic Auth, TOTP, missing password
			array(
				null,
				array(
					'PHP_AUTH_USER'	=> 'awfuser',
					'PHP_AUTH_PW'	=> $encoded_missingPassword,
				),
				false
			),
			// HTTP Basic Auth, TOTP, crap encoded data
			array(
				null,
				array(
					'PHP_AUTH_USER'	=> 'awfuser',
					'PHP_AUTH_PW'	=> $encoded_crap,
				),
				false
			),
			// HTTP Basic Auth, TOTP, crap non-encoded data
			array(
				null,
				array(
					'PHP_AUTH_USER'	=> 'awfuser',
					'PHP_AUTH_PW'	=> 'this_is_crap_data',
				),
				false
			),
			// HTTP Basic Auth, TOTP, outdated but otherwise correctly encrypted data
			array(
				null,
				array(
					'PHP_AUTH_USER'	=> 'awfuser',
					'PHP_AUTH_PW'	=> $encodedOutdated,
				),
				false
			),

			// Query string, TOTP, correct
			array(
				array(
					'testAuth' => $encoded_right
				),
				null,
				true
			),
			// Query string, TOTP, missing username
			array(
				array(
					'testAuth' => $encoded_missingUsername
				),
				null,
				false
			),
			// Query string, TOTP, missing password
			array(
				null,
				array(
					'testAuth' => $encoded_missingPassword
				),
				false
			),
			// Query string, TOTP, crap encoded data
			array(
				null,
				array(
					'testAuth'	=> $encoded_crap,
				),
				false
			),
			// Query string, TOTP, crap non-encoded data
			array(
				array(
					'testAuth'	=> 'this_is_crap_data',
				),
				null,
				false
			),
			// Query string, TOTP, outdated but otherwise correctly encrypted data
			array(
				array(
					'testAuth'	=> $encodedOutdated,
				),
				null,
				false
			),
		);
	}

	protected function setUp()
	{
		$this->auth = new TransparentAuthentication(static::$container);
		$this->auth->setAuthenticationMethods(array(1,2,3,4,5));
		$this->auth->setBasicAuthUsername('awfuser');
		$this->auth->setQueryParam('testAuth');
		$this->auth->setQueryParamPassword('awfPassword');
		$this->auth->setQueryParamUsername('awfUsername');
		$this->auth->setTimeStep(30);
		$this->auth->setTotpKey(self::$totpKey);
	}
}