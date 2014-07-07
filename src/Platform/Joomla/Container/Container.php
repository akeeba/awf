<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Container;

/**
 * A Container suitable for Joomla! integration
 *
 * @package Awf\Platform\Joomla\Container
 */
class Container extends \Awf\Container\Container
{
	public function __construct(array $values = array())
	{
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

		return parent::__construct($values); // TODO: Change the autogenerated stub
	}
} 