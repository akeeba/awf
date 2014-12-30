<?php
/**
 * @package     Awf
 * @copyright   2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Mvc;

use Awf\Mvc\View;
use Awf\Text\Text;

/**
 * Class View
 *
 * A generic Joomla! back-end MVC view implementation
 *
 * @package Awf\Mvc
 */
class BackendView extends View
{
	/** @var  string  The back-end page title shown above the toolbar. */
	protected $pageTitle = null;

	/** @var  string  The component's name (com_something) */
	protected $componentName = null;

	/**
	 * Overrides the default method to execute and display a template script to add a back-end page title.
	 *
	 * @param   string $tpl The name of the template file to parse
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  \Exception  When the layout file is not found
	 */
	public function display($tpl = null)
	{
		// Set the component's name
		if (empty($this->componentName))
		{
			$this->componentName = $this->getComponentName();
		}

		// Set the page title
		if (empty($this->pageTitle))
		{
			$this->pageTitle = $this->getJoomlaPageTitle();
		}

		\JToolbarHelper::title($this->pageTitle);

		return parent::display($tpl);
	}

	/**
	 * Tries to figure out the page title to use in the Joomla! back-end
	 *
	 * @return string
	 */
	protected function getJoomlaPageTitle()
	{
		$appName = strtoupper($this->getContainer()->application_name);

		if (substr($appName, -5) == 'ADMIN')
		{
			$appName = substr($appName, 0, -5);
		}

		// e.g. FOOBAR_APP_TITLE
		$appTitle = $appName . '_APP_TITLE';

		// e.g. COM_FOOBAR
		$componentTitle = 'COM_' . $appName;

		// e.g. COM_FOOBAR_ITEMS_TITLE
		$viewTitle = $componentTitle . '_' . strtoupper($this->name) . '_TITLE';

		// e.g. COM_FOOBAR_ITEMS_BROWSE
		$taskTitle = substr($viewTitle, 0, -5) . strtoupper($this->doTask);

		// First try the task-specific title
		$title = Text::_($taskTitle);

		if ($title != $taskTitle)
		{
			return $title;
		}

		// Then try the view-specific title
		$title = Text::_($viewTitle);

		if ($title != $viewTitle)
		{
			return $title;
		}

		// Then try the component-specific title
		$title = Text::_($componentTitle);

		if ($title != $componentTitle)
		{
			return $title;
		}

		// Then try the app-specific title
		$title = Text::_($appTitle);

		if ($title != $appTitle)
		{
			return $title;
		}

		// If all else fails, try using the generic title...
		$title = Text::_($appName);

		return $title;
	}

	/**
	 * Try to guess the Joomla! component name for this application
	 *
	 * @return string
	 */
	protected function getComponentName()
	{
		$appName = strtolower($this->getContainer()->application_name);

		if (substr($appName, -5) == 'admin')
		{
			$appName = substr($appName, 0, -5);
		}

		return 'com_' . $appName;
	}
}