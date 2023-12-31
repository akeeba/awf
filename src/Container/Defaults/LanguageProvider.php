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

/**
 * Application default Language object service provider
 *
 * @since   1.2.0
 */
class LanguageProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  Language  The returned service object
	 * @since   1.2.0
	 */
	public function __invoke(Container $c): Language
	{
		Text::setContainer($c);

		$lang = new Language($c);

		$lang->loadLanguage();

		return $lang;
	}

}