<?php
/**
 * @package		awf
 * @subpackage  tests
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 *
 * Bootstrap file for unit testing
 */

// Include the AWF autoloader.
if (false == include __DIR__ . '/../src/Autoloader/Autoloader.php')
{
	echo 'ERROR: AWF Autoloader not found' . PHP_EOL;

	exit(1);
}

// Tell the AWF autoloader where to load test classes from (very useful for stubs!)
\Awf\Autoloader\Autoloader::getInstance()->addMap('Awf\\Tests\\', __DIR__);
\Awf\Autoloader\Autoloader::getInstance()->addMap('Fakeapp\\', __DIR__ . '/Stubs/Fakeapp');

// Include the Composer autoloader.
if (false == include __DIR__ . '/../vendor/autoload.php')
{
	echo 'ERROR: Composer Autoloader not found' . PHP_EOL;

	exit(1);
}

// Don't report strict errors. This is needed because sometimes a test complains about arguments passed as reference
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

// This is necessary for the session testing
ini_set('session.use_only_cookies', false);
ini_set('session.use_cookies', false);
ini_set('session.use_trans_sid', false);
ini_set('session.cache_limiter', null);
