<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

/**
 * The Session package in Awf is based on the Session package in Aura for PHP, and has been modified to suit our needs.
 *
 * Please consult the LICENSE file in the Awf\Session package for copyright and license information of the original
 * library.
 */

namespace Awf\Session;

if (!defined('PHP_SESSION_NONE'))
{
	define('PHP_SESSION_NONE', 0);
}

if (!defined('PHP_SESSION_ACTIVE'))
{
	define('PHP_SESSION_ACTIVE', 1);
}

/**
 * A central control point for new session segments, PHP session management
 * values, and CSRF token checking.
 */
class Manager
{
	/**
	 * The session segment factory object.
	 *
	 * @var SegmentFactory
	 */
	protected $segment_factory;

	/**
	 * The CSRF token object for this session.
	 *
	 * @var CsrfToken
	 */
	protected $csrf_token;

	/**
	 * A CSRF token factory, for lazy-creating the CSRF token.
	 *
	 * @var CsrfTokenFactory
	 */
	protected $csrf_token_factory;

	/**
	 * Incoming cookies from the client, typically a copy of the $_COOKIE superglobal.
	 *
	 * @var array
	 */
	protected $cookies;

	/**
	 * Session cookie parameters.
	 *
	 * @var array
	 */
	protected $cookie_params = [];

	/**
	 * Array to hold already created session segments.
	 *
	 * @var array
	 */
	protected $segments = [];

	/**
	 * Parameters passed directly to session_start.
	 *
	 * @var    array
	 *
	 * @since  1.1.2
	 */
	protected $sessionCreateParameters = [];

	/**
	 * Public constructor.
	 *
	 * @param   SegmentFactory  $segment_factory  A session segment factory.
	 *
	 * @param   CsrfTokenFactory A CSRF token factory.
	 *
	 * @param   array           $cookies          An array of cookies from the client, typically a copy of $_COOKIE.
	 */
	public function __construct(
		SegmentFactory $segment_factory,
		CsrfTokenFactory $csrf_token_factory,
		array $cookies = [],
		array $sessionCreateParameters = []
	)
	{
		$this->segment_factory         = $segment_factory;
		$this->csrf_token_factory      = $csrf_token_factory;
		$this->cookies                 = $cookies;
		$this->cookie_params           = session_get_cookie_params();
		$this->sessionCreateParameters = $sessionCreateParameters;
	}

	/**
	 * Gets a new session segment instance by name.
	 *
	 * Segments with the same name will be different objects but will reference the same $_SESSION values, so it is
	 * possible to have two or more objects that share state.
	 *
	 * @param   string  $name  The name of the session segment, typically a
	 *                         fully-qualified class name.
	 *
	 * @return  Segment
	 *
	 */
	public function newSegment(string $name): Segment
	{
		if (!isset($this->segments[$name]))
		{
			$this->segments[$name] = $this->segment_factory->newInstance($this, $name);
		}

		return $this->segments[$name];
	}

	/**
	 * Tells us if a session is available to be reactivated, but not if it has
	 * started yet.
	 *
	 * @return  bool
	 *
	 */
	public function isAvailable(): bool
	{
		$name = $this->getName();

		return isset($this->cookies[$name]);
	}

	/**
	 * Tells us if a session has started.
	 *
	 * @return  bool
	 *
	 */
	public function isStarted(): bool
	{
		return $this->getStatus() == PHP_SESSION_ACTIVE;
	}

	/**
	 * Starts a new session, or resumes an existing one.
	 *
	 * @return  bool
	 *
	 */
	public function start(): bool
	{
		if (!$this->isStarted())
		{
			return @session_start(
				array_merge(
					[
						'cookie_lifetime' => $this->cookie_params['lifetime'] ?? 3600,
						'cookie_path'     => $this->cookie_params['path'] ?? '/',
						'cookie_domain'   => $this->cookie_params['domain'] ?? '',
						'cookie_secure'   => $this->cookie_params['secure'] ?? 0,
						'cookie_httponly' => $this->cookie_params['httponly'] ?? 1,
					],
					$this->sessionCreateParameters
				)
			);
		}

		return true;
	}

	/**
	 * Clears all session variables across all segments.
	 *
	 * @return  void
	 *
	 */
	public function clear(): void
	{
		$this->segments = [];

		@session_unset();
	}

	/**
	 * Writes session data from all segments and ends the session.
	 *
	 * @return  void
	 *
	 */
	public function commit(): void
	{
		if (count($this->segments))
		{
			/** @var SegmentInterface $segment */
			foreach ($this->segments as $segment)
			{
				$segment->save();
			}
		}

		@session_write_close();
	}

	/**
	 * Destroys the session entirely.
	 *
	 * @return bool
	 *
	 */
	public function destroy(): bool
	{
		if (!$this->isStarted())
		{
			$this->start();
		}

		$this->clear();

		return @session_destroy();
	}

	/**
	 * Set the algorithm for generating CSRF tokens.
	 *
	 * @param   string  $algorithm
	 *
	 * @return  void
	 */
	public function setCsrfTokenAlgorithm(string $algorithm): void
	{
		$this->csrf_token_factory->setAlgorithm($algorithm);
		$this->csrf_token = null;
	}

	/**
	 * Returns the CSRF token, creating it if needed, thereby starting a session.
	 *
	 * @return  CsrfToken
	 *
	 */
	public function getCsrfToken(): CsrfToken
	{
		if (!$this->csrf_token)
		{
			$this->csrf_token = $this->csrf_token_factory->newInstance($this);
		}

		return $this->csrf_token;
	}

	// =======================================================================
	//
	// support and admin methods
	//

	/**
	 * Sets the HTTP cache expire time.
	 *
	 * This has nothing to do with the session lifetime. It is the value used in the Expires: and Cache-Control:
	 * max-age headers.
	 *
	 * @param   int  $expire  The expiration time in seconds.
	 *
	 * @return  int
	 *
	 * @see     session_cache_expire()
	 *
	 */
	public function setCacheExpire(int $expire): int
	{
		return session_cache_expire($expire);
	}

	/**
	 * Gets the HTTP cache expire time.
	 *
	 * This has nothing to do with the session lifetime. It is the value used in the Expires: and Cache-Control:
	 * max-age headers.
	 *
	 * @return  int  The cache expiration time in seconds.
	 *
	 * @see     session_cache_expire()
	 *
	 */
	public function getCacheExpire(): int
	{
		return session_cache_expire();
	}

	/**
	 * Sets the cache limiter value.
	 *
	 * This has nothing to do with the session lifetime. It controls the Expires: and Cache-Control: max-age headers.
	 *
	 * @param   string  $limiter  The limiter value.
	 *
	 * @return  string
	 *
	 * @see     session_cache_limiter()
	 *
	 */
	public function setCacheLimiter(string $limiter): string
	{
		return session_cache_limiter($limiter);
	}

	/**
	 * Gets the session cache limiter value.
	 *
	 * This has nothing to do with the session lifetime. It controls the Expires: and Cache-Control: max-age headers.
	 *
	 * @return  string The limiter value.
	 *
	 * @see     session_cache_limiter()
	 *
	 */
	public function getCacheLimiter(): string
	{
		return session_cache_limiter();
	}

	/**
	 * Gets the session cookie params.
	 *
	 * @return  array
	 *
	 */
	public function getCookieParams(): array
	{
		return $this->cookie_params;
	}

	/**
	 * Sets the session cookie params.  Param array keys are:
	 *
	 * - `lifetime` : Lifetime of the session cookie, defined in seconds.
	 *
	 * - `path` : Path on the domain where the cookie will work.
	 *   Use a single slash ('/') for all paths on the domain.
	 *
	 * - `domain` : Cookie domain, for example 'www.php.net'.
	 *   To make cookies visible on all subdomains then the domain must be
	 *   prefixed with a dot like '.php.net'.
	 *
	 * - `secure` : If TRUE cookie will only be sent over secure connections.
	 *
	 * - `httponly` : If set to TRUE then PHP will attempt to send the httponly
	 *   flag when setting the session cookie.
	 *
	 * - `samesite` : See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
	 *
	 * @param   array  $params  The array of session cookie param keys and values.
	 *
	 * @return  void
	 *
	 * @see     session_set_cookie_params()
	 *
	 */
	public function setCookieParams(array $params)
	{
		$this->cookie_params = array_merge($this->cookie_params, $params);

		// On PHP 7.3+ we need to use the alternative syntax so that we can pass the `samesite` option, if it's defined.
		if (version_compare(PHP_VERSION, '7.3.0', 'ge'))
		{
			@session_set_cookie_params($params);

			return;
		}

		@session_set_cookie_params(
			$this->cookie_params['lifetime'],
			$this->cookie_params['path'],
			$this->cookie_params['domain'],
			$this->cookie_params['secure'],
			$this->cookie_params['httponly']
		);
	}

	/**
	 * Gets the current session ID.
	 *
	 * This is the unique identifier of the session.
	 *
	 * @return  string
	 */
	public function getId(): string
	{
		return session_id();
	}

	/**
	 * Regenerates and replaces the current session ID; also regenerates the CSRF token value if one exists.
	 *
	 * This should be called every time the security context of the session changes. It's also a good idea to
	 * periodically regenerate the session ID to prevent session hijacking.
	 *
	 * @return  bool  True if regeneration worked, false if not.
	 */
	public function regenerateId(): bool
	{
		$result = session_regenerate_id(true);

		if ($result && $this->csrf_token)
		{
			$this->csrf_token->regenerateValue();
		}

		return $result;
	}

	/**
	 * Sets the current session name.
	 *
	 * This is just the name of the cookie which holds the session ID. By default, it's something like PHPSESSID. It
	 * should be set to something more descriptive, per application, to make it easier for users to identify the cookies
	 * stored by their browser.
	 *
	 * @param   string  $name  The session name to use.
	 *
	 * @return  string
	 *
	 * @see     session_name()
	 */
	public function setName(string $name): string
	{
		return session_name($name);
	}

	/**
	 * Returns the current session name.
	 *
	 * This is just the name of the cookie which holds the session ID. By default, it's something like PHPSESSID. It
	 * should be set to something more descriptive, per application, to make it easier for users to identify the cookies
	 * stored by their browser.
	 *
	 * @return  string
	 */
	public function getName(): string
	{
		return session_name();
	}

	/**
	 * Sets the session save path.
	 *
	 * If you use a non-zero $levels parameter, every time you call this method we have to check if all the necessary
	 * subdirectories of the session save path are created (e.g. foo/a/a, foo/a/b, ...). This is SLOW. It's advisable
	 * to keep the number of levels low (1 or 2). If you have a massive amount of concurrent sessions it might be better
	 * overriding the whole Session package with something that uses a different kind of storage, e.g. Redis, Memcached,
	 * or something similar.
	 *
	 * @param   string  $path   The new save path.
	 * @param   int     $levels How many folder levels do you want for saving the sessions.
	 *
	 * @return  string  The actual session save path
	 *
	 * @see     session_save_path()
	 */
	public function setSavePath(string $path, int $levels = 0): string
	{
		// Workaround for some servers where the call to session_save_path() is ignored.
		$usedIniSet = false;

		$levels          = max(0, $levels);
		$prefixForLevels = '';

		if ($levels > 0)
		{
			$this->makeSubpaths($path, $levels);
			$prefixForLevels = $levels . ';';
		}

		if (function_exists('ini_set'))
		{
			$usedIniSet = true;
			ini_set('session.save_path', $prefixForLevels . $path);
		}

		if (function_exists('session_save_path'))
		{
			// session_save_path exists, return its output
			return session_save_path($prefixForLevels . $path);
		}

		if ($usedIniSet)
		{
			// session_save_path does not exist, but we used ini_set, i.e. we're using $path
			return $path;
		}

		// session_save_path does not exist, and we could not use ini_set, all bets are off...
		return $this->getSavePath();
	}

	/**
	 * Gets the session save path.
	 *
	 * @return  string
	 *
	 * @see     session_save_path()
	 */
	public function getSavePath(): string
	{
		$sessionPath = '';

		if (function_exists('session_save_path'))
		{
			$sessionPath = session_save_path();
		}
		elseif (function_exists('ini_get'))
		{
			$sessionPath = ini_get('session.save_path');
		}

		if (empty($sessionPath) && function_exists('sys_get_temp_dir'))
		{
			$sessionPath = sys_get_temp_dir();
		}

		return $sessionPath;
	}

	/**
	 * Returns the current session status:
	 *
	 * - `PHP_SESSION_DISABLED` if sessions are disabled.
	 * - `PHP_SESSION_NONE` if sessions are enabled, but none exists.
	 * - `PHP_SESSION_ACTIVE` if sessions are enabled, and one exists.
	 *
	 * @return  int
	 *
	 * @see     session_status()
	 */
	public function getStatus(): int
	{
		if (function_exists('session_status'))
		{
			return session_status();
		}

		$sid = session_id();

		return empty($sid) ? PHP_SESSION_NONE : PHP_SESSION_ACTIVE;
	}

	/**
	 * Create subpaths for storing sessions under multiple directories
	 *
	 * @param   string  $baseDir
	 * @param   int     $levels
	 *
	 * @return  void
	 * @since   1.2.0
	 */
	private function makeSubpaths(string $baseDir, int $levels = 2)
	{
		$allChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTYZ-,';

		foreach (str_split($allChars) as $char)
		{
			$newDir = $baseDir . '/' . $char;

			if (!@is_dir($newDir))
			{
				@mkdir($newDir, 0700, true);
			}

			if ($levels > 1)
			{
				$this->makeSubpaths($newDir, $levels - 1);
			}
		}
	}
}
