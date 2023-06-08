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
 *
 * A factory to create CSRF token objects.
 */
class CsrfTokenFactory
{
	private $algorithm = 'sha512';

	public function __construct($algorithm = 'sha512')
	{
		$this->algorithm = $algorithm;
	}

	/**
	 *
	 * Creates a CsrfToken object.
	 *
	 * @param Manager $manager The session manager.
	 *
	 * @return CsrfToken
	 *
	 */
	public function newInstance(Manager $manager)
	{
		$segment = $manager->newSegment('Awf\Session\CsrfToken');

		return new CsrfToken($segment, $this->algorithm);
	}

	public function setAlgorithm(string $algorithm): void
	{
		$this->algorithm = $algorithm;
	}
}
