<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Session\Encoder;

use Awf\Encrypt\Base32;

/**
 * A session segment data encoder using Base32 against serialised data
 *
 * @since   1.1.2
 */
class Base32Encoder implements EncoderInterface
{

	public function isAvailable(): bool
	{
		return class_exists(Base32::class);
	}

	public function encode(?array $raw)
	{
		if ($raw === null)
		{
			return null;
		}

		$base32 = new Base32();

		try
		{
			return $base32->encode(serialize($raw));
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	public function decode($encoded): array
	{
		if (empty($encoded))
		{
			return [];
		}

		$base32 = new Base32();

		try
		{
			$decoded = @$base32->decode($encoded);
		}
		catch (\Exception $e)
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