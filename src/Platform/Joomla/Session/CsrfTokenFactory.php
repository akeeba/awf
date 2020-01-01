<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Session;

use Awf\Session\RandvalInterface;
use Awf\Session\Manager as SessionManager;

/**
 *
 * A factory to create CSRF token objects.
 */
class CsrfTokenFactory extends \Awf\Session\CsrfTokenFactory
{
	/**
	 *
	 * A cryptographically-secure random value generator.
	 *
	 * @var RandvalInterface
	 *
	 */
	protected $randval;

	/**
	 *
	 * Constructor.
	 *
	 * @param RandvalInterface $randval A cryptographically-secure random
	 *                                  value generator. IGNORED IN JOOMLA!.
	 *
	 */
	public function __construct(RandvalInterface $randval = null)
	{

	}

	/**
	 *
	 * Creates a CsrfToken object.
	 *
	 * @param Manager $manager The session manager. IGNORED
	 *
	 * @return CsrfToken
	 *
	 */
	public function newInstance(SessionManager $manager = null)
	{
		return new CsrfToken();
	}
}
