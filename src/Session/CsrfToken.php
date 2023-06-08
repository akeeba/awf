<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

/**
 * The Session package in Awf is based on the Session package in Aura for PHP. Please consult the LICENSE file in the
 * Awf\Session package for copyright and license information.
 */

namespace Awf\Session;

/**
 * Cross-site request forgery token tools.
 */
class CsrfToken
{
	/**
	 *
	 * Session segment for values in this class.
	 *
	 * @var Segment
	 *
	 */
	protected $segment;

	protected $algorithm;

	/**
	 *
	 * Constructor.
	 *
	 * @param   Segment  $segment  A segment for values in this class.
	 *
	 */
	public function __construct(Segment $segment, string $algorithm = 'sha512')
	{
		$this->segment   = $segment;

		$this->setAlgorithm($algorithm);

		if (!isset($this->segment->value))
		{
			$this->regenerateValue();
		}
	}

	private function setAlgorithm(string $algorithm)
	{
		$acceptableAlgorithms = [
			'md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512/224', 'sha512/256', 'sha512', 'sha3-224', 'sha3-256',
			'sha3-512'
		];

		if (!in_array($algorithm, $acceptableAlgorithms))
		{
			$algorithm = 'sha512';
		}

		$hash_algos = hash_algos();

		if (in_array($algorithm, $hash_algos))
		{
			$this->algorithm = $algorithm;

			return;
		}

		$acceptableAlgorithms = array_intersect($hash_algos, $acceptableAlgorithms);

		if (in_array('sha512', $acceptableAlgorithms))
		{
			$this->algorithm = 'sha512';
		}
		elseif (in_array('sha384', $acceptableAlgorithms))
		{
			$this->algorithm = 'sha384';
		}
		elseif (in_array('sha256', $acceptableAlgorithms))
		{
			$this->algorithm = 'sha256';
		}
		elseif (in_array('sha1', $acceptableAlgorithms))
		{
			$this->algorithm = 'sha1';
		}
		elseif (in_array('md5', $acceptableAlgorithms))
		{
			$this->algorithm = 'md5';
		}
		elseif(!empty($acceptableAlgorithms))
		{
			$this->algorithm = array_pop($acceptableAlgorithms);
		}

		// If we are here your PHP installation is unusable
		throw new \RuntimeException('This PHP installation supports neither any SHA family hashing algorithms, nor MD5. Refusing to proceed as session security cannot be guaranteed.');
	}

	/**
	 *
	 * Regenerates the value of the outgoing CSRF token.
	 *
	 * @return void
	 *
	 */
	public function regenerateValue()
	{
		$this->segment->value = hash($this->algorithm, random_bytes(64));
	}

	/**
	 *
	 * Checks whether an incoming CSRF token value is valid.
	 *
	 * @param   string  $value  The incoming token value.
	 *
	 * @return bool True if valid, false if not.
	 *
	 */
	public function isValid($value)
	{
		return $value === $this->getValue();
	}

	/**
	 *
	 * Gets the value of the outgoing CSRF token.
	 *
	 * @return string
	 *
	 */
	public function getValue()
	{
		return $this->segment->value;
	}
}
