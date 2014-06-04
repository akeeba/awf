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

\Awf\Autoloader\Autoloader::getInstance()->addMap('Tests\\', __DIR__);
