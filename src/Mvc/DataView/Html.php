<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataView;

class Html extends Raw
{
	/**
	 * Executes before rendering the page for the Add task.
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	protected function onBeforeAdd()
	{
		// Hide main menu
		$this->container->application->getDocument()->getMenu()->disableMenu('main');

		return true;
	}

	/**
	 * Executes before rendering the page for the Edit task.
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	protected function onBeforeEdit()
	{
		// Hide main menu
		$this->container->application->getDocument()->getMenu()->disableMenu('main');

		return true;
	}

	/**
	 * Executes before rendering the page for the Read task.
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	protected function onBeforeRead()
	{
		return true;
	}
} 
