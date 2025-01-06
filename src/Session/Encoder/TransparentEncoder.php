<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Session\Encoder;

/**
 * A session segment data encoder which returns raw objects.
 *
 * This is the best choice when session.serialize_handler is set to php_serialize.
 *
 * @since   1.1.2
 */
class TransparentEncoder implements EncoderInterface
{
	public function isAvailable(): bool
	{
		return true;
	}

	public function encode(?array $raw)
	{
		return $raw;
	}

	public function decode($encoded): array
	{
		if (!is_array($encoded))
		{
			return [];
		}

		return $encoded;
	}
}