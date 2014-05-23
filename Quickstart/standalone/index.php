<?php
/**
 * @package     Quickstart.Standalone
 * @copyright   2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

use Awf\Autoloader\Autoloader;
use Awf\Session;

// Makes sure you have PHP 5.3.4 or later. Earlier versions are not supported by AWF.
if (version_compare(PHP_VERSION, '5.3.4', 'lt'))
{
	die(sprintf('This application requires PHP 5.3.4 or later but your server only has PHP %s.', PHP_VERSION));
}

// Include the AWF autoloader. Tip: You can put it outside your site's root as well, just edit the path.
if (false == include __DIR__ . '/Awf/Autoloader/Autoloader.php')
{
	echo 'ERROR: AWF Autoloader not found' . PHP_EOL;

	exit(1);
}

// If you plan on using your application inside WordPress or Joomla! you need the following integration block. If it is
// a standalone application only, you can remove this.
// ---------- INTEGRATION BLOCK -- START ----------
// Load the integration script
$dirParts = isset($_SERVER['SCRIPT_FILENAME']) ? explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']) : array();

if (count($dirParts) > 3)
{
	$dirParts = array_splice($dirParts, 0, -2);
	$myDir = implode(DIRECTORY_SEPARATOR, $dirParts);
}

if (@file_exists(__DIR__ . '/../../helpers/integration.php'))
{
	require_once __DIR__ . '/../../helpers/integration.php';
}
elseif (@file_exists('../../helpers/integration.php'))
{
	require_once '../../helpers/integration.php';
}
elseif (@file_exists($myDir . '/helpers/integration.php'))
{
	require_once $myDir . '/helpers/integration.php';
}
// ---------- INTEGRATION BLOCK -- END ----------

// Load the platform defines
if (!defined('APATH_BASE'))
{
	require_once __DIR__ . '/defines.php';
}

// Add your app to the autoloader, if it's not already set. We suppose your app is called Example.
// Tip: You can store your app outside your web root, just change the path below.
$prefixes = Autoloader::getInstance()->getPrefixes();
if (!array_key_exists('Example\\', $prefixes))
{
	Autoloader::getInstance()->addMap('Example\\', APATH_BASE . '/Example');
}

try
{
	// Create the container if it doesn't already exist
	if (!isset($container))
	{
		$container = new \Awf\Container\Container(array(
			'application_name'	=> 'Example'
		));
	}

	// Create the application
	$application = $container->application;

	// Initialise the application
	$application->initialise();

	// Route the URL: parses the URL through routing rules, replacing the data in the app's input
	$application->route();

	// Dispatch the application
	$application->dispatch();

	// Render the output
	$application->render();

	// Clean-up and shut down
	$application->close();
}
catch (Exception $exc)
{
	$filename = null;

	if (isset($application))
	{
		if ($application instanceof \Awf\Application\Application)
		{
			$template = $application->getTemplate();

			if (file_exists(APATH_THEMES . '/' . $template . '/error.php'))
			{
				$filename = APATH_THEMES . '/' . $template . '/error.php';
			}
		}
	}

	if (is_null($filename))
	{
		die($exc->getMessage());
	}

	include $filename;
}