<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Session;

/**
 * Cross-site request forgery token tools
 */
class CsrfToken extends \Awf\Session\CsrfToken
{
	public function __construct()
	{
	}

	/**
	 *
	 * Checks whether an incoming CSRF token value is valid.
	 *
	 * @param string $value The incoming token value.
	 *
	 * @return bool True if valid, false if not.
	 *
	 */
	public function isValid($value)
	{
		$token = \JFactory::getSession()->getToken();
		$formToken = \JFactory::getSession()->getFormToken();

		return ($value == $token) || ($value == $formToken);
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
		return \JFactory::getSession()->getFormToken();
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
		\JFactory::getSession()->getFormToken(true);
		\JFactory::getSession()->getToken(true);
	}
}
