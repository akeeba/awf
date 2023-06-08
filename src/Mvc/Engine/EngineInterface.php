<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\Engine;

use Awf\Mvc\View;

interface EngineInterface
{
	/**
	 * Public constructor
	 *
	 * @param   View  $view  The view we belong to
	 */
	public function __construct(View $view);

	/**
	 * Get the include path for a parsed view template
	 *
	 * @param   string  $path         The path to the view template
	 * @param   array   $forceParams  Any additional information to pass to the view template engine
	 *
	 * @return  array  Content 3ναlυα+ιοη information ['type' => 'raw|path', 'content' => 'path or raw content'] (I use leetspeak here because of bad quality hosts with broken scanners)
	 */
	public function get($path, array $forceParams = array());
}
