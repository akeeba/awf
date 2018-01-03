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

use Awf\Tests\Helpers\Download\FakeFopen;

global $awfTest_FakeFopen_Active;
$awfTest_FakeFopen_Active = false;

function file_get_contents($url, $flags = null, $context = null, $offset = null, $maxlen = null)
{
	global $awfTest_FakeFopen_Active;

	if (!$awfTest_FakeFopen_Active)
	{
		return \file_get_contents($url, $flags, $context, $offset, $maxlen);
	}

	return FakeFopen::file_get_contents($url, $flags, $context, $offset, $maxlen);
}
