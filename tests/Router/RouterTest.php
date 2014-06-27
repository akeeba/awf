<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Router;

use Awf\Router\Router;
use Awf\Tests\Helpers\ApplicationTestCase;
use Awf\Uri\Uri;

/**
 * Class RouterTest
 *
 * @package Awf\Tests\Router
 *
 * @coversDefaultClass \Awf\Router\Router
 */
class RouterTest extends ApplicationTestCase
{

	/** @var Router The object under test */
	protected $router = null;

	protected function setUp()
	{
		$this->router = static::$container->router;
		$this->router->clearRules();
		$this->router->addRuleFromDefinition(array(
			'path'      => 'foo/show/:id/:gender?',
			'matchVars' => array('view' => 'foo', 'task' => 'read'),
			'pushVars'  => array('view' => 'foo', 'task' => 'read'),
			'types'     => array('id' => '#\d#', 'gender' => '#(m|f|na)#i')
		));
		$this->router->addRuleFromDefinition(array(
			'path'      => 'foo/grok/:id*/:action?',
			'types'		=> array('id' => '#\d#', 'action' => '#(kot|lol)#i'),
			'matchVars' => array('view' => 'foo', 'task' => 'grok'),
			'pushVars'	=> array('view' => 'foo', 'task' => 'grok'),
		));
	}

	/**
	 * @dataProvider getTestRoute
	 *
	 * @param string $url
	 * @param bool   $rebase
	 * @param string $expected
	 */
	public function testRoute($url, $rebase, $expected)
	{
		$actual = $this->router->route($url, $rebase);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider getTestParse
	 *
	 * @param string $url
	 * @param bool   $rebase
	 * @param string $expected
	 */
	public function testParse($url, $rebase, $expected)
	{
		static::$container->input->setData(array());
		$this->router->parse($url, $rebase);

		$uri = new Uri($expected);
		$data = $uri->getQuery(true);

		foreach ($data as $k => $v)
		{
			$actual = static::$container->input->get($k, null, 'raw');
			$this->assertEquals($v, $actual, $url);
		}
	}

	public function getTestRoute()
	{
		return array(
			// No rule matches, rebase
			array(
				'index.php?view=baz&foo=bar', true, 'http://www.example.com/index.php?view=baz&foo=bar'
			),
			// No rule matches, no rebase
			array(
				'index.php?view=baz&foo=bar', false, 'index.php?view=baz&foo=bar'
			),
			// No rule matches, rebase, different hostname
			array(
				'http://www.example.net/index.php?view=baz&foo=bar', true, 'http://www.example.com/index.php?view=baz&foo=bar'
			),
			// No rule matches, no rebase, different hostname
			array(
				'http://www.example.net/index.php?view=baz&foo=bar', false, 'http://www.example.net/index.php?view=baz&foo=bar'
			),
			// Simple rule, missing optional argument, rebase
			array(
				'index.php?view=foo&task=read&id=123', true, 'http://www.example.com/foo/show/123'
			),
			// Simple rule, missing optional argument, custom path (always removed), rebase
			array(
				'/somewhere/index.php?view=foo&task=read&id=123', true, 'http://www.example.com/foo/show/123'
			),
			// Simple rule, missing optional argument, custom path (always removed), no rebase
			array(
				'/somewhere/index.php?view=foo&task=read&id=123', false, 'foo/show/123'
			),
			// Simple rule, missing optional argument, no rebase
			array(
				'index.php?view=foo&task=read&id=123', false, 'foo/show/123'
			),
			// Simple rule, with optional argument, rebase
			array(
				'index.php?view=foo&task=read&id=123&gender=m', true, 'http://www.example.com/foo/show/123/m'
			),
			// Array rule, without optional argument, rebase
			array(
				'index.php?view=foo&task=grok&id[]=123&id[]=456', true, 'http://www.example.com/foo/grok/123/456'
			),
			// Array rule, with optional argument, rebase
			array(
				'index.php?view=foo&task=grok&id[]=123&id[]=456&action=kot', true, 'http://www.example.com/foo/grok/123/456/kot'
			),
			// Array rule, with optional argument and unhandled parameter, rebase
			array(
				'index.php?view=foo&task=grok&id[]=123&id[]=456&action=kot&option=something', true, 'http://www.example.com/foo/grok/123/456/kot?option=something'
			),
		);
	}

	public function getTestParse()
	{
		return array(
			// No rule matches, rebase
			array(
				'http://www.example.com/index.php?view=baz&foo=bar', true, 'index.php?view=baz&foo=bar'
			),
			// No rule matches, no rebase
			array(
				'index.php?view=baz&foo=bar', false, 'index.php?view=baz&foo=bar'
			),
			// No rule matches, rebase, different hostname
			array(
				'http://www.example.com/index.php?view=baz&foo=bar', true, 'http://www.example.net/index.php?view=baz&foo=bar'
			),
			// No rule matches, no rebase, different hostname
			array(
				'http://www.example.net/index.php?view=baz&foo=bar', false, 'http://www.example.net/index.php?view=baz&foo=bar'
			),
			// Simple rule, missing optional argument, rebase
			array(
				'http://www.example.com/foo/show/123', true, 'index.php?view=foo&task=read&id=123'
			),
			// Simple rule, missing optional argument, custom path (always removed), rebase
			array(
				'http://www.example.com/foo/show/123', true, '/somewhere/index.php?view=foo&task=read&id=123'
			),
			// Simple rule, missing optional argument, custom path (always removed), no rebase
			array(
				'foo/show/123', false, '/somewhere/index.php?view=foo&task=read&id=123'
			),
			// Simple rule, missing optional argument, no rebase
			array(
				'foo/show/123', false, 'index.php?view=foo&task=read&id=123'
			),
			// Simple rule, with optional argument, rebase
			array(
				'http://www.example.com/foo/show/123/m', true, 'index.php?view=foo&task=read&id=123&gender=m'
			),
			// Array rule, without optional argument, rebase
			array(
				'http://www.example.com/foo/grok/123/456', true, 'index.php?view=foo&task=grok&id[]=123&id[]=456'
			),
			// Array rule, with optional argument, rebase
			array(
				'http://www.example.com/foo/grok/123/456/kot', true, 'index.php?view=foo&task=grok&id[]=123&id[]=456&action=kot'
			),
			// Array rule, with optional argument and unhandled parameter, rebase
			array(
				'http://www.example.com/foo/grok/123/456/kot?option=something', true, 'index.php?view=foo&task=grok&id[]=123&id[]=456&action=kot&option=something'
			),
		);
	}
}
