<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Session;

use Awf\Session\Manager as SessionManager;

/**
 *
 * A factory to create CSRF token objects.
 */
class CsrfTokenFactory extends \Awf\Session\CsrfTokenFactory
{
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
