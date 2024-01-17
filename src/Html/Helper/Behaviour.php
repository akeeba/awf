<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html\Helper;

use Awf\Html\AbstractHelper;
use Awf\Utils\Template;

/**
 * Javascript behaviours abstraction class
 *
 * This class is based on the JHtml package of Joomla! 3 but heavily modified
 *
 * @since 1.1.0
 */
class Behaviour extends AbstractHelper
{
	/**
	 * Array containing information for loaded files
	 *
	 * @var    array
	 */
	protected $loaded = [];

	/**
	 * Add unobtrusive JavaScript support for a calendar control.
	 *
	 * @return  void
	 */
	public function calendar(): void
	{
		// Only load once
		if (isset($this->loaded[__METHOD__]))
		{
			return;
		}

		$app = $this->container->application;

		Template::addJs('media://js/datepicker/bootstrap-datepicker.js', $app);
		Template::addCss('media://css/datepicker.css', $app);

		$this->loaded[__METHOD__] = true;
	}
} 
