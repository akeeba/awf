<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\User\Exception;

/**
 * An abstract class representing a login error.
 *
 * This class extends the \RuntimeException PHP built-in exception class.
 * It serves as a base class for all login related exceptions in the application.
 *
 * @since 1.2.0
 */
abstract class LoginException extends \RuntimeException
{
}