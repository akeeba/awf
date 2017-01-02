<?php
/**
 * @package        awf
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Application;


use Awf\Utils\Phpfunc;

class MockPhpfuncConfig extends Phpfunc
{
	public function file_get_contents($filename, $flags = null, $context = null, $offset = null, $maxlen = null)
	{
		if ($filename == '/dev/false')
		{
			return false;
		}
		elseif ($filename == '/dev/fake')
		{
			return "some line\n" . '{"foo": "bar"}';
		}
		elseif ($filename == '/dev/trash')
		{
			return "This is not the configuration content you are looking for";
		}
		elseif ($filename == '/dev/invalid')
		{
			return "some line\nThis is not the configuration content you are looking for";
		}

		return file_get_contents($filename, $flags = null, $context = null, $offset = null, $maxlen = null);
	}
} 