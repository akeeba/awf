<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Session\Encoder;

/**
 * A session segment data encoder using Base64 against serialised data
 *
 * @since   1.1.2
 */
class Base64Encoder implements EncoderInterface
{

	public function isAvailable(): bool
	{
		return function_exists('base64_encode')
		       && function_exists('base64_decode');
	}

	public function encode(?array $raw)
	{
		if ($raw === null)
		{
			return null;
		}

		$serialised = base64_encode(serialize($raw));

		if ($serialised === false)
		{
			return null;
		}

		return $serialised;
	}

	public function decode($encoded): array
	{
		if (empty($encoded))
		{
			return [];
		}

		$decoded = @base64_decode($encoded);

		if ($decoded === false)
		{
			return [];
		}

		$unserialized = @unserialize($decoded);

		if (!is_array($unserialized))
		{
			return [];
		}

		return $unserialized;
	}
}