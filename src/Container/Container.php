<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container;

use Awf\Application\Application;
use Awf\Application\ApplicationServiceProvider;
use Awf\Application\Configuration as AppConfiguration;
use Awf\Container\Defaults\AppConfigProvider;
use Awf\Container\Defaults\BladeProvider;
use Awf\Container\Defaults\DatabaseProvider;
use Awf\Container\Defaults\DateFactoryProvider;
use Awf\Container\Defaults\DispatcherProvider;
use Awf\Container\Defaults\EventDispatcherProvider;
use Awf\Container\Defaults\FilesystemProvider;
use Awf\Container\Defaults\HelperProvider;
use Awf\Container\Defaults\HTMLHelperProvider;
use Awf\Container\Defaults\InputProvider;
use Awf\Container\Defaults\LanguageFactoryProvider;
use Awf\Container\Defaults\LanguageProvider;
use Awf\Container\Defaults\MailerProvider;
use Awf\Container\Defaults\MVCFactoryProvider;
use Awf\Container\Defaults\RouterProvider;
use Awf\Container\Defaults\SegmentProvider;
use Awf\Container\Defaults\SessionProvider;
use Awf\Container\Defaults\UserManagerProvider;
use Awf\Database\Driver as DatabaseDriver;
use Awf\Date\Date;
use Awf\Dispatcher\Dispatcher as AppDispatcher;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Filesystem\FilesystemInterface as Filesystem;
use Awf\Helper\HelperService;
use Awf\Html\HtmlService as HtmlService;
use Awf\Input\Input;
use Awf\Mailer\Mailer;
use Awf\Mvc\Compiler\Blade as BladeCompiler;
use Awf\Mvc\Factory as MVCFactory;
use Awf\Pimple\Pimple;
use Awf\Router\Router;
use Awf\Session\Manager as SessionManager;
use Awf\Session\Segment as SessionSegment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;

/**
 * Dependency injection container for Awf's Application
 *
 * @property  string                    $application_name      The name of the application
 * @property  string                    $applicationNamespace  Namespace for the application classes, defaults to
 *            \\{$application_name}
 * @property  bool                      $autoloadHelpers       Should I autoload helper classes?
 * @property  string                    $basePath              The path to your application's PHP files
 * @property  string                    $filesystemBase        The base path of your web root,for use by
 *            Awf\Filesystem
 * @property  array                     $helperList            List of helper classnames to autoload. Empty to
 *            auto-detect.
 * @property string                     $helperPath            Absolute path to the Helpers. NULL to assume
 *           `src/Helpers` under the basePath.
 * @property  string                    $languagePath          The base path of all your language folders
 * @property  string                    $mediaQueryKey         The query string parameter to append to media added
 *            through the Template class
 * @property  string                    $session_segment_name  The name of the session segment
 * @property  string                    $sqlPath               The path to the SQL files restored by
 *            Awf\Database\Restore
 * @property  string                    $templatePath          The base path of all your template folders
 * @property  string                    $temporaryPath         The temporary directory of your application
 *
 * @property-read  AppConfiguration     $appConfig             The application configuration registry
 * @property-read  Application          $application           The application instance
 * @property-read  BladeCompiler        $blade                 The Blade view template compiler engine
 * @property-read  string               $constantPrefix        The prefix for the constants, default `APATH_`
 * @property-read  DatabaseDriver       $db                    The global database connection object
 * @property-read  AppDispatcher        $dispatcher            The application dispatcher
 * @property-read  EventDispatcher      $eventDispatcher       The global event dispatcher
 * @property-read  Filesystem           $fileSystem            The filesystem manager, created in hybrid mode
 * @property-read  HelperService        $helper                The helper server
 * @property-read  HtmlService          $html                  The HTML helper service
 * @property-read  Input                $input                 The global application input object
 * @property-read  Language             $language              The global language object
 * @property-read  Mailer               $mailer                The email sender. Note: this is a factory method
 * @property-read  MVCFactory           $mvcFactory            The MVC factory
 * @property-read  Router               $router                The URL router
 * @property-read  SessionSegment       $segment               The session segment, where values are stored
 * @property-read  SessionManager       $session               The session manager
 * @property-read  UserManagerInterface $userManager           The user manager object
 *
 * @method         Date  dateFactory(string $date = 'now', $tz = null)
 * @method         Language  languageFactory(string|null $langCode = null, UserInterface|null $user = null)
 *                 callable|callable[] $callbacks = [])
 */
class Container extends Pimple
{
	public function __construct(array $values = [])
	{
		$values = array_merge(
			[
				// Scalars
				'constantPrefix'       => 'APATH_',
				'application_name'     => null,
				'applicationNamespace' => null,
				'session_segment_name' => null,
				'filesystemBase'       => null,
				'basePath'             => null,
				'templatePath'         => null,
				'languagePath'         => null,
				'temporaryPath'        => null,
				'sqlPath'              => null,
				'autoloadHelpers'      => true,
				'helperList'           => [],
				'helperPath'           => null,
				'mediaQueryKey'        => null,
				// Services (and service factories)
				'application'          => new ApplicationServiceProvider(),
				'mvcFactory'           => new MVCFactoryProvider(),
				'appConfig'            => new AppConfigProvider(),
				'blade'                => new BladeProvider(),
				'db'                   => new DatabaseProvider(),
				'dispatcher'           => new DispatcherProvider(),
				'eventDispatcher'      => new EventDispatcherProvider(),
				'fileSystem'           => new FilesystemProvider(),
				'input'                => new InputProvider(),
				'mailer'               => new MailerProvider(),
				'router'               => new RouterProvider(),
				'session'              => new SessionProvider(),
				'segment'              => new SegmentProvider(),
				'userManager'          => new UserManagerProvider(),
				'dateFactory'          => new DateFactoryProvider(),
				'languageFactory'      => new LanguageFactoryProvider(),
				'language'             => new LanguageProvider(),
				'html'                 => new HTMLHelperProvider(),
				'helper'               => new HelperProvider(),
			], $values
		);

		parent::__construct($values);

		// Application name.
		$this['application_name'] = $this['application_name'] ?? call_user_func(
			function () {
				trigger_error(
					'You must provide a custom application_name in your AWF Container\'s constructor. Currently using ‘myapp’ as a default.',
					E_USER_WARNING
				);

				return 'myapp';
			}
		);

		// Application namespace
		$this['applicationNamespace'] = $this['applicationNamespace'] ?? call_user_func(
			function () {
				trigger_error(
					'You must provide a custom applicationNamespace in your AWF Container\'s constructor.',
					E_USER_WARNING
				);

				return '\\' . $this->application_name;
			}
		);

		// Session Segment name
		$this->session_segment_name = $this->session_segment_name ?? call_user_func(
			function () {
				trigger_error(
					'You must provide a custom session_segment_name in your AWF Container\'s constructor.',
					E_USER_WARNING
				);

				$installationId = 'default';

				if (function_exists('base64_encode'))
				{
					$installationId = base64_encode($this->application_name);
				}

				if (function_exists('md5'))
				{
					$installationId = md5($this->application_name);
				}

				if (function_exists('sha1'))
				{
					$installationId = sha1($this->application_name);
				}

				return $this->application_name . '_' . $installationId;
			}
		);

		// Filesystem base.
		$this['filesystemBase'] = $this['filesystemBase'] ?? call_user_func(
			function () {
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'BASE';

				if (defined($constantName))
				{
					return constant($constantName);
				}

				$default = getcwd();

				trigger_error(
					sprintf(
						'You should provide a custom filesystemBase in your AWF Container\'s constructor, or set the %sBASE constant. Currently using %s as the default.',
						$this->constantPrefix, $default
					),
					E_USER_WARNING
				);

				return $default;
			}
		);

		// Application base path.
		$this->basePath = $this->basePath ?? call_user_func(
			function () {
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'BASE';

				if (defined($constantName))
				{
					return constant($constantName) . '/' . ucfirst($this->application_name);
				}

				$default = getcwd() . '/' . ucfirst($this->application_name);

				trigger_error(
					sprintf(
						'You must provide a custom basePath in your AWF Container\'s constructor, or set the %s_BASE constant. Currently using %s as the default.',
						$this->constantPrefix,
						$default
					),
					E_USER_WARNING
				);

				return $default;
			}
		);

		// Templates path
		$this->templatePath = $this->templatePath ?? call_user_func(
			function () {
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'THEMES';

				if (defined($constantName))
				{
					return constant($constantName);
				}

				$default = $this->filesystemBase . '/templates';

				trigger_error(
					sprintf(
						'You must provide a custom templatePath in your AWF Container\'s constructor, or set the %s_THEMES constant. Currently using %s as the default.',
						$this->constantPrefix,
						$default
					),
					E_USER_WARNING
				);

				return $default;
			}
		);

		// Language files path
		$this->languagePath = $this->languagePath ?? call_user_func(
			function () {
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'TRANSLATION';

				if (defined($constantName))
				{
					return constant($constantName);
				}

				$default = $this->filesystemBase . '/languages';

				trigger_error(
					sprintf(
						'You must provide a custom languagePath in your AWF Container\'s constructor, or set the %s_TRANSLATION constant. Currently using %s as the default.',
						$this->constantPrefix,
						$default
					),
					E_USER_WARNING
				);

				return $default;
			}
		);

		// Temporary path
		$this->temporaryPath = $this->temporaryPath ?? call_user_func(
			function () {
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'TMP';

				if (defined($constantName))
				{
					return constant($constantName);
				}

				if (is_dir($this->basePath . '/tmp'))
				{
					return $this->basePath . '/tmp';
				}

				$path = sys_get_temp_dir();

				if (@is_dir($path) && @is_writable($path))
				{
					return $path;
				}

				trigger_error(
					sprintf(
						'The autodetected temporary folder %s is not writeable. Things may get weird.',
						$path
					),
					E_USER_NOTICE
				);

				return $path;
			}
		);

		// SQL path
		$this->sqlPath = $this->sqlPath ?? call_user_func(
			function () {
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'SQL';

				if (defined($constantName))
				{
					return constant($constantName);
				}

				// We DO NOT raise a warning; the implicit default will continue to be supported.
				$constantName = ($this->constantPrefix ?: 'APATH_') . 'ROOT';
				$rootFolder   = defined($constantName) ? constant($constantName) : realpath($this->basePath . '/..');

				return $rootFolder . '/installation/sql';
			}
		);
	}

	/**
	 * Magic method caller.
	 *
	 * Allows calling stored callables directly, as if they were methods. Useful for factories which take parameters.
	 *
	 * @param   string  $name       The name of the callable to execute
	 * @param   array   $arguments  Any parameters to the callable
	 *
	 * @return  mixed  The return type of the callable.
	 * @since   1.1.0
	 */
	public function __call($name, $arguments)
	{
		$callable = $this->offsetGet($name);

		if (!is_callable($callable))
		{
			throw new \BadMethodCallException(
				sprintf(
					'Method %s::%s does not exist.',
					__CLASS__,
					htmlentities($name)
				),
				500
			);
		}

		return call_user_func($callable, ...$arguments);
	}


}
