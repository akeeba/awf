<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html\Helper;

use Awf\Html\AbstractHelper;
use Awf\Html\Html;
use Awf\Text\Text;

/**
 * Administration grid actions abstraction
 *
 * This class is based on the JHtml package of Joomla! 3
 *
 * @since 1.1.0
 */
class Grid extends AbstractHelper
{
	private $javascriptPrefix = 'akeeba.System.';

	/**
	 * Sets the JavaScript prefix.
	 *
	 * @param   string  $prefix  The new prefix to set, e.g. `myCompany.myApp.`
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function setJavascriptPrefix(string $prefix): void
	{
		$this->javascriptPrefix = $prefix;
	}

	/**
	 * Method to sort a column in a grid
	 *
	 * @param   string       $title          The link title
	 * @param   string       $order          The order field for the column
	 * @param   string       $direction      The current direction
	 * @param   string|null  $selected       The selected ordering
	 * @param   string|null  $task           An optional task override
	 * @param   string       $new_direction  An optional direction for the new column
	 * @param   string       $tip            An optional text shown as tooltip title instead of $title
	 * @param   string       $orderingJs     (optional) The Javascript function which handles table reordering, e.g.
	 *                                       "Foobar.System.tableOrdering"
	 *
	 * @return  string
	 */
	public function sort(
		string $title, string $order, ?string $direction = 'asc', ?string $selected = '', string $task = null,
		string $new_direction = 'asc', string $tip = '', string $orderingJs = ''
	)
	{
		$direction = strtolower($direction ?? '') ?: 'asc';

		$icon  = ['caret-up', 'caret-down'];
		$index = (int) ($direction == 'desc');

		if ($order != $selected)
		{
			$direction = $new_direction;
		}
		else
		{
			$direction = ($direction == 'desc') ? 'asc' : 'desc';
		}

		if (empty($orderingJs))
		{
			$orderingJs = $this->javascriptPrefix . 'tableOrdering';
		}

		$html = '<a href="#" onclick="' . $orderingJs . '(\'' . $order . '\',\'' . $direction . '\',\'' . $task
		        . '\');return false;"'
		        . ' class="hasTooltip" title="' . Text::_($tip ? $tip : $title) . '">';

		$html .= Text::_($title);

		if ($order == $selected)
		{
			$html .= ' <span class="fa fa-' . $icon[$index] . '"></span>';
		}

		$html .= '</a>';

		return $html;
	}

	/**
	 * Method to check all checkboxes in a grid
	 *
	 * @param   string  $name    The name of the form element
	 * @param   string  $tip     The text shown as tooltip title instead of $tip
	 * @param   string  $action  The action to perform on clicking the checkbox, e.g. "Foobar.System.checkAll(this)"
	 *
	 * @return  string
	 */
	public function checkAll(
		string $name = 'checkall-toggle', string $tip = 'AWF_COMMON_LBL_CHECK_ALL', string $action = ''
	): string
	{
		if (empty($action))
		{
			$action = $this->javascriptPrefix . 'checkAll(this)';
		}



		return '<input type="checkbox" name="' . $name . '" value="" class="hasTooltip" title="' .
		       $this->getContainer()->html->get('basic.tooltipText', $tip) . '" onclick="' . $action . '" />';
	}

	/**
	 * Method to create a checkbox for a grid row.
	 *
	 * @param   integer  $rowNum      The row index
	 * @param   integer  $recId       The record id
	 * @param   boolean  $checkedOut  True if item is checke out
	 * @param   string   $name        The name of the form element
	 * @param   string   $checkedJs   (optional) The Javscript function to determine if a box is checked, e.g.
	 *                                "Foobar.system.isChecked"
	 * @param   string   $altLabel    (optional) The (invisible) label for the checkbox
	 *
	 * @return  string    String of html with a checkbox if item is not checked out, empty string if checked out.
	 */
	public function id(
		int $rowNum, int $recId, bool $checkedOut = false, string $name = 'cid', string $checkedJs = '',
		string $altLabel = ''
	): string
	{
		if (empty($checkedJs))
		{
			$checkedJs = $this->javascriptPrefix . 'isChecked';
		}

		if ($checkedOut)
		{
			return '';
		}

		$altLabel = $altLabel ?: Text::_('AWF_LBL_HTML_GRID_ID_ALT_LABEL');

		// Note: The label for the checkbox is hidden in Bootstrap (visually-hidden) and Akeeba FEF (akeeba-sr-only).
		return <<< HTML
<label for="cb{$rowNum}"><span class="visually-hidden akeeba-sr-only">$altLabel</span></label><input type="checkbox" id="cb{$rowNum}" name="{$name}[]" value="$recId" onclick="$checkedJs(this.checked);" />
HTML;
	}
} 
