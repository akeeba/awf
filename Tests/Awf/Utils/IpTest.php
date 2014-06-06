<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 6/6/2014
 * Time: 1:20 μμ
 */

namespace Tests\Awf\Utils;

use Awf\Utils\Ip;

class IpTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param bool   $useSuperGlobal
	 * @param string $remoteAddr
	 * @param string $clientIp
	 * @param string $forwardedFor
	 * @param string $expected
	 * @param string $message
	 *
	 * @backupGlobals
	 *
	 * @covers ::getUserIP
	 * @covers ::_real_getUserIP
	 *
	 * @dataProvider getTestReal_getUserIP
	 */
	public function testReal_getUserIP($useSuperGlobal, $remoteAddr, $clientIp, $forwardedFor, $expected, $message)
	{
		global $_SERVER;

		if (!$useSuperGlobal)
		{
			unset($_SERVER);

			putenv("REMOTE_ADDR=$remoteAddr");
			putenv("HTTP_CLIENT_IP=$clientIp");
			putenv("HTTP_X_FORWARDED_FOR=$forwardedFor");
		}
		else
		{
			$_SERVER['REMOTE_ADDR'] = $remoteAddr;

			if (empty($clientIp) && isset($_SERVER['HTTP_CLIENT_IP']))
			{
				unset($_SERVER['HTTP_CLIENT_IP']);
			}
			else
			{
				$_SERVER['HTTP_CLIENT_IP'] = $clientIp;
			}

			if (empty($forwardedFor) && isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				unset($_SERVER['HTTP_X_FORWARDED_FOR']);
			}
			else
			{
				$_SERVER['HTTP_X_FORWARDED_FOR'] = $forwardedFor;
			}
		}

		$this->assertEquals(
			$expected,
			Ip::getUserIP(),
			$message
		);
	}

	/**
	 * @param bool   $useSuperGlobal
	 * @param string $remoteAddr
	 * @param string $clientIp
	 * @param string $forwardedFor
	 * @param string $expected
	 * @param string $message
	 *
	 * @backupGlobals
	 *
	 * @covers ::getUserIP
	 * @covers ::_real_getUserIP
	 *
	 * @dataProvider getTestReal_getUserIP
	 */
	public function testWorkaroundIpIssues($useSuperGlobal, $remoteAddr, $clientIp, $forwardedFor, $expected, $message)
	{
		global $_SERVER;

		$this->testReal_getUserIP($useSuperGlobal, $remoteAddr, $clientIp, $forwardedFor, $expected, $message);

		Ip::workaroundIPIssues();

		if ($useSuperGlobal)
		{
			$actual = $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			$actual = getenv('REMOTE_ADDR');
		}

		$this->assertEquals(
			$expected,
			$actual
		);
	}

	public function getTestReal_getUserIP()
	{
		// useSuperGlobal, remoteAddr, clientIp, forwardedFor, expected, message
		return array(
			array(true, '1.2.3.4', '', '', '1.2.3.4', 'Server: remote address'),
			array(true, '1.2.3.4', '1.2.3.5', '', '1.2.3.5', 'Server: remote & client'),
			array(true, '1.2.3.4', '1.2.3.5', '1.2.3.6', '1.2.3.6', 'Server: remote, client & forwarded'),
			array(true, '', '1.2.3.5', '1.2.3.6', '1.2.3.6', 'Server: client & forwarded'),
			array(true, '', '', '1.2.3.6', '1.2.3.6', 'Server: forwarded'),
			array(true, '', '1.2.3.5', '', '1.2.3.5', 'Server: client'),

			array(true, '1.2.3.4,192.168.1.1', '', '', '192.168.1.1', 'Server: remote address list'),

			array(false, '1.2.3.4', '', '', '1.2.3.4', 'Env: remote address'),
			array(false, '1.2.3.4', '1.2.3.5', '', '1.2.3.5', 'Env: remote & client'),
			array(false, '1.2.3.4', '1.2.3.5', '1.2.3.6', '1.2.3.6', 'Env: remote, client & forwarded'),
			array(false, '', '1.2.3.5', '1.2.3.6', '1.2.3.6', 'Env: client & forwarded'),
			array(false, '', '', '1.2.3.6', '1.2.3.6', 'Env: forwarded'),
			array(false, '', '1.2.3.5', '', '1.2.3.5', 'Env: client'),

			array(false, '1.2.3.4,192.168.1.1', '', '', '192.168.1.1', 'Env: remote address list'),
		);
	}
}
 