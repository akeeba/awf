<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Tests\Stubs\Session;


use Awf\Session\Phpfunc;

class MockPhpfunc extends Phpfunc
{
	protected $extensions = [];

	public function __construct()
	{
		$this->setExtensions(get_loaded_extensions());
	}

	public function setExtensions(array $extensions)
	{
		$this->extensions = $extensions;
	}

	public function extension_loaded($name)
	{
		// for parent coverage
		$this->__call('extension_loaded', [$name]);

		// for testing
		return in_array($name, $this->extensions);
	}
} 