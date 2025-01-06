<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\User\Exception;

use Awf\User\Exception\LoginException;

/**
 * Represents an exception that is thrown when the requested user account to log in is invalid.
 *
 * This exception extends the LoginException class, which is used to handle login-related exceptions.
 *
 * @since 1.2.0
 */
class InvalidUser extends LoginException
{

}