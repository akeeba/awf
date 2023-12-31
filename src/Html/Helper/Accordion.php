<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html\Helper;

use Awf\Html\AbstractHelper;

/**
 * An abstraction around Bootstrap collapsible panels (accordions)
 *
 * @since 1.1.0
 */
class Accordion extends AbstractHelper
{
	/**
	 * @param   string   $title  The title HTML of this panel
	 * @param   string   $id  The ID of this panel
	 * @param   string   $accordionId  The ID of the accordion this panel belongs to
	 * @param   string   $panelStyle  The style of this panel (default, warning, info, success, danger)
	 * @param   boolean  $open  Is this panel open in the accordion?
	 */
	public static function panel(
		string $title, string $id, string $accordionId, string $panelStyle = 'default', bool $open = false
	): string
	{
		// Open a new panel inside the accordion
		$in = $open ? 'in' : '';

		return <<< HTML
			</div>
		</div>
	</div>
	<div class="panel panel-$panelStyle">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#$accordionId" href="#$id">
					$title
				</a>
			</h4>
		</div>
		<div id="$id" class="panel-collapse collapse $in">
			<div class="panel-body">
HTML;

	}

	/**
	 * Opens the current accordion group
	 *
	 * @param   string  $id  The ID of the accordion
	 *
	 * @return string
	 */
	public function start(string $id): string
	{
		return <<< HTML
<div class="panel-group" id="$id">
	<div style="display: none"><div><div>
HTML;

	}

	/**
	 * Close the current accordion group
	 *
	 * @return  string  HTML to close the accordion group
	 */
	public function end(): string
	{
		return <<< HTML
			</div>
		</div>
	</div>
</div>
HTML;

	}
} 
