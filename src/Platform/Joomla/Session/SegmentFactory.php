<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Session;

use Awf\Session\Manager as SessionManager;

/**
 * A factory to create session segment objects.
 */
class SegmentFactory extends \Awf\Session\SegmentFactory
{
	/**
	 *
	 * Creates a session segment object.
	 *
	 * @param SessionManager $manager
	 * @param string  $name
	 *
	 * @return Segment
	 */
	public function newInstance(SessionManager $manager, $name)
	{
		return new Segment($manager, $name);
	}
}
