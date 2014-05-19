<?php
/**
 * @package     Awf
 * @copyright   2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

namespace Awf\Document;

/**
 * Class Html
 *
 * The HTML document implementation. Uses the defined template to render itself.
 *
 * @package Awf\Document
 */
class Html extends Document
{
	/**
	 * Uses the defined template to outputs the buffer to the browser using the
	 * defined template.
	 *
	 * @return  void
	 */

	public function render()
	{
		$this->addHTTPHeader('Content-Type', $this->getMimeType());

		$this->addHTTPHeader('Content-Type', $this->getMimeType());

		$name = $this->getName();

		if (!empty($name))
		{
			$this->addHTTPHeader('Content-disposition', 'attachment; filename="' . $name . '.html"', true);
		}

		$template = \Awf\Application\Application::getInstance()->getTemplate();
		$templatePath = APATH_THEMES . '/' . $template;

		include $templatePath . '/index.php';
	}
}