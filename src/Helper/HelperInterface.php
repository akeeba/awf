<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Helper;

interface HelperInterface
{
	/**
	 * Returns the prefix of the helper
	 *
	 * @return  string
	 *
	 * @since   1.1.0
	 */
	public function getName(): string;
}