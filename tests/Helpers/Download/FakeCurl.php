<?php
/**
 * @package     FOF
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace Awf\Tests\Helpers\Download;

/**
 * A helper class to simulate downloads through cURL. This solution includes tow parts. Part one is this class, part two
 * is the FakeCurlImporter.php file. You need to include the FakeCurlImporter.php file.
 */
class FakeCurl extends FakeBase
{
	/** @var int How many bytes we'll return for the entire download */
	public static $returnSize = 1048576;

	/** @var int The reported size of the file */
	public static $reportedSize = 1048576;

	/** @var string The URL we'll handle */
	public static $url = 'http://www.example.com/donwload.dat';

	/** @var int cURL error number simulation */
	public static $errno = 0;

	/** @var int cURL error string simulation */
	public static $error = '';

	/** @var int Simulated HTTP status */
	public static $httpstatus = 200;

	/** @var bool Can I handle this request? If false I'll return a 404 Not Found */
	private $canHandleRequest = false;

	/** @var int Byte range: from */
	private $from = 0;

	/** @var int Byte range: to */
	private $to = 0;

	/** @var bool Should I only return headers? */
	private $header = false;

	/**
	 * Reset the FakeCurl simulation values
	 */
	public static function reset()
	{
		static::$returnSize = 1048576;
		static::$reportedSize = 1048576;
		static::$url = 'http://www.example.com/donwload.dat';
		static::$errno = 0;
		static::$error = '';
		static::$httpstatus = 200;
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
					static::$returnSize = $v;
					break;

				case 'reportedSize':
					static::$reportedSize = $v;
					break;

				case 'url':
					static::$url = $v;
					break;

				case 'errno':
					static::$errno = $v;
					break;

				case 'error':
					static::$error = $v;
					break;

				case 'httpstatus':
					static::$httpstatus = $v;
					break;
			}
		}
	}

	/**
	 * Handle curl_setopt
	 *
	 * @param int   $optname
	 * @param mixed $value
	 */
	public function setopt($optname, $value)
	{
		switch ($optname)
		{
			case CURLOPT_URL:
				$this->canHandleRequest = $value == self::$url;

				if (!$this->canHandleRequest)
				{
					self::$httpstatus = 404;
				}
				break;

			case CURLOPT_RANGE:
				list($from, $to) = explode('-', $value);
				$this->from = $from;
				$this->to = $to;
				break;

			case CURLOPT_HEADER:
				$this->header = true;
				break;
		}
	}

	public function exec()
	{
		if (self::$errno > 0)
		{
			return false;
		}

		if ((self::$httpstatus != 200))
		{
			return '';
		}

		$buffer = '';

		if ($this->header)
		{
			$reportedSize = self::$reportedSize;
			return <<< HTTPRESPONSE
HTTP/1.1 200 OK
Content-Length: $reportedSize

HTTPRESPONSE;
		}

		if (empty($this->from) && empty($this->to))
		{
			return self::returnBytes(self::$returnSize);
		}

		$from = $this->from;
		$to = $this->to;

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

	/**
	 * Handle curl_errno
	 *
	 * @return int
	 */
	public function errno()
	{
		return self::$errno;
	}

	/**
	 * Handle curl_error
	 *
	 * @return int
	 */
	public function error()
	{
		return self::$error;
	}

	/**
	 * Handle curl_getinfo. Currently only handles CURLINFO_HTTP_CODE
	 *
	 * @param $opt
	 *
	 * @return int
	 */
	public function getinfo($opt)
	{
		if ($opt == CURLINFO_HTTP_CODE)
		{
			return self::$httpstatus;
		}
	}
}
