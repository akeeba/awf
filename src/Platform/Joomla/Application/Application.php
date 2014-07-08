<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Application;


use Awf\Container\Container;
use Awf\Platform\Joomla\Application\Observer\ControllerAcl;
use Awf\Platform\Joomla\Application\Observer\ViewAlternatePaths;
use Awf\Platform\Joomla\Helper\Helper;
use Awf\Text\Text;

class Application extends \Awf\Application\Application
{
	/**
	 * Gets an instance of the application
	 *
	 * @param   string    $name      The name of the application (folder name)
	 * @param   Container $container The DI container to use for the instance (if the instance is not already set)
	 *
	 * @return  Application
	 *
	 * @throws  \Awf\Exception\App
	 */
	public static function getInstance($name = null, Container $container = null)
	{
		if (empty($name) && !empty(self::$instances))
		{
			$keys = array_keys(self::$instances);
			$name = array_shift($keys);
		}
		elseif (empty($name))
		{
			$name = $container->input->get('option', null);
		}

		$name = strtolower($name);

		if (!array_key_exists($name, self::$instances))
		{
			$className = '\\' . ucfirst($name) . '\\Application';

			if (!class_exists($className))
			{
				$filePath = (Helper::isBackend() ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/components/com_'
					. strtolower($name) . '/' . $name . '/application.php';
				$result = @include_once($filePath);

				if (!class_exists($className, false))
				{
					$className = 'Application';
				}

				if (!class_exists($className, false))
				{
					$result = false;
				}
			}
			else
			{
				$result = true;
			}

			if ($result === false)
			{
				throw new \Awf\Exception\App("The application '$name' was not found on this server");
			}

			self::$instances[$name] = new $className($container);
		}

		return self::$instances[$name];
	}

	public function initialise()
	{
		// Put a small marker to indicate that we run inside another CMS
		$this->container->segment->set('insideCMS', true);

		// Attach the Joomla!-specific observer for Controller ACL checks
		$this->container->eventDispatcher->attach(new ControllerAcl($this->container->eventDispatcher));

		// Attach the Joomla!-specific observer for template override support
		$this->container->eventDispatcher->attach(new ViewAlternatePaths($this->container->eventDispatcher));

		// @todo Set up the template (theme) to use – different for front-end and back-end
		$this->setTemplate('CUSTOMTEMPLATE');

		// Load the extra language files
		$appName = $this->container->application_name;
		$appNameLower = strtolower($appName);
		$languageTag = \JFactory::getLanguage()->getTag();
		Text::loadLanguage('en-GB', $appName, '.com_' . $appNameLower . '.ini', false, $this->container->languagePath);
		Text::loadLanguage($languageTag, $appName, '.com_' . $appNameLower . '.ini', true, $this->container->languagePath);
	}

} 