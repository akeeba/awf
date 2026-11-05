<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Download\Stub;

use Awf\Download\Adapter\AbstractAdapter;

/**
 * Download adapter which always returns the same content.
 */
class SucceedingAdapter extends AbstractAdapter
{
	public const CONTENT = 'downloaded content';

	public function __construct()
	{
		$this->name        = 'succeeding';
		$this->priority    = 1;
		$this->isSupported = true;
	}

	public function downloadAndReturn($url, $from = null, $to = null, array $params = [])
	{
		return self::CONTENT;
	}
}
