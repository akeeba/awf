<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Session;

class MockSessionHandler
{
	public $data;

	public function close()
	{
		return true;
	}

	public function destroy($session_id)
	{
		$this->data = null;
		return true;
	}

	public function gc($maxlifetime)
	{
		return true;
	}

	public function open($save_path, $session_id)
	{
		return true;
	}

	public function read($session_id)
	{
		return $this->data;
	}

	public function write($session_id, $session_data)
	{
		$this->data = $session_data;
	}
} 
