<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Text\Language;
use Awf\Text\Text;
use Awf\User\UserInterface;

/**
 * Language Factory service provider
 *
 * @since   1.2.0
 */
class LanguageFactoryProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  callable  The returned service object
	 * @since   1.2.0
	 */
	public function __invoke(Container $c): callable
	{
		return $c->protect(
			function (?string $langCode = null, ?UserInterface $user = null, $callbacks = []) use ($c): Language {
				Text::setContainer($c);

				$lang     = new Language($c);
				$langCode = $langCode ?? $lang->detectLanguage(null, $user);

				$lang->loadLanguage($langCode, null, true, true, $callbacks);

				return $lang;
			}
		);
	}

}