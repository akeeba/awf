<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html;

/**
 * Interface to an HTML helper class.
 *
 * @since 1.1.0
 */
interface HtmlHelperInterface
{
	/**
	 * Returns the name of the HTML helper class, i.e. its call prefix.
	 *
	 * @return  string
	 *
	 * @since   1.1.0
	 */
	public function getName(): string;
}