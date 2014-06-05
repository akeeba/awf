<?php
/**
 * @package		awf
 * @subpackage  tests
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 *
 * Bootstrap file for unit testing
 */

// Include the AWF autoloader. Tip: You can put it outside your site's root as well, just edit the path.
if (false == include __DIR__ . '/../Awf/Autoloader/Autoloader.php')
{
	echo 'ERROR: AWF Autoloader not found' . PHP_EOL;

	exit(1);
}

// Tell the AWF autoloader where to load test classes from (very useful for stubs!)
\Awf\Autoloader\Autoloader::getInstance()->addMap('Tests\\', __DIR__);

// This is necessary for the session testing
ini_set('session.use_only_cookies', false);
ini_set('session.use_cookies', false);
ini_set('session.use_trans_sid', false);
ini_set('session.cache_limiter', null);
