<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Application;


use Awf\Platform\Joomla\Application\Observer\ControllerAcl;
use Awf\Platform\Joomla\Application\Observer\ViewAlternatePaths;
use Awf\Text\Text;

class Application extends \Awf\Application\Application
{
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
		Text::loadLanguage('en-GB', $appName, '.com_' . $appNameLower . '.ini', false, $this->container->languagePath);
		Text::loadLanguage(null, $appName, '.com_' . $appNameLower . '.ini', true, $this->container->languagePath);
	}

} 