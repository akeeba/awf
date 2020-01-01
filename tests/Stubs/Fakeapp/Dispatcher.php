<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Fakeapp;


class Dispatcher extends \Awf\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'jasager';

	/** @var bool|\Exception  */
	public static $onBeforeDispatchResult = true;

	/** @var bool|\Exception  */
	public static $onAfterDispatchResult = true;

	public function onBeforeDispatch()
	{
		$result = parent::onBeforeDispatch();

		if ($result)
		{
			if (!is_bool(self::$onBeforeDispatchResult))
			{
				throw self::$onBeforeDispatchResult;
			}

			$result = self::$onBeforeDispatchResult;
		}

		return $result;
	}

	public function onAfterDispatch()
	{
		$result = parent::onAfterDispatch();

		if ($result)
		{
			if (!is_bool(self::$onAfterDispatchResult))
			{
				throw self::$onAfterDispatchResult;
			}

			$result = self::$onAfterDispatchResult;
		}

		return $result;
	}
}
