<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Filesystem\Factory as FilesystemFactory;
use Awf\Filesystem\FilesystemInterface;

/**
 * Filesystem service provider
 *
 * @since   1.1.0
 */
class FilesystemProvider
{
	/**
	 * Should I create a hybrid access (FTP/SFTP + direct file access) filesystem object by default?
	 *
	 * @var   bool
	 * @since 1.1.0
	 */
	private $hybrid;

	/**
	 * Public constructor
	 *
	 * @param   bool  $hybrid  Should I create a hybrid access object by default?
	 *
	 * @since   1.1.0
	 */
	public function __construct(bool $hybrid = true)
	{
		$this->hybrid = $hybrid;
	}

	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  FilesystemInterface  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): FilesystemInterface
	{
		return FilesystemFactory::getAdapter($c, $this->hybrid);
	}

}