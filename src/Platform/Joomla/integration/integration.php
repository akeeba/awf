<?php
// Bootstrap file for Joomla!
use Awf\Session;

/**
 * Make sure we are being called from Joomla!
 */
defined('_JEXEC') or die;

// Makes sure we have PHP 5.3.4 or later
if (version_compare(PHP_VERSION, '5.3.4', 'lt'))
{
	echo sprintf('This component requires PHP 5.3.4 or later but your server only has PHP %s.', PHP_VERSION);
}

// Include the autoloader
if (false == include_once JPATH_LIBRARIES . '/awf/Autoloader/Autoloader.php')
{
	echo 'ERROR: Autoloader not found' . PHP_EOL;

	exit(1);
}

// Add our app to the autoloader, if it's not already set
$prefixes = Awf\Autoloader\Autoloader::getInstance()->getPrefixes();
if (!array_key_exists($appName . '\\', $prefixes))
{
	\Awf\Autoloader\Autoloader::getInstance()
		->addMap($appName . '\\', JPATH_SITE . '/components/com_' . strtolower($appName))
		->addMap($appName . 'Admin\\', JPATH_ADMINISTRATOR . '/components/com_' . strtolower($appName))
		->addMap($appName . '\\', JPATH_SITE . '/components/com_' . strtolower($appName) . '/' . $appName)
		->addMap($appName . 'Admin\\', JPATH_ADMINISTRATOR . '/components/com_' . strtolower($appName) . '/' . $appName);
}

$appName = \Awf\Platform\Joomla\Helper\Helper::isBackend() ? ($appName . 'Admin') : $appName;
$containerClass = "\\$appName\\Container\\Container";

if (!class_exists($containerClass, true))
{
	$containerClass = '\Awf\Platform\Joomla\Container\Container';
}

if (!isset($containerOverrides))
{
	$containerOverrides = array();
}

if (!isset($containerOverrides['application_name']))
{
	$containerOverrides['application_name'] = $appName;
}

try
{
	$container = new $containerClass($containerOverrides);
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

unset($prefixes);
unset($appName);
unset($containerClass);
unset($containerOverrides);