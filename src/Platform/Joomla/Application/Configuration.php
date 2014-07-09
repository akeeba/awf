<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Application;

use Awf\Utils\Phpfunc;

class Configuration extends \Awf\Application\Configuration
{
	/**
	 * Loads the configuration from the Joomla! global configuration itself
	 *
	 * @param string  $filePath Ignored
	 * @param Phpfunc $phpfunc  Ignored
	 *
	 * @return  void
	 */
	public function loadConfiguration($filePath = null, Phpfunc $phpfunc = null)
	{
		// Get the Joomla! configuration object
		$jConfig = \JFactory::getConfig();

		// Create the basic configuration data
		$data = array(
			'timezone'	=> $jConfig->get('offset', 'UTC'),
			'fs'		=> array(
				'driver'	=> 'file'
			),
			'dateformat'	=> \JText::_('DATE_FORMAT_LC2'),
			'base_url'	=> \JUri::base() . '/index.php?option=com_' . strtolower($this->container->application_name),
			'live_site'	=> \JUri::base() . '/index.php?option=com_' . strtolower($this->container->application_name),
			'cms_url'	=> \JUri::base(),
		);

		// Get the Joomla! FTP layer options
		if (!class_exists('JClientHelper'))
		{
			\JLoader::import('joomla.client.helper');
		}

		$ftpOptions = \JClientHelper::getCredentials('ftp');

		// If the FTP layer is enabled, enable the Hybrid filesystem engine
		if ($ftpOptions['enabled'] == 1)
		{
			$data['fs'] = array(
				'driver'	=> 'hybrid',
				'host'		=> $ftpOptions['host'],
				'port'		=> empty($ftpOptions['port']) ? '21' : $ftpOptions['port'],
				'directory'	=> rtrim($ftpOptions['root'], '/\\'),
				'ssl'		=> false,
				'passive'	=> true,
				'username'	=> $ftpOptions['user'],
				'password'	=> $ftpOptions['pass'],
			);
		}

		// Finally, load the data to the registry class
		$this->data = new \stdClass();
		$this->loadArray($data);
	}

	/**
	 * Not available in Joomla!
	 *
	 * @param   string $filePath Ignored
	 *
	 * @return  void
	 *
	 * @throws  \RuntimeException  Always
	 */
	public function saveConfiguration($filePath = null)
	{
		throw new \RuntimeException('Cannot save the configuration when running inside Joomla');
	}
}