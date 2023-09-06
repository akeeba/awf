<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container;

use Awf\Application\Application;
use Awf\Application\ApplicationServiceProvider;
use Awf\Application\Configuration as AppConfiguration;
use Awf\Database\Driver;
use Awf\Database\Driver as DatabaseDriver;
use Awf\Date\Date;
use Awf\Dispatcher\Dispatcher as AppDispatcher;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Filesystem\Factory as FilesystemFactory;
use Awf\Filesystem\FilesystemInterface as Filesystem;
use Awf\Html\Helper\Accordion as AccordionHtmlHelper;
use Awf\Html\Helper\Basic as BasicHtmlHelper;
use Awf\Html\Helper\Behaviour as BehaviourHtmlHelper;
use Awf\Html\Helper\Grid as GridHtmlHelper;
use Awf\Html\Helper\Select as SelectHtmlHelper;
use Awf\Html\Helper\Tabs as TabsHtmlHelper;
use Awf\Html\Service as HtmlService;
use Awf\Input\Input;
use Awf\Mailer\Mailer;
use Awf\Mvc\Compiler\Blade;
use Awf\Mvc\Compiler\Blade as BladeCompiler;
use Awf\Mvc\Factory as MVCFactory;
use Awf\Pimple\Pimple;
use Awf\Router\Router;
use Awf\Session;
use Awf\Session\Manager as SessionManager;
use Awf\Session\Segment as SessionSegment;
use Awf\User\Manager as UserManager;
use Awf\User\ManagerInterface as UserManagerInterface;

/**
 * Dependency injection container for Awf's Application
 *
 * @property  string                    $application_name      The name of the application
 * @property  string                    $session_segment_name  The name of the session segment
 * @property  string                    $basePath              The path to your application's PHP files
 * @property  string                    $templatePath          The base path of all your template folders
 * @property  string                    $languagePath          The base path of all your language folders
 * @property  string                    $temporaryPath         The temporary directory of your application
 * @property  string                    $filesystemBase        The base path of your web root (for use by
 *            Awf\Filesystem)
 * @property  string                    $sqlPath               The path to the SQL files restored by
 *            Awf\Database\Restore
 * @property  string                    $mediaQueryKey         The query string parameter to append to media added
 *            through the Template class
 * @property  string                    $applicationNamespace  Namespace for the application classes, defaults to
 *            \\{$application_name}
 * @property  bool                      $autoloadHelpers       Should I autoload helper classes?
 * @property  array                     $helperList            List of helper classnames to autoload. Empty to
 *            auto-detect.
 * @property string                     $helperPath            Absolute path to the Helpers. NULL to assume
 *           `src/Helpers` under the basePath.
 *
 * @property-read  string               $constantPrefix        The prefix for the constants, default `APATH_`
 * @property-read  MVCFactory           $mvcFactory            The MVC factory
 * @property-read  Application          $application           The application instance
 * @property-read  AppConfiguration     $appConfig             The application configuration registry
 * @property-read  BladeCompiler        $blade                 The Blade view template compiler engine
 * @property-read  DatabaseDriver       $db                    The global database connection object
 * @property-read  AppDispatcher        $dispatcher            The application dispatcher
 * @property-read  EventDispatcher      $eventDispatcher       The global event dispatcher
 * @property-read  Filesystem           $fileSystem            The filesystem manager, created in hybrid mode
 * @property-read  Input                $input                 The global application input object
 * @property-read  Mailer               $mailer                The email sender. Note: this is a factory method
 * @property-read  Router               $router                The URL router
 * @property-read  SessionSegment       $segment               The session segment, where values are stored
 * @property-read  SessionManager       $session               The session manager
 * @property-read  UserManagerInterface $userManager           The user manager object
 * @property-read  HtmlService          $html                  The HTML helper service
 */
class Container extends Pimple
{
	public function __construct(array $values = [])
	{
		$this->constantPrefix       = 'APATH_';
		$this->application_name     = '';
		$this->session_segment_name = null;
		$this->basePath             = null;
		$this->templatePath         = null;
		$this->languagePath         = null;
		$this->temporaryPath        = null;
		$this->filesystemBase       = null;
		$this->sqlPath              = null;
		$this->mediaQueryKey        = null;
		$this->autoloadHelpers      = true;
		$this->helperList           = [];
		$this->helperPath           = null;

		parent::__construct($values);

		// Application name. YOU SHOULD DEFINE THIS IN YOUR CONTAINER!
		if ($this['application_name'] === null)
		{
			trigger_error(
				'You must provide a custom application_name in your AWF Container\'s constructor. Currently using ‘myapp’ as a default.',
				E_USER_WARNING
			);

			$this->application_name = 'myapp';
		}

		// Filesystem base.
		if ($this['filesystemBase'] === null)
		{
			$this->filesystemBase = call_user_func(
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
		}

		// Application base path.
		if ($this['basePath'] === null)
		{
			$this->basePath = call_user_func(
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
		}

		// Templates path
		$this->templatePath = $this->templatePath ?? call_user_func(function () {
			$constantName = ($this->constantPrefix ?: 'APATH_') . 'THEMES';

			return defined($constantName)
				? constant($constantName)
				: $this->filesystemBase . '/templates';
		});

		// Language files path
		$this->languagePath = call_user_func(function () {
			$constantName = ($this->constantPrefix ?: 'APATH_') . 'TRANSLATION';

			return defined($constantName)
				? constant($constantName)
				: $this->filesystemBase . '/languages';
		});

		// Temporary path
		if ($this['temporaryPath'] === null)
		{
			$this->temporaryPath = call_user_func(
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
		}

		// SQL path
		$this->sqlPath = $this->sqlPath ?? call_user_func(function () {
			$constantName = ($this->constantPrefix ?: 'APATH_') . 'ROOT';

			return defined($constantName)
				? constant($constantName)
				: $this->filesystemBase . '/installation/sql';
		});

		// Application namespace
		$this['applicationNamespace'] = $this['applicationNamespace'] ?? '\\' . $this->application_name;

		// Application service
		if (!isset($this['application']))
		{
			$this->register(new ApplicationServiceProvider());
		}

		// MVC Factory
		if (!isset($this['mvcFactory']))
		{
			$this['mvcFactory'] = function (Container $c) {
				return new MVCFactory($c);
			};
		}

		// Application Configuration service
		if (!isset($this['appConfig']))
		{
			$this['appConfig'] = function (Container $c) {
				return new AppConfiguration($c);
			};
		}

		// Blade view template compiler service
		if (!isset($this['blade']))
		{
			$this['blade'] = function (Container $c) {
				return new Blade($c);
			};
		}

		// Database Driver service
		if (!isset($this['db']))
		{
			$this['db'] = function (Container $c) {
				return Driver::fromContainer($c);
			};
		}

		// Application Dispatcher service
		if (!isset($this['dispatcher']))
		{
			$this['dispatcher'] = function (Container $c) {
				$className = $this->applicationNamespace . '\\Dispatcher';

				if (!class_exists($className))
				{
					$className = '\\' . ucfirst($c->application_name) . '\Dispatcher';
				}

				if (!class_exists($className))
				{
					$className = AppDispatcher::class;
				}

				return new $className($c);
			};
		}

		// Application Event Dispatcher service
		if (!isset($this['eventDispatcher']))
		{
			$this['eventDispatcher'] = function (Container $c) {
				return new EventDispatcher($c);
			};
		}

		// Filesystem Abstraction Layer service
		if (!isset($this['fileSystem']))
		{
			$this['fileSystem'] = function (Container $c) {
				return FilesystemFactory::getAdapter($c, true);
			};
		}

		// Input Access service
		if (!isset($this['input']))
		{
			$this['input'] = function (Container $c) {
				return new Input();
			};
		}

		// Mailer Object service
		if (!isset($this['mailer']))
		{
			$this['mailer'] = $this->factory(
				function (Container $c) {
					return new Mailer($c);
				}
			);
		}

		// Application Router service
		if (!isset($this['router']))
		{
			$this['router'] = function (Container $c) {
				return new Router($c);
			};
		}

		// Session Manager service
		if (!isset($this['session']))
		{
			$this['session'] = function () {
				return new Session\Manager(
					new Session\SegmentFactory,
					new Session\CsrfTokenFactory(),
					$_COOKIE
				);
			};
		}

		// Application Session Segment service
		if (!isset($this['segment']))
		{
			$this['segment'] = function (Container $c) {
				if (empty($c->session_segment_name))
				{
					$c->session_segment_name = 'Akeeba\\Awf\\' . $c->application_name;
				}

				return $c->session->newSegment($c->session_segment_name);
			};
		}

		// User Manager service
		if (!isset($this['userManager']))
		{
			$this['userManager'] = function (Container $c) {
				return new UserManager($c);
			};
		}

		// HTML Helper service
		if (!isset($this['html']))
		{
			$this['html'] = function (Container $c) {
				$service = new HtmlService($c);

				$service->registerHelperClass(AccordionHtmlHelper::class);
				$service->registerHelperClass(BasicHtmlHelper::class);
				$service->registerHelperClass(BehaviourHtmlHelper::class);
				$service->registerHelperClass(GridHtmlHelper::class);
				$service->registerHelperClass(SelectHtmlHelper::class);
				$service->registerHelperClass(TabsHtmlHelper::class);

				return $service;
			};
		}
	}

	/**
	 * @param   string                     $date
	 * @param   string|\DateTimeZone|null  $tz
	 *
	 * @return  Date
	 */
	public function dateFactory(string $date = 'now', $tz = null)
	{
		return new Date($date, $tz, $this);
	}
}
