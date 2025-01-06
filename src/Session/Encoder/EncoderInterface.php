<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Session\Encoder;

/**
 * This interface represents an encoder that is used to encode and decode session data.
 *
 * The segment stores data in an internal object. The object is _encoded_ before being written to the $_SESSION
 * superglobal, and _decoded_ after being read from the $_SESSION superglobal.
 *
 * Encoders, classes which implement this EncoderInterface, handle this encoding and decoding.
 *
 * @since  1.1.2
 */
interface EncoderInterface
{
	/**
	 * Checks if this encoder is available under the current PHP environment configuration.
	 *
	 * @return  bool  True if the resource is available, false otherwise.
	 * @since   1.1.2
	 */
	public function isAvailable(): bool;

	/**
	 * Encodes the given value using a specific encoding algorithm.
	 *
	 * @param   array|null  $raw  The raw string to be encoded.
	 *
	 * @return  string|array|null  Something suitable to be written to the session storage
	 * @since   1.1.2
	 */
	public function encode(?array $raw);

	/**
	 * Decodes the given value using a specific encoding algorithm.
	 *
	 * @param   string|array|null  $encoded  The encoded string to be decoded.
	 *
	 * @return  array  The raw value which will be handled internally by the segment
	 * @since   1.1.2
	 */
	public function decode($encoded): array;
}