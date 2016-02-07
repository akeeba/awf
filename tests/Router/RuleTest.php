<?php
/**
 * @package        awf
 * @copyright      2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Router;

use Awf\Router\Rule;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Uri\Uri;

/**
 * Class RuleTest
 *
 * @package Awf\Tests\Router
 *
 * @coversDefaultClass \Awf\Router\Rule
 */
class RuleTest extends \PHPUnit_Framework_TestCase
{
	public function testSetParseCallable()
	{
		$def = array('matchVars' => array('foo' => 'bar'));
		$rule = new Rule($def);

		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForParse'));

		$rule->setParseCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForParse'));

		$rule->setParseCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForParse'));

		$rule->setParseCallable('foobar');
		$this->assertTrue(ReflectionHelper::getValue($rule, 'useCallableForParse'));

		$rule->setParseCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForParse'));

		$rule->setParseCallable(array($this, 'something'));
		$this->assertTrue(ReflectionHelper::getValue($rule, 'useCallableForParse'));

		$rule->setParseCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForParse'));
	}

	public function testSetRouteCallable()
	{
		$def = array('matchVars' => array('foo' => 'bar'));
		$rule = new Rule($def);

		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForRoute'));

		$rule->setRouteCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForRoute'));

		$rule->setRouteCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForRoute'));

		$rule->setRouteCallable('foobar');
		$this->assertTrue(ReflectionHelper::getValue($rule, 'useCallableForRoute'));

		$rule->setRouteCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForRoute'));

		$rule->setRouteCallable(array($this, 'something'));
		$this->assertTrue(ReflectionHelper::getValue($rule, 'useCallableForRoute'));

		$rule->setRouteCallable(null);
		$this->assertFalse(ReflectionHelper::getValue($rule, 'useCallableForRoute'));
	}

	/**
	 * @dataProvider getTestRoute
	 *
	 * @param array  $routeDef
	 * @param string $url
	 * @param string $expected
	 * @param string $msg
	 */
	public function testRoute(array $routeDef, $url, $expected, $msg)
	{
		$rule = new Rule($routeDef);

		$reverse = true;

		if (is_array($expected) && isset($expected['reverse']))
		{
			$reverse = $expected['reverse'];
			unset($expected['reverse']);
		}
		elseif (is_null($expected))
		{
			$reverse = false;
		}

		// Test route construction
		$actual = $rule->route($url);
		$this->assertEquals($expected, $actual, $msg);

		// Test route parsing, if possible
		if (is_array($actual) && $reverse)
		{
			// Get the path to parse
			$uri = new Uri(implode('/', $actual['segments']));

			if (!empty($actual['vars']))
			{
				foreach ($actual['vars'] as $k => $v)
				{
					$uri->setVar($k, $v);
				}
			}

			$path = $uri->toString(array('path', 'query'));

			// Get the original path's variables
			$oldUri = new Uri($url);
			$oldVars = $oldUri->getQuery(true);

			// Parse the path
			$actual = $rule->parse($path);

			if ($reverse === 'fail')
			{
				$this->assertNull($actual, $msg);
			}
			else
			{
				$this->assertInternalType('array', $actual, $msg);

				foreach ($oldVars as $k => $v)
				{
					$this->assertArrayHasKey($k, $actual, $msg);
					$this->assertEquals($actual[$k], $v, $msg);
				}
			}
		}
	}

	public function getTestRoute()
	{
		$definitions = array(
			'simple' => array(
				'ruleMsg'   => 'Simple rule matching a specific view and task',
				'path'      => 'foo/show/:id',
				'matchVars' => array('view' => 'foo', 'task' => 'read'),
				'pushVars'	=> array('view' => 'foo', 'task' => 'read'),
				'types'		=> array('id' => '#\d#')
			),
			'optionalArg' => array(
				'ruleMsg'   => 'Simple rule matching a specific view and task with an optional argument',
				'path'      => 'foo/show/:id/:gender?',
				'types'		=> array('id' => '#\d#', 'gender' => '#(m|f|na)#i'),
				'matchVars' => array('view' => 'foo', 'task' => 'read'),
				'pushVars'	=> array('view' => 'foo', 'task' => 'read'),
			),
			'arrayVar' => array(
				'ruleMsg'   => 'A rule with array variables',
				'path'      => 'foo/grok/:id*/:action?',
				'types'		=> array('id' => '#\d#', 'action' => '#(kot|lol)#i'),
				'matchVars' => array('view' => 'foo', 'task' => 'grok'),
				'pushVars'	=> array('view' => 'foo', 'task' => 'grok'),
			),
		);

		return array(
			array(
				$definitions['arrayVar'],
				'index.php?view=foo&task=grok&id[]=1&id[]=2&id[]=3&action=kot',
				array(
					'segments' => array('foo', 'grok', 1, 2, 3, 'kot'),
					'vars'     => array()
				),
				'A rule with array variables, with array argument'
			),

			// =========================================================================================================
			array(
				$definitions['simple'],
				'index.php?view=foo&task=read&id=1',
				array(
					'segments' => array('foo', 'show', 1),
					'vars'     => array()
				),
				'Simple rule, matching'
			),

			array(
				$definitions['simple'],
				'index.php?view=foo&task=read&id=1&gender=m',
				array(
					'segments' => array('foo', 'show', 1),
					'vars'     => array('gender' => 'm')
				),
				'Simple rule, matching, unhandled variables'
			),

			array(
				$definitions['simple'],
				'index.php?view=foo&task=read&id=1&gender=z',
				array(
					'segments' => array('foo', 'show', 1),
					'vars'     => array('gender' => 'z'),
					'reverse'  => false
				),
				'Simple rule, matching, unhandled variables'
			),

			array(
				$definitions['simple'],
				'index.php?view=foo&task=read',
				null,
				'Simple rule, missing required parameter'
			),

			array(
				$definitions['simple'],
				'index.php?view=baz&task=read',
				null,
				'Simple rule, match var has different value'
			),
			// =========================================================================================================
			array(
				$definitions['optionalArg'],
				'index.php?view=foo&task=read&id=1',
				array(
					'segments' => array('foo', 'show', 1),
					'vars'     => array()
				),
				'Simple rule with optional parameter, matching, without optional parameter'
			),

			array(
				$definitions['optionalArg'],
				'index.php?view=foo&task=read&id=1&gender=m',
				array(
					'segments' => array('foo', 'show', 1, 'm'),
					'vars'     => array()
				),
				'Simple rule with optional parameter, matching, with optional parameter'
			),

			array(
				$definitions['optionalArg'],
				'index.php?view=foo&task=read&id=1&foo=bar',
				array(
					'segments' => array('foo', 'show', 1),
					'vars'     => array('foo' => 'bar')
				),
				'Simple rule with optional parameter, matching, unhandled variables'
			),

			array(
				$definitions['optionalArg'],
				'index.php?view=foo&task=read',
				null,
				'Simple rule with optional parameter, missing required parameter'
			),

			array(
				$definitions['optionalArg'],
				'index.php?view=baz&task=read',
				null,
				'Simple rule with optional parameter, match var has different value'
			),
			// =========================================================================================================

		);
	}
}
 