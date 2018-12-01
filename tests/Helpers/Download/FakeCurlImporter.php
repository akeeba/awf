<?php
/**
 * @package     FOF
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 *
 * This file overrides certain core cURL functions inside the FOF30\Download\Adapter namespace. Because of the strange
 * way PHP handles calls to functions, the functions specified in this namespace override the core functions which are
 * implicitly defined in the global namespace. Therefore when the FOF30\Download\Adapter\Curl adapter calls, say,
 * curl_init PHP will execute FOF30\Download\Adapter\curl_init instead of the core, global curl_init function. This
 * allows us to mock libcurl for testing.
 */

namespace Awf\Download\Adapter;

use Awf\Tests\Helpers\Download\FakeCurl;

global $awfTest_FakeCurl_Active;
$awfTest_FakeCurl_Active = false;

function curl_init()
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_init();
	}

	return new FakeCurl();
}

function curl_setopt($ch, $optname, $value)
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_setopt($ch, $optname, $value);
	}

	$ch->setopt($optname, $value);
}

function curl_exec($ch)
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_exec($ch);
	}

	return $ch->exec();
}

function curl_errno($ch)
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_errno($ch);
	}

	return $ch->errno();
}

function curl_error($ch)
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_error($ch);
	}

	return $ch->error();
}

function curl_getinfo($ch, $opt)
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_getinfo($ch, $opt);
	}

	return $ch->getinfo($opt);
}

function curl_close(&$ch)
{
	global $awfTest_FakeCurl_Active;

	if (!$awfTest_FakeCurl_Active)
	{
		return \curl_close($ch);
	}

	$ch = null;
}
