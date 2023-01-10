<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataView;

use Awf\Mvc\DataModel;
use Awf\Text\Text;

class Json extends Raw
{
	/**
	 * Set to true if your onBefore* methods have already populated the item, items, limitstart etc properties used to
	 * render a JSON document.
	 *
	 * @var bool
	 */
	public $alreadyLoaded = false;

	/**
	 * Overrides the default method to execute and display a template script.
	 * Instead of loadTemplate is uses loadAnyTemplate.
	 *
	 * @param   string  $tpl  The name of the template file to parse
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  \Exception  When the layout file is not found
	 */
	public function display($tpl = null)
	{
		$method = 'onBefore' . ucfirst($this->doTask);
		if (method_exists($this, $method))
		{
			$result = $this->$method($tpl);

			if (!$result)
			{
				throw new \Exception(Text::_('AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
			}
		}

		$method = 'onAfter' . ucfirst($this->doTask);
		if (method_exists($this, $method))
		{
			$result = $this->$method($tpl);

			if (!$result)
			{
				throw new \Exception(Text::_('AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
			}
		}

		return true;
	}

	/**
	 * The event which runs when we are displaying the record list JSON view
	 *
	 * @param   string  $tpl  The sub-template to use
	 *
	 * @return  boolean  True to allow display of the view
	 */
	public function onBeforeBrowse($tpl = null)
	{
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		if (!$this->alreadyLoaded)
		{
			$this->limitStart = $model->getState('limitstart', 0);
			$this->limit = $model->getState('limit', 0);
			$this->items = $model->getItemsArray($this->limitStart, $this->limit);
			$this->total = $model->count();
		}

		$document = $this->container->application->getDocument();

		if ($document instanceof \Awf\Document\Json)
		{
			$document->setUseHashes(false);

			$document->setMimeType('application/json');
		}

		if (is_null($tpl))
		{
			$tpl = 'json';
		}

		$hasFailed = false;

		try
		{
			$result = $this->loadTemplate($tpl, true);

			if ($result instanceof \Exception)
			{
				$hasFailed = true;
			}
		}
		catch (\Exception $e)
		{
			$hasFailed = true;
		}

		if ($hasFailed)
		{
			// Default JSON behaviour in case the template isn't there!
            $result = array();

            foreach($this->items as $item)
            {
                if(is_object($item) && method_exists($item, 'toArray'))
                {
                    $result[] = $item->toArray();
                }
                else
                {
                    $result[] = $item;
                }
            }

			$json = json_encode($result);

			// JSONP support
			$callback = $this->input->get('callback', null, 'raw');

			if (!empty($callback))
			{
				echo $callback . '(' . $json . ')';
			}
			else
			{
				$defaultName = $this->input->get('view', 'main', 'cmd');
				$filename = $this->input->get('basename', $defaultName, 'cmd');

				$document->setName($filename);
				echo $json;
			}
		}
		else
		{
			echo $result;
		}

		return true;
	}

	/**
	 * The event which runs when we are displaying a single item JSON view
	 *
	 * @param   string  $tpl  The view sub-template to use
	 *
	 * @return  boolean  True to allow display of the view
	 */
	protected function onBeforeRead($tpl = null)
	{
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		if (!$this->alreadyLoaded)
		{
			$this->item = $model->find();
		}


		$document = $this->container->application->getDocument();

		if ($document instanceof \Awf\Document\Json)
		{
			$document->setUseHashes(false);

			$document->setMimeType('application/json');
		}

		if (is_null($tpl))
		{
			$tpl = 'json';
		}

		$hasFailed = false;

		try
		{
			$result = $this->loadTemplate($tpl, true);

			if ($result instanceof \Exception)
			{
				$hasFailed = true;
			}
		}
		catch (\Exception $e)
		{
			$hasFailed = true;
		}

		if ($hasFailed)
		{
			// Default JSON behaviour in case the template isn't there!

            if(is_object($this->item) && method_exists($this->item, 'toArray'))
            {
                $json = json_encode($this->item->toArray());
            }
            else
            {
                $json = json_encode($this->item);
            }

			// JSONP support
			$callback = $this->input->get('callback', null);

			if (!empty($callback))
			{
				echo $callback . '(' . $json . ')';
			}
			else
			{
				$defaultName = $this->input->get('view', 'main', 'cmd');
				$filename = $this->input->get('basename', $defaultName, 'cmd');
				$document->setName($filename);

				echo $json;
			}
		}
		else
		{
			echo $result;
		}

		return true;
	}
}
