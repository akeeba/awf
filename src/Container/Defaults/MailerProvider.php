<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Mailer\Mailer;

/**
 * Mailer object factory service provider
 *
 * @since   1.1.0
 */
class MailerProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  callable  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): callable
	{
		return $c->protect(
			function () use ($c): Mailer {
				return new Mailer($c);
			}
		);
	}

}