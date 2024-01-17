<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Application;

use Awf\Container\Container;
use Awf\Exception\App as AppException;

class ApplicationServiceProvider
{
	public function __invoke(Container $container)
	{
		$classNames = [
			$container->applicationNamespace . '\\Application',
			ucfirst($container->application_name) . '\\Application',
			ucfirst(strtolower($container->application_name)) . '\\Application'
		];

		$className = array_reduce(
			$classNames,
			function (?string $carry, string $className) {
				return $carry ?? (class_exists($className,true) ? $className : null);
			},
			null
		);

		if ($className === null) {
			throw new AppException("The application '{$container->application_name}' was not found on this server");
		}

		$appObject = new $className($container);

		// Temporary workaround for Application::getInstance() compatibility. This will be removed in 2.0.
		Application::setInstance($container->application_name, $appObject);

		return $appObject;
	}
}