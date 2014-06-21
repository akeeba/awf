<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Session;


use Awf\Utils\Phpfunc;

class MockPhpfunc extends Phpfunc
{
	protected $extensions = array();

	protected $functions_enabled = null;

	protected $mcrypt_algorithms = null;

	protected $hash_algorithms = null;

	public function __construct()
	{
		$this->setExtensions(get_loaded_extensions());
	}

	public function setExtensions(array $extensions)
	{
		$this->extensions = $extensions;
	}

	public function setFunctions($functions)
	{
		$this->functions_enabled = $functions;
	}

	public function setMcryptAlgorithms($algos)
	{
		$this->mcrypt_algorithms = $algos;
	}

	public function setHashAlgorithms($algos)
	{
		$this->hash_algorithms = $algos;
	}

	public function extension_loaded($name)
	{
		// for parent coverage
		$this->__call('extension_loaded', array($name));

		// for testing
		return in_array($name, $this->extensions);
	}

	public function function_exists($name)
	{
		// for parent coverage
		$result = $this->__call('function_exists', array($name));

		if (is_null($this->functions_enabled))
		{
			return $result;
		}

		// for testing
		return in_array($name, $this->functions_enabled);
	}

	public function mcrypt_list_algorithms()
	{
		// for parent coverage
		$result = $this->__call('mcrypt_list_algorithms', array());

		if (is_null($this->mcrypt_algorithms))
		{
			return $result;
		}

		// for testing
		return $this->mcrypt_algorithms;
	}

	public function hash_algos()
	{
		// for parent coverage
		$result = $this->__call('hash_algos', array());

		if (is_null($this->hash_algorithms))
		{
			return $result;
		}

		// for testing
		return $this->hash_algorithms;
	}
} 