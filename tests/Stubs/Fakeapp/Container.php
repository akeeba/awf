<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 6/6/2014
 * Time: 4:11 μμ
 */

namespace Awf\Tests\Stubs\Fakeapp;

class Container extends \Awf\Container\Container
{
	public function __construct(array $values = array())
	{
		$basePath = realpath(__DIR__ . '/../../..') . '/tests';

		$defaults = array(
			'application_name'     => 'Fakeapp',
			'session_segment_name' => 'fakeapp',
			'basePath'             => $basePath . '/Stubs/Fakeapp',
			'languagePath'         => $basePath . '/Stubs/Fakeapp/language',
			'filesystemBase'       => $basePath . '/Stubs/Fakeapp',
			'templatePath'         => $basePath . '/Stubs/Fakeapp/template',
			'sqlPath'              => $basePath . '/Stubs/schema'
		);

		$values = array_merge($defaults, $values);

		parent::__construct($values);

		$this->appConfig->set('live_site', 'http://www.example.com');

		// I have to put the connection details inside the fake configuration, otherwise while running Data-aware Mvc
		// elements AWF will use the default connection params, leading to a connection error
		if (defined('AWFTEST_DATABASE_MYSQL_DSN') || getenv('AWFTEST_DATABASE_MYSQL_DSN'))
		{
			$dsn = defined('AWFTEST_DATABASE_MYSQL_DSN') ? AWFTEST_DATABASE_MYSQL_DSN : getenv('AWFTEST_DATABASE_MYSQL_DSN');

			// First let's trim the mysql: part off the front of the DSN if it exists.
			if (strpos($dsn, 'mysql:') === 0)
			{
				$dsn = substr($dsn, 6);
			}

			// Split the DSN into its parts over semicolons.
			$parts = explode(';', $dsn);

			// Prefxi could be optional, so let's set a default
			$prefix = 'awf_';

			// Parse each part and populate the options array.
			foreach ($parts as $part)
			{
				list ($k, $v) = explode('=', $part, 2);

				switch ($k)
				{
					case 'host':
						$this->appConfig->set('dbhost', $v);
						break;
					case 'dbname':
						$this->appConfig->set('dbname', $v);
						break;
					case 'user':
						$this->appConfig->set('dbuser', $v);
						break;
					case 'pass':
						$this->appConfig->set('dbpass', $v);
						break;
					case 'prefix':
						$prefix = $v;
						break;
				}
			}

			$this->appConfig->set('prefix', $prefix);
		}
	}
}