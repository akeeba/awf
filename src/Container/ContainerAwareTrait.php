<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container;

/**
 * Interface for objects which are aware of the existence of the Container.
 *
 * @since 1.1.0
 */
trait ContainerAwareTrait
{
	/**
	 * The instance of the application container
	 *
	 * @var   Container
	 * @since 1.1.0
	 */
	protected $container;

	/**
	 * Set the container instance to the object
	 *
	 * @param   Container  $container  The container instance
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function setContainer(Container $container): void
	{
		$this->container = $container;
	}

	public function getContainer(): Container
	{
		return $this->container;
	}
}