<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html\Helper;

use Awf\Html\AbstractHelper;
use Awf\Utils\ArrayHelper;

/**
 * An abstraction to creating HTML select elements and related widgets
 *
 * This class is based on the JHtml package of Joomla! 3 but heavily modified
 *
 * @since 1.1.0
 */
class Select extends AbstractHelper
{
	/**
	 * Default values for options. Organized by option group.
	 *
	 * @var     array
	 */
	protected $optionDefaults = [
		'option' => [
			'option.attr'         => null,
			'option.disable'      => 'disable',
			'option.id'           => null,
			'option.key'          => 'value',
			'option.key.toHtml'   => true,
			'option.label'        => null,
			'option.label.toHtml' => true,
			'option.text'         => 'text',
			'option.text.toHtml'  => true,
		],
	];

	public function setOptionSettings(array $options)
	{
		foreach ($options as $k => $v)
		{
			if (!isset($this->optionDefaults['option'][$k]))
			{
				continue;
			}

			$this->optionDefaults['option'][$k] = $v;
		}
	}

	public function getOptionSettings(): array
	{
		return $this->optionDefaults['option'];
	}

	/**
	 * Generates a yes/no radio list.
	 *
	 * @param   string             $name      The value of the HTML name attribute
	 * @param   array              $attribs   Additional HTML attributes for the <select> tag
	 * @param   string|null|array  $selected  The key that is selected
	 * @param   string             $yes       Language key for Yes
	 * @param   string             $no        Language key for no
	 * @param   bool|null|string   $id        The id for the field
	 *
	 * @return  string  HTML for the radio list
	 */
	public function booleanList(
		string $name, array $attribs = [], $selected = null, string $yes = 'AWF_YES', string $no = 'AWF_NO',
		$id = false
	): string
	{
		$arr = [
			$this->option('0', $this->getContainer()->language->text($no)),
			$this->option('1', $this->getContainer()->language->text($yes))
		];

		return $this->radioList($arr, $name, $attribs, 'value', 'text', (int) $selected, $id);
	}

	/**
	 * Generates an HTML selection list.
	 *
	 * @param   array              $data       An array of objects, arrays, or scalars.
	 * @param   string             $name       The value of the HTML name attribute.
	 * @param   mixed              $attribs    Additional HTML attributes for the <select> tag. This
	 *                                         can be an array of attributes, or an array of options. Treated as options
	 *                                         if it is the last argument passed. Valid options are:
	 *                                         Format options,
	 *                                         Selection options, see {@see Select::options()}.
	 *                                         list.attr, string|array: Additional attributes for the select
	 *                                         element.
	 *                                         id, string: Value to use as the select element id attribute.
	 *                                         Defaults to the same as the name.
	 *                                         list.select, string|array: Identifies one or more option elements
	 *                                         to be selected, based on the option key values.
	 * @param   string             $optKey     The name of the object variable for the option value. If
	 *                                         set to null, the index of the value array is used.
	 * @param   string             $optText    The name of the object variable for the option text.
	 * @param   null|string|array  $selected   The key that is selected (accepts an array or a string).
	 * @param   bool|null|string   $idTag      Value of the field id or null by default
	 * @param   boolean            $translate  True to translate
	 *
	 * @return  string  HTML for the select list.
	 */
	public function genericList(
		array $data, string $name, ?array $attribs = null, string $optKey = 'value', string $optText = 'text',
		$selected = null, $idTag = false, bool $translate = false
	): string
	{
		// Set default options
		$options = array_merge(
			$this->getContainer()->html->getFormatOptions(),
			['format.depth' => 0, 'id' => false]
		);

		if (is_array($attribs) && func_num_args() == 3)
		{
			// Assume we have an options array
			$options = array_merge($options, $attribs);
		}
		else
		{
			// Get options from the parameters
			$options['id']             = $idTag;
			$options['list.attr']      = $attribs;
			$options['list.translate'] = $translate;
			$options['option.key']     = $optKey;
			$options['option.text']    = $optText;
			$options['list.select']    = $selected;
		}

		$attribs = '';

		if (isset($options['list.attr']))
		{
			if (is_array($options['list.attr']))
			{
				$attribs = ArrayHelper::toString($options['list.attr']);
			}
			else
			{
				$attribs = $options['list.attr'];
			}

			if ($attribs != '')
			{
				$attribs = ' ' . $attribs;
			}
		}

		$id = $options['id'] !== false ? $options['id'] : $name;
		$id = str_replace(['[', ']'], '', $id);

		$baseIndent = str_repeat($options['format.indent'], $options['format.depth']++);
		$html       = $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"'
		              . $attribs . '>' . $options['format.eol']
		              . $this->options($data, $options) . $baseIndent . '</select>' . $options['format.eol'];

		return $html;
	}

	/**
	 * Method to build a list with suggestions
	 *
	 * @param   array             $data       An array of objects, arrays, or values.
	 * @param   string            $optKey     The name of the object variable for the option value. If
	 *                                        set to null, the index of the value array is used.
	 * @param   string            $optText    The name of the object variable for the option text.
	 * @param   bool|null|string  $idTag      Value of the field id or null by default
	 * @param   boolean           $translate  True to translate
	 *
	 * @return  string  HTML for the select list
	 */
	public function suggestionList(
		array $data, string $optKey = 'value', string $optText = 'text', $idTag = '', bool $translate = false
	): string
	{
		// Set default options
		$options = array_merge(
			$this->getContainer()->html->getFormatOptions(),
			['format.depth' => 0, 'id' => false]
		);

		// Get options from the parameters
		$options['id']             = $idTag;
		$options['list.attr']      = null;
		$options['list.translate'] = $translate;
		$options['option.key']     = $optKey;
		$options['option.text']    = $optText;
		$options['list.select']    = null;

		$id = ' id="' . $idTag . '"';

		$baseIndent = str_repeat($options['format.indent'], $options['format.depth']++);
		$html       = $baseIndent . '<datalist' . $id . '>' . $options['format.eol']
		              . $this->options($data, $options) . $baseIndent . '</datalist>' . $options['format.eol'];

		return $html;
	}

	/**
	 * Generates a grouped HTML selection list from nested arrays.
	 *
	 * @param   array   $data     An array of groups, each of which is an array of options.
	 * @param   string  $name     The value of the HTML name attribute
	 * @param   array   $options  Options, an array of key/value pairs. Valid options are:
	 *                            Format options,
	 *                            Selection options. See {@see Select::options()}.
	 *                            group.id: The property in each group to use as the group id
	 *                            attribute. Defaults to none.
	 *                            group.label: The property in each group to use as the group
	 *                            label. Defaults to "text". If set to null, the data array index key is
	 *                            used.
	 *                            group.items: The property in each group to use as the array of
	 *                            items in the group. Defaults to "items". If set to null, group.id and
	 *                            group. label are forced to null and the data element is assumed to be a
	 *                            list of selections.
	 *                            id: Value to use as the select element id attribute. Defaults to
	 *                            the same as the name.
	 *                            list.attr: Attributes for the select element. Can be a string or
	 *                            an array of key/value pairs. Defaults to none.
	 *                            list.select: either the value of one selected option or an array
	 *                            of selected options. Default: none.
	 *                            list.translate: Boolean. If set, text and labels are translated via
	 *                            Text::_().
	 *
	 * @return  string  HTML for the select list
	 *
	 * @throws  \RuntimeException If a group has contents that cannot be processed.
	 */
	public function groupedList(array $data, string $name, array $options = []): string
	{
		// Set default options and overwrite with anything passed in
		$options = array_merge(
			$this->getContainer()->html->getFormatOptions(),
			[
				'format.depth'       => 0,
				'group.items'        => 'items',
				'group.label'        => 'text',
				'group.label.toHtml' => true,
				'id'                 => false,
			],
			$options
		);

		// Apply option rules
		if ($options['group.items'] === null)
		{
			$options['group.label'] = null;
		}

		$attribs = '';

		if (isset($options['list.attr']))
		{
			if (is_array($options['list.attr']))
			{
				$attribs = ArrayHelper::toString($options['list.attr']);
			}
			else
			{
				$attribs = $options['list.attr'];
			}

			if ($attribs != '')
			{
				$attribs = ' ' . $attribs;
			}
		}

		$id = $options['id'] !== false ? $options['id'] : $name;
		$id = str_replace(['[', ']'], '', $id);

		// Disable groups in the options.
		$options['groups'] = false;

		$baseIndent  = str_repeat($options['format.indent'], $options['format.depth']++);
		$html        = $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"'
		               . $attribs . '>' . $options['format.eol'];
		$groupIndent = str_repeat($options['format.indent'], $options['format.depth']++);

		foreach ($data as $dataKey => $group)
		{
			$label   = $dataKey;
			$id      = '';
			$noGroup = is_int($dataKey);

			if ($options['group.items'] == null)
			{
				// Sub-list is an associative array
				$subList = $group;
			}
			elseif (is_array($group))
			{
				// Sub-list is in an element of an array.
				$subList = $group[$options['group.items']];

				if (isset($group[$options['group.label']]))
				{
					$label   = $group[$options['group.label']];
					$noGroup = false;
				}

				if (isset($options['group.id']) && isset($group[$options['group.id']]))
				{
					$id      = $group[$options['group.id']];
					$noGroup = false;
				}
			}
			elseif (is_object($group))
			{
				// Sub-list is in a property of an object
				$subList = $group->{$options['group.items']};

				if (isset($group->{$options['group.label']}))
				{
					$label   = $group->{$options['group.label']};
					$noGroup = false;
				}

				if (isset($options['group.id']) && isset($group->{$options['group.id']}))
				{
					$id      = $group->{$options['group.id']};
					$noGroup = false;
				}
			}
			else
			{
				throw new \RuntimeException('Invalid group contents.', 1);
			}

			if ($noGroup)
			{
				$html .= $this->options($subList, $options);
			}
			else
			{
				$html .= $groupIndent . '<optgroup' . (empty($id) ? '' : ' id="' . $id . '"') . ' label="'
				         . ($options['group.label.toHtml'] ? htmlspecialchars($label ?? '', ENT_COMPAT, 'UTF-8')
						: $label) . '">' . $options['format.eol']
				         . $this->options($subList, $options) . $groupIndent . '</optgroup>' . $options['format.eol'];
			}
		}

		$html .= $baseIndent . '</select>' . $options['format.eol'];

		return $html;
	}

	/**
	 * Generates a selection list of integers.
	 *
	 * @param   integer            $start     The start integer
	 * @param   integer            $end       The end integer
	 * @param   integer            $inc       The increment
	 * @param   string             $name      The value of the HTML name attribute
	 * @param   array|null         $attribs   Additional HTML attributes for the <select> tag, an array of
	 *                                        attributes, or an array of options. Treated as options if it is the last
	 *                                        argument passed.
	 * @param   string|null|array  $selected  The key that is selected
	 * @param   string             $format    The printf format to be applied to the number
	 *
	 * @return  string   HTML for the select list
	 */
	public function integerList(
		int $start, int $end, int $inc, string $name, ?array $attribs = null, $selected = null,
		string $format = ''
	): string
	{
		// Set default options
		$options = array_merge(
			$this->getContainer()->html->getFormatOptions(),
			['format.depth' => 0, 'option.format' => '', 'id' => null]
		);

		if (is_array($attribs) && func_num_args() == 5)
		{
			// Assume we have an options array
			$options = array_merge($options, $attribs);

			// Extract the format and remove it from downstream options
			$format = $options['option.format'];
			unset($options['option.format']);
		}
		else
		{
			// Get options from the parameters
			$options['list.attr']   = $attribs;
			$options['list.select'] = $selected;
		}

		$start = (int) $start;
		$end   = (int) $end;
		$inc   = (int) $inc;

		$data = [];

		for ($i = $start; $i <= $end; $i += $inc)
		{
			$data[$i] = $format ? sprintf($format, $i) : $i;
		}

		// Tell genericlist() to use array keys
		$options['option.key'] = null;

		return $this->genericList($data, $name, $options);
	}

	/**
	 * Create an object that represents an option in an option list.
	 *
	 * @param   string        $value    The value of the option
	 * @param   string        $text     The text for the option
	 * @param   string|array  $optKey   If a string, the returned object property name for
	 *                                  the value. If an array, options. Valid options are:
	 *                                  attr: String|array. Additional attributes for this option.
	 *                                  Defaults to none.
	 *                                  disable: Boolean. If set, this option is disabled.
	 *                                  label: String. The value for the option label.
	 *                                  option.attr: The property in each option array to use for
	 *                                  additional selection attributes. Defaults to none.
	 *                                  option.disable: The property that will hold the disabled state.
	 *                                  Defaults to "disable".
	 *                                  option.key: The property that will hold the selection value.
	 *                                  Defaults to "value".
	 *                                  option.label: The property in each option array to use as the
	 *                                  selection label attribute. If a "label" option is provided, defaults to
	 *                                  "label", if no label is given, defaults to null (none).
	 *                                  option.text: The property that will hold the the displayed text.
	 *                                  Defaults to "text". If set to null, the option array is assumed to be a
	 *                                  list of displayable scalars.
	 * @param   string        $optText  The property that will hold the the displayed text. This
	 *                                  parameter is ignored if an options array is passed.
	 * @param   boolean       $disable  Not used.
	 *
	 * @return  object
	 */
	public function option(
		string $value, string $text = '', $optKey = 'value', string $optText = 'text', bool $disable = false
	): object
	{
		$options = [
			'attr'           => null,
			'disable'        => false,
			'option.attr'    => null,
			'option.disable' => 'disable',
			'option.key'     => 'value',
			'option.label'   => null,
			'option.text'    => 'text',
		];

		if (is_array($optKey))
		{
			// Merge in caller's options
			$options = array_merge($options, $optKey);
		}
		else
		{
			// Get options from the parameters
			$options['option.key']  = $optKey;
			$options['option.text'] = $optText;
			$options['disable']     = $disable;
		}

		$obj                            = new \stdClass;
		$obj->{$options['option.key']}  = $value;
		$obj->{$options['option.text']} = trim($text) ? $text : $value;

		/*
		 * If a label is provided, save it. If no label is provided and there is
		 * a label name, initialise to an empty string.
		 */
		$hasProperty = $options['option.label'] !== null;

		if (isset($options['label']))
		{
			$labelProperty       = $hasProperty ? $options['option.label'] : 'label';
			$obj->$labelProperty = $options['label'];
		}
		elseif ($hasProperty)
		{
			$obj->{$options['option.label']} = '';
		}

		// Set attributes only if there is a property and a value
		if ($options['attr'] !== null)
		{
			$obj->{$options['option.attr']} = $options['attr'];
		}

		// Set disable only if it has a property and a value
		if ($options['disable'] !== null)
		{
			$obj->{$options['option.disable']} = $options['disable'];
		}

		return $obj;
	}

	/**
	 * Generates the option tags for an HTML select list (with no select tag
	 * surrounding the options).
	 *
	 * @param   array              $arr        An array of objects, arrays, or values.
	 * @param   string|array       $optKey     If a string, this is the name of the object variable for
	 *                                         the option value. If null, the index of the array of objects is used. If
	 *                                         an array, this is a set of options, as key/value pairs. Valid options
	 *                                         are:
	 *                                         -Format options,
	 *                                         -list.select: either the value of one selected option or an array
	 *                                         of selected options. Default: none.
	 *                                         -list.translate: Boolean. If set, text and labels are translated via
	 *                                         Text::_(). Default is false.
	 *                                         -option.id: The property in each option array to use as the
	 *                                         selection id attribute. Defaults to none.
	 *                                         -option.key: The property in each option array to use as the
	 *                                         selection value. Defaults to "value". If set to null, the index of the
	 *                                         option array is used.
	 *                                         -option.label: The property in each option array to use as the
	 *                                         selection label attribute. Defaults to null (none).
	 *                                         -option.text: The property in each option array to use as the
	 *                                         displayed text. Defaults to "text". If set to null, the option array is
	 *                                         assumed to be a list of displayable scalars.
	 *                                         -option.attr: The property in each option array to use for
	 *                                         additional selection attributes. Defaults to none.
	 *                                         -option.disable: The property that will hold the disabled state.
	 *                                         Defaults to "disable".
	 *                                         -option.key: The property that will hold the selection value.
	 *                                         Defaults to "value".
	 *                                         -option.text: The property that will hold the the displayed text.
	 *                                         Defaults to "text". If set to null, the option array is assumed to be a
	 *                                         list of displayable scalars.
	 * @param   string             $optText    The name of the object variable for the option text.
	 * @param   string|array|null  $selected   The key that is selected (accepts an array or a string)
	 * @param   boolean            $translate  Translate the option values.
	 *
	 * @return  string  HTML for the select list
	 */
	public function options(
		array $arr, $optKey = 'value', string $optText = 'text', $selected = null, bool $translate = false
	): string
	{
		$options = array_merge(
			$this->getContainer()->html->getFormatOptions(),
			$this->optionDefaults['option'],
			['format.depth' => 0, 'groups' => true, 'list.select' => null, 'list.translate' => false]
		);

		if (is_array($optKey))
		{
			// Set default options and overwrite with anything passed in
			$options = array_merge($options, $optKey);
		}
		else
		{
			// Get options from the parameters
			$options['option.key']     = $optKey;
			$options['option.text']    = $optText;
			$options['list.select']    = $selected;
			$options['list.translate'] = $translate;
		}

		$html       = '';
		$baseIndent = str_repeat($options['format.indent'], $options['format.depth']);

		foreach ($arr as $elementKey => &$element)
		{
			$attr  = '';
			$extra = '';
			$label = '';
			$id    = '';

			if (is_array($element))
			{
				$key  = $options['option.key'] === null ? $elementKey : $element[$options['option.key']];
				$text = $element[$options['option.text']];

				if (isset($element[$options['option.attr']]))
				{
					$attr = $element[$options['option.attr']];
				}

				if (isset($element[$options['option.id']]))
				{
					$id = $element[$options['option.id']];
				}

				if (isset($element[$options['option.label']]))
				{
					$label = $element[$options['option.label']];
				}

				if (isset($element[$options['option.disable']]) && $element[$options['option.disable']])
				{
					$extra .= ' disabled="disabled"';
				}
			}
			elseif (is_object($element))
			{
				$key  = $options['option.key'] === null ? $elementKey : $element->{$options['option.key']};
				$text = $element->{$options['option.text']};

				if (isset($element->{$options['option.attr']}))
				{
					$attr = $element->{$options['option.attr']};
				}

				if (isset($element->{$options['option.id']}))
				{
					$id = $element->{$options['option.id']};
				}

				if (isset($element->{$options['option.label']}))
				{
					$label = $element->{$options['option.label']};
				}

				if (isset($element->{$options['option.disable']}) && $element->{$options['option.disable']})
				{
					$extra .= ' disabled="disabled"';
				}
			}
			else
			{
				// This is a simple associative array
				$key  = $elementKey;
				$text = $element;
			}

			/*
			 * The use of options that contain optgroup HTML elements was
			 * somewhat hacked for J1.5. J1.6 introduces the grouplist() method
			 * to handle this better. The old solution is retained through the
			 * "groups" option, which defaults true in J1.6, but should be
			 * deprecated at some point in the future.
			 */

			$key = (string) $key;

			// If no string after hyphen - take hyphen out
			$splitText = preg_split('/ -[\s]*/', $text, 2, PREG_SPLIT_NO_EMPTY);
			$text      = isset($splitText[0]) ? $splitText[0] : '';

			if (!empty($splitText[1]))
			{
				$text .= ' - ' . $splitText[1];
			}

			if ($options['list.translate'] && !empty($label))
			{
				$label = $this->getContainer()->language->text($label);
			}

			if ($options['option.label.toHtml'])
			{
				$label = htmlentities($label);
			}

			if (is_array($attr))
			{
				$attr = ArrayHelper::toString($attr);
			}
			else
			{
				$attr = trim($attr);
			}

			$extra = ($id ? ' id="' . $id . '"' : '') . ($label ? ' label="' . $label . '"' : '') . ($attr ? ' ' . $attr
					: '') . $extra;

			if (is_array($options['list.select']))
			{
				foreach ($options['list.select'] as $val)
				{
					$key2 = is_object($val) ? $val->{$options['option.key']} : $val;

					if ($key == $key2)
					{
						$extra .= ' selected="selected"';
						break;
					}
				}
			}
			elseif ((string) $key == (string) $options['list.select'])
			{
				$extra .= ' selected="selected"';
			}

			if ($options['list.translate'])
			{
				$text = $this->getContainer()->language->text($text);
			}

			// Generate the option, encoding as required
			$html .= $baseIndent . '<option value="' . ($options['option.key.toHtml'] ? htmlspecialchars(
					$key ?? '', ENT_COMPAT, 'UTF-8'
				) : $key) . '"'
			         . $extra . '>';
			$html .= $options['option.text.toHtml'] ? htmlentities(
				html_entity_decode($text, ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8'
			) : $text;
			$html .= '</option>' . $options['format.eol'];

		}

		return $html;
	}

	/**
	 * Generates an HTML radio list.
	 *
	 * @param   array              $data       An array of objects
	 * @param   string             $name       The value of the HTML name attribute
	 * @param   array|null         $attribs    Additional HTML attributes for the <select> tag, or the following
	 *                                         - inline: boolean Create the radio list as inline elements
	 *                                         - radioType: radio|checkbox Use radio buttons (radio) or checkboxes
	 *                                         (checkbox)
	 * @param   string             $optKey     The key that is selected
	 * @param   string             $optText    The name of the object variable for the option value
	 * @param   string|null|array  $selected   The name of the object variable for the option text
	 * @param   bool|null|string   $idTag      Value of the field id or null by default
	 * @param   boolean            $translate  True if options will be translated
	 *
	 * @return  string  HTML for the select list
	 */
	public function radioList(
		array $data, string $name, array $attribs = null, string $optKey = 'value', string $optText = 'text',
		$selected = null, $idTag = false, bool $translate = false
	): string
	{
		reset($data);

		$inline    = false;
		$button    = false;
		$radioType = 'radio';

		$attribs = $attribs ?: [];

		if (isset($attribs['inline']))
		{
			$inline = $attribs['inline'];
			unset($attribs['inline']);
		}

		if (isset($attribs['radioType']))
		{
			$radioType = $attribs['radioType'];

			if (!in_array($radioType, ['radio', 'checkbox']))
			{
				$radioType = 'radio';
			}

			unset($attribs['radioType']);
		}

		if (isset($attribs['button']))
		{
			$button = $attribs['button'];
			unset($attribs['button']);
		}

		if (is_array($attribs))
		{
			$attribs = ArrayHelper::toString($attribs);
		}

		$id_text = $idTag ? $idTag : $name;

		$html = '';

		if ($button)
		{
			$html .= '<div class="btn-group" data-toggle="buttons">';
		}

		foreach ($data as $obj)
		{
			$k  = $obj->$optKey;
			$t  = $translate ? $this->getContainer()->language->text($obj->$optText) : $obj->$optText;
			$id = (isset($obj->id) ? $obj->id : null);

			$extra = '';
			$id    = $id ? $obj->id : $id_text . $k;

			if (is_array($selected))
			{
				foreach ($selected as $val)
				{
					$k2 = is_object($val) ? $val->$optKey : $val;

					if ($k == $k2)
					{
						if ($radioType == 'radio')
						{
							$extra .= ' selected="selected" ';
						}
						else
						{
							$extra .= ' checked="checked" ';
						}
						break;
					}
				}
			}
			else
			{
				if ($radioType == 'radio')
				{
					$extra .= ((string) $k == (string) $selected ? ' checked="checked" ' : '');
				}
				else
				{
					$extra .= ((string) $k == (string) $selected ? ' selected="selected" ' : '');
				}
			}

			if (!$inline && !$button)
			{
				$html .= "\n<div class=\"$radioType\">\n";
			}

			$class = '';

			if ($inline)
			{
				$class = ' class="' . $radioType . '-inline"';
			}
			elseif ($button)
			{
				$class = ' class="btn btn-default"';
			}

			$html .= "\n\t" . '<label' . $class . '>';
			$html .= "\n\t\n\t" . '<input type="' . $radioType . '" name="' . $name . '" id="' . $id . '" value="' . $k
			         . '" ' . $extra
			         . $attribs . ' >' . $t;
			$html .= "\n\t" . '</label>';

			if (!$inline && !$button)
			{
				$html .= "\n</div>\n";
			}
		}

		if ($button)
		{
			$html .= '</div>';
		}

		return $html;
	}
}
