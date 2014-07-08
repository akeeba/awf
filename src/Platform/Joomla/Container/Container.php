<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Container;

use Awf\Database\Driver;
use Awf\Platform\Joomla\Application\Application;
use Awf\Platform\Joomla\Event\Dispatcher;

/**
 * A Container suitable for Joomla! integration
 *
 * @package Awf\Platform\Joomla\Container
 *
 * @property-read  \Awf\Platform\Joomla\Application\Application		$application           The application instance
 * @property-read  \Awf\Application\Configuration                   $appConfig             The application configuration registry
 * @property-read  \Awf\Platform\Joomla\Event\Dispatcher            $eventDispatcher       The global event dispatcher
 * @property-read  \JMail                                           $mailer                The email sender. Note: this is a factory method
 * @property-read  \Awf\Router\Router                               $router                The URL router
 * @property-read  \Awf\Session\Segment                             $segment               The session segment, where values are stored
 * @property-read  \Awf\Session\Manager                             $session               The session manager
 * @property-read  \Awf\User\ManagerInterface                       $userManager           The user manager object
 */
class Container extends \Awf\Container\Container
{
	public function __construct(array $values = array())
	{
		// Application service
		if (!isset($this['application']))
		{
			$this['application'] = function (Container $c)
			{
				return Application::getInstance($c->application_name, $c);
			};
		}

		// Session Manager service
		if (!isset($this['session']))
		{
			$this['session'] = function ()
			{
				return new \Awf\Platform\Joomla\Session\Manager(
					new \Awf\Platform\Joomla\Session\SegmentFactory,
					new \Awf\Platform\Joomla\Session\CsrfTokenFactory()
				);
			};
		}

		// Application Session Segment service
		if (!isset($this['segment']))
		{
			$this['segment'] = function (Container $c)
			{
				if (empty($c->session_segment_name))
				{
					$c->session_segment_name = $c->application_name;
				}

				return $c->session->newSegment($c->session_segment_name);
			};
		}

		// Database Driver service
		if (!isset($this['db']))
		{
			$this['db'] = function (Container $c)
			{
				$db = \JFactory::getDbo();

				$options = array(
					'connection' => $db->getConnection(),
					'prefix'     => $db->getPrefix(),
					'driver'     => 'mysqli',
				);

				switch ($db->name)
				{
					case 'mysql':
						$options['driver'] = 'Mysql';
						break;

					default:
					case 'mysqli':
						$options['driver'] = 'Mysqli';
						break;

					case 'sqlsrv':
					case 'mssql':
					case 'sqlazure':
						$options['driver'] = 'Sqlsrv';
						break;

					case 'postgresql':
						$options['driver'] = 'Postgresql';
						break;

					case 'pdo':
						$options['driver'] = 'Pdo';
						break;

					case 'sqlite':
						$options['driver'] = 'Sqlite';
						break;
				}

				return Driver::getInstance($options);
			};
		}

		// Application Event Dispatcher service
		if (!isset($this['eventDispatcher']))
		{
			$this['eventDispatcher'] = function (Container $c)
			{
				return new Dispatcher($c);
			};
		}

		parent::__construct($values);

		// Mailer Object service – returns a Joomla! JMail object
		// IMPORTANT! It has to appear AFTER the parent __construct method
		$this['mailer'] = $this->factory(function (Container $c)
		{
			return \JFactory::getMailer();
		});

	}
} 