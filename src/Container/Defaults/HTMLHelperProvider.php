<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Html\Helper\Accordion as AccordionHtmlHelper;
use Awf\Html\Helper\Basic as BasicHtmlHelper;
use Awf\Html\Helper\Behaviour as BehaviourHtmlHelper;
use Awf\Html\Helper\Grid as GridHtmlHelper;
use Awf\Html\Helper\Select as SelectHtmlHelper;
use Awf\Html\Helper\Tabs as TabsHtmlHelper;
use Awf\Html\HtmlService as HtmlService;

/**
 * HTML Helper service provider
 *
 * @since   1.1.0
 */
class HTMLHelperProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  HtmlService  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): HtmlService
	{
		$service = new HtmlService($c);

		$service->registerHelperClass(AccordionHtmlHelper::class);
		$service->registerHelperClass(BasicHtmlHelper::class);
		$service->registerHelperClass(BehaviourHtmlHelper::class);
		$service->registerHelperClass(GridHtmlHelper::class);
		$service->registerHelperClass(SelectHtmlHelper::class);
		$service->registerHelperClass(TabsHtmlHelper::class);

		return $service;
	}

}