<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html;

use Awf\Application\Application;
use Awf\Exception\App;
use Awf\Uri\Uri;
use Awf\Utils\Template;

/**
 * Javascript behaviours abstraction class
 *
 * This class is based on the JHtml package of Joomla! 3 but heavily modified
 */
abstract class Behaviour
{
	/**
	 * Array containing information for loaded files
	 *
	 * @var    array
	 */
	protected static $loaded = array();

	/**
	 * Add unobtrusive JavaScript support for a calendar control.
	 *
	 * @param   Application|null  $app  CSS and JS will be added to the document of the selected application
	 *
	 * @return  void
	 * @throws  App
	 */
	public static function calendar(?Application $app = null)
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		$app = $app ?? Application::getInstance();

		Template::addJs('media://js/datepicker/bootstrap-datepicker.js', $app);
		Template::addCss('media://css/datepicker.css', $app);

		static::$loaded[__METHOD__] = true;
	}
} 
