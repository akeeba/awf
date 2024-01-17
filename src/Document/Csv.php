<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Document;
use Awf\Container\Container;
use Awf\Text\Language;

/**
 * Class Json
 *
 * A JSON output implementation
 *
 * @package Awf\Document
 */
class Csv extends Document
{
	public function __construct(Container $container, ?Language $language = null)
	{
		parent::__construct($container, $language);

		$this->mimeType = 'text/csv';
	}

	/**
	 * Outputs the buffer, which is assumed to contain CSV data, to the
	 * browser.
	 *
	 * @return  void
	 */
	public function render()
	{
		$this->addHTTPHeader('Content-Type', $this->getMimeType());

		$name = $this->getName();

		if (!empty($name))
		{
			$this->addHTTPHeader('Content-Disposition', 'attachment; filename="' . $name . '.csv"', true);
		}

		$this->outputHTTPHeaders();

        echo $this->getBuffer();
	}
}
