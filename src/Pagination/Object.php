<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Pagination;

/**
 * This is a backwards compatibility class. It should not be used in PHP 7.2 and later.
 */
class Object extends PaginationObject
{
	public function __construct($text, $base = null, $link = null, $active = false)
	{
		trigger_error(sprintf("Using %s is deprecated and will cause fatal errors in PHP 7.2 and later. Update your code.", __CLASS__), E_USER_NOTICE);

		parent::__construct($text, $base, $link, $active);
	}

} 
