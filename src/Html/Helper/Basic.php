<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html\Helper;

use Awf\Exception\App;
use Awf\Html\AbstractHelper;
use Awf\Uri\Uri;
use Awf\Utils\ArrayHelper;

/**
 * Generic HTML output abstraction class
 *
 * This class is based on the JHtml package of Joomla! 3 but heavily modified
 *
 * @since 1.1.0
 */
class Basic extends AbstractHelper
{
	/**
	 * Set new format options
	 *
	 * @param   array  $options  The new format options to set
	 *
	 * @deprecated 2.0 Use the html service in the container
	 * @return  void
	 */
	public function setFormatOptions(array $options): void
	{
		trigger_error(
			sprintf('Do not call method %s; use the html service in the container instead.', __METHOD__),
			E_USER_DEPRECATED
		);
	}

	/**
	 * Returns an anchor element
	 *
	 * @param   string      $url      The relative URL to use for the href attribute
	 * @param   string      $text     The target attribute to use
	 * @param   array|null  $attribs  An associative array of attributes to add
	 *
	 * @return  string  Anchor element
	 */
	public function link(string $url, string $text, ?array $attribs = null): string
	{
		if (is_array($attribs))
		{
			$attribs = ArrayHelper::toString($attribs);
		}

		return '<a href="' . $url . '" ' . $attribs . '>' . $text . '</a>';
	}

	/**
	 * Write an IFRAME element
	 *
	 * @param   string      $url       The relative URL to use for the src attribute.
	 * @param   string      $name      The target attribute to use.
	 * @param   array|null  $attribs   An associative array of attributes to add.
	 * @param   string      $noFrames  The message to display if the iframe tag is not supported.
	 *
	 * @return  string  IFRAME element
	 */
	public function iframe(string $url, string $name, ?array $attribs = null, string $noFrames = '')
	{
		if (is_array($attribs))
		{
			$attribs = ArrayHelper::toString($attribs);
		}

		return '<iframe src="' . $url . '" ' . $attribs . ' name="' . $name . '">' . $noFrames . '</iframe>';
	}

	/**
	 * Returns formatted date according to a given format and time zone.
	 *
	 * @param   string                     $input   String in a format accepted by date(), defaults to "now".
	 * @param   string|null                $format  The date format specification string (see {@link PHP_MANUAL#date}).
	 * @param   bool|string|\DateTimeZone  $tz      Time zone to be used for the date.  Special cases: boolean true for
	 *                                              user setting, boolean false for server setting.
	 *
	 * @return  string    A date translated by the given format and time zone.
	 *
	 * @throws  App
	 * @see     strftime
	 */
	public function date(string $input = 'now', ?string $format = null, $tz = true): string
	{
		$container = $this->getContainer();

		// Get some system objects.
		$config      = $container->appConfig;
		$userManager = $container->userManager;
		$user        = $userManager->getUser();

		// UTC date converted to user time zone.
		if ($tz === true)
		{
			// Get a date object based on UTC.
			$date = $container->dateFactory($input, 'UTC');

			// Set the correct time zone based on the user configuration.
			$date->setTimeZone(new \DateTimeZone($user->getParameters()->get('timezone', $config->get('timezone'))));
		}
		// UTC date converted to server time zone.
		elseif ($tz === false)
		{
			// Get a date object based on UTC.
			$date = $container->dateFactory($input, 'UTC');

			// Set the correct time zone based on the server configuration.
			$date->setTimeZone(new \DateTimeZone($config->get('timezone', 'UTC')));
		}
		// No date conversion.
		elseif ($tz === null)
		{
			$date = $container->dateFactory($input);
		}
		// UTC date converted to given time zone.
		else
		{
			// Get a date object based on UTC.
			$date = $container->dateFactory($input, 'UTC');

			// Set the correct time zone based on the server configuration.
			$date->setTimeZone(new \DateTimeZone($tz));
		}

		// If no format is given use the default locale based format.
		if (!$format)
		{
			$format = $this->getContainer()->language->text('DATE_FORMAT_LC1');
		}
		// $format is an existing language key
		elseif ($this->getContainer()->language->hasKey($format))
		{
			$format = $this->getContainer()->language->text($format);
		}

		return $date->format($format, true);
	}

	/**
	 * Write an IMG element
	 *
	 * @param   string             $file      The relative or absolute URL to use for the src attribute.
	 * @param   string             $alt       The alt text.
	 * @param   array|string|null  $attribs   String or associative array of attribute(s) to use.
	 * @param   boolean            $relative  Path to file is relative to /media folder
	 *
	 * @return  string
	 * @throws  App
	 */
	public function image(string $file, string $alt, $attribs = null, bool $relative = false): string
	{
		if ($relative)
		{
			$file = Uri::base(false, $this->getContainer()) . 'media/' . ltrim($file, '/');
		}

		return '<img src="' . $file . '" alt="' . $alt . '" '
		       . trim((is_array($attribs) ? ArrayHelper::toString($attribs) : $attribs) . ' /')
		       . '>';
	}

	/**
	 * Creates a tooltip with an image as button
	 *
	 * @param   string        $tooltip      The tip string.
	 * @param   string|array  $title        The title of the tooltip or an associative array with keys contained in
	 *                                      {'title','image','text','href','alt'} and values corresponding to
	 *                                      parameters of the same name.
	 * @param   string        $image        The image for the tip, if no text is provided.
	 * @param   string        $text         The text for the tip.
	 * @param   string        $href         An URL that will be used to create the link.
	 * @param   string        $alt          The alt attribute for img tag.
	 * @param   string        $class        CSS class for the tool tip.
	 *
	 * @return  string
	 * @throws  App
	 */
	public function tooltip(
		string $tooltip,
		$title = '',
		string $image = 'images/tooltip.png',
		string $text = '',
		string $href = '',
		string $alt = 'Tooltip',
		string $class = 'hasTooltip'
	): string
	{
		if (is_array($title))
		{
			foreach (['image', 'text', 'href', 'alt', 'class'] as $param)
			{
				if (isset($title[$param]))
				{
					${$param} = $title[$param];
				}
			}

			$title = $title['title'] ?? '';
		}

		if (!$text)
		{
			$alt  = htmlspecialchars($alt ?? '', ENT_COMPAT, 'UTF-8');
			$text = $this->image($image, $alt, null, true);
		}

		if ($href)
		{
			$tip = '<a href="' . $href . '">' . $text . '</a>';
		}
		else
		{
			$tip = $text;
		}

		$tooltip = $this->tooltipText($title, $tooltip, false);

		return '<span class="' . $class . '" title="' . $tooltip . '">' . $tip . '</span>';
	}

	/**
	 * Converts a double colon separated string or 2 separate strings to a string ready for bootstrap tooltips
	 *
	 * @param   string  $title      The title of the tooltip (or combined '::' separated string).
	 * @param   string  $content    The content to tooltip.
	 * @param   bool    $translate  If true will pass texts through Text.
	 * @param   bool    $escape     If true will pass texts through htmlspecialchars.
	 *
	 * @return  string  The tooltip string
	 */
	public function tooltipText(string $title = '', string $content = '', bool $translate = true, bool $escape = true
	): string
	{
		// Return empty in no title or content is given.
		if ($title == '' && $content == '')
		{
			return '';
		}

		// Split title into title and content if the title contains '::' (migrated Joomla! code, using the obsolete Mootools format).
		if ($content == '' && !(strpos($title, '::') === false))
		{
			[$title, $content] = explode('::', $title, 2);
		}

		// Pass strings through Text.
		if ($translate)
		{
			$title   = $this->getContainer()->language->text($title);
			$content = $this->getContainer()->language->text($content);
		}

		// Escape the strings.
		if ($escape)
		{
			$title   = str_replace('"', '&quot;', $title);
			$content = str_replace('"', '&quot;', $content);
		}

		// Return only the content if no title is given.
		if ($title == '')
		{
			return $content;
		}

		// Return only the title if title and text are the same.
		if ($title == $content)
		{
			return '<strong>' . $title . '</strong>';
		}

		// Return the formatted sting combining the title and  content.
		if ($content != '')
		{
			return '<strong>' . $title . '</strong><br />' . $content;
		}

		// Return only the title.
		return $title;
	}

	/**
	 * Displays a calendar control field
	 *
	 * This field can use either jQuery DatePicker (default) or Pikaday. This is controlled by the application
	 * Container, namely its awf_date_picker field. It can be either "jQuery" or "Pikaday".
	 *
	 * @param   string  $value    The date value
	 * @param   string  $name     The name of the text field
	 * @param   string  $id       The id of the text field
	 * @param   string  $format   The date format
	 * @param   null    $attribs  Additional HTML attributes
	 *
	 * @return  string  HTML markup for a calendar field
	 * @throws  App
	 */
	public function calendar(
		string $value, string $name, string $id, string $format = 'yyyy-mm-dd', ?array $attribs = []
	): string
	{
		static $done = [];

		$attribs = $attribs ?: [];

		$attribs['class'] = $attribs['class'] ?? 'form-control';
		$attribs['class'] = trim($attribs['class'] . ' hasTooltip calendar');

		$readonly = ($attribs['readonly'] ?? null) == 'readonly';
		$disabled = ($attribs['disabled'] ?? null) == 'disabled';

		$attribs = ArrayHelper::toString($attribs);

		if (!$readonly && !$disabled)
		{
			// Load the calendar behavior
			$this->getContainer()->html->run('behaviour.calendar');

			// Only display the triggers once for each control.
			if (!in_array($id, $done))
			{
				// Do I use jQuery date picker or Pikaday?
				$container  = $this->getContainer();
				$pickerType = $container['awf_date_picker'] ?? 'jQuery';
				$pickerType = !in_array($pickerType, ['jQuery', 'Pikaday']) ? 'jQuery' : $pickerType;

				// @todo Implement a way for the application to override the language
				$lang = $this->getContainer()->application->getLanguage()->getLangCode();

				$document = $this->getContainer()->application->getDocument();

				if ($pickerType === 'jQuery')
				{
					$document
						->addScriptDeclaration(
							<<< JS
akeeba.jQuery(document).ready(function(){
	akeeba.jQuery('#$id-container').datepicker({
		format: "$format",
		todayBtn: "linked",
		language: "$lang",
		autoclose: true
	});
})
JS

						);
				}
				else
				{
					$document
						->addScriptDeclaration(
							<<< JS
akeeba.System.documentReady(function() {
   new Pikaday({
   		field: document.getElementById('$id-container'),
   		format: "$format",
   }); 
});
JS
						);

				}
				$done[] = $id;
			}

			return '<div class="input-group date" id="' . $id . '-container"><input type="text" title="' .
			       (0 !== (int) $value ? $this->date($value, null, null) : '')
			       . '" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars(
				       $value ?? '', ENT_COMPAT, 'UTF-8'
			       ) . '" ' . $attribs . ' />'
			       . '<span class="input-group-btn" id="' . $id
			       . '_img"><span class="btn btn-default"><span class="glyphicon glyphicon-calendar"></span></span></span></div>';
		}
		else
		{
			return '<input type="text" title="' . (0 !== (int) $value ? $this->date($value, null, null) : '')
			       . '" value="' . (0 !== (int) $value ? $this->date($value, 'Y-m-d H:i:s', null) : '') . '" '
			       . $attribs
			       . ' /><input type="hidden" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars(
				       $value ?? '', ENT_COMPAT, 'UTF-8'
			       ) . '" />';
		}
	}
} 
