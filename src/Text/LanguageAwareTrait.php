<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Text;

trait LanguageAwareTrait
{
	private $languageObject;

	public function setLanguage(Language $language)
	{
		$this->languageObject = $language;
	}

	public function getLanguage(): Language
	{
		return $this->languageObject;
	}

}