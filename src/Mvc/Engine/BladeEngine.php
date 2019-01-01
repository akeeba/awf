<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Mvc\Engine;

use Awf\Mvc\View;

/**
 * View engine for compiling PHP template files.
 */
class BladeEngine extends CompilingEngine implements EngineInterface
{
	public function __construct(View $view)
	{
		parent::__construct($view);

		// Assign the Blade compiler to this engine
		$this->compiler = $view->getContainer()->blade;
	}
}
