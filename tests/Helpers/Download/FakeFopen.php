<?php
/**
 * @package     FOF
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace Awf\Tests\Helpers\Download;

/**
 * A helper class to simulate downloads through fopen URL wrappers. This solution includes tow parts. Part one is this
 * class, part two is the FakeFopenImporter.php file. You need to include the FakeFopenImporter.php file.
 */
class FakeFopen extends FakeBase
{
	/** @var int How many bytes we'll return for the entire download */
	public static $returnSize = 1048576;

	/** @var string The URL we'll handle */
	public static $url = 'http://www.example.com/donwload.dat';

	/** @var int Simulated HTTP status */
	public static $httpstatus = 200;

	/** @var bool  Should I set the global $http_response_header variable? */
	public static $setHeaders = true;

	/**
	 * Reset the FakeCurl simulation values
	 */
	public static function reset()
	{
		static::$returnSize = 1048576;
		static::$url = 'http://www.example.com/donwload.dat';
		static::$httpstatus = 200;
		static::$setHeaders = true;
	}

	/**
	 * Apply a configuration array
	 *
	 * @param  array  $configuration
	 */
	public static function setUp(array $configuration)
	{
		self::reset();

		foreach ($configuration as $k => $v)
		{
			switch ($k)
			{
				case 'returnSize':
					static::$returnSize = (int) $v;
					break;

				case 'url':
					static::$url = $v;
					break;

				case 'httpstatus':
					static::$httpstatus = (int) $v;
					break;

				case 'setHeaders':
					static::$setHeaders = (bool) $v;
					break;
			}
		}
	}

	public static function file_get_contents($url, $flags = null, $context = null, $offset = null, $maxlen = null)
	{
		// Not a URL? Do not handle.
		if (strpos($url, '://') === false)
		{
			return file_get_contents($url, $flags, $context, $offset, $maxlen);
		}

		if ($url != static::$url)
		{
			self::$httpstatus = 404;
		}


		global $http_response_header_test;

		if (self::$setHeaders && (self::$httpstatus != 404))
		{
			$http_response_header_test = array('HTTP/1.1 ' . self::$httpstatus);
		}
		else
		{
			$http_response_header_test = null;
		}

		if ((self::$httpstatus != 200))
		{
			return '';
		}

		// Get the from/to from the context
		$from = 0;
		$to = 0;

		if (is_resource($context))
		{
			$contextOptions = stream_context_get_options($context);

			if (isset($contextOptions['http']) && isset($contextOptions['http']['header']))
			{
				$headers = $contextOptions['http']['header'];
				$headers = explode("\r\n", $headers);

				foreach ($headers as $line)
				{
					if (substr($line, 0, 13) != 'Range: bytes=')
					{
						continue;
					}

					$line = substr($line, 13);
					$line = trim($line, "\r\n=");
					list($from, $to) = explode('-', $line);
				}
			}
		}

		if (empty($from) && empty($to))
		{
			return self::returnBytes(self::$returnSize);
		}

		$buffer = '';

		if (empty($from))
		{
			$from = 0;
		}

		if ($from > (self::$returnSize - 1))
		{
			return '';
		}

		if ($from < 0)
		{
			$from = 0;
		}

		if (empty($to))
		{
			$to = self::$returnSize - 1;
		}

		if ($to > (self::$returnSize - 1))
		{
			$to = self::$returnSize - 1;
		}

		$bytes = 1 + $to - $from;

		return self::returnBytes($bytes);
	}
}
