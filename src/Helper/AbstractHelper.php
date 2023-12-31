<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Helper;

use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;

class AbstractHelper implements HelperInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	protected $helperPrefix = '';

	/** @inheritDoc */
	public function getName(): string
	{
		if (empty($this->helperPrefix))
		{
			$parts              = explode('\\', get_class($this));
			$this->helperPrefix = strtolower(array_pop($parts));
		}

		return $this->helperPrefix;
	}
}