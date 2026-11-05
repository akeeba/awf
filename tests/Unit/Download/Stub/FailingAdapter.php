<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Download\Stub;

use Awf\Download\Adapter\AbstractAdapter;

/**
 * Download adapter which always fails the way the cURL adapter does on a connection error.
 */
class FailingAdapter extends AbstractAdapter
{
	public const MESSAGE = 'cURL library error #5: Could not resolve proxy: proxy.invalid';

	public function __construct()
	{
		$this->name        = 'failing';
		$this->priority    = 1;
		$this->isSupported = true;
	}

	public function downloadAndReturn($url, $from = null, $to = null, array $params = [])
	{
		throw new \Exception(self::MESSAGE, 5);
	}
}
