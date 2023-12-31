<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html;

use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;

/**
 * Abstract implementation of an HTML helper.
 *
 * @since 1.1.0
 */
abstract class AbstractHelper implements HtmlHelperInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	protected $name = '';

	/** @inheritDoc */
	public function getName(): string
	{
		if (empty($this->name))
		{
			$parts      = explode('\\', get_class($this));
			$this->name = strtolower(array_pop($parts));
		}

		return $this->name;
	}
}