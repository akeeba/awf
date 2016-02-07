<?php
/**
 * @package        awf
 * @copyright      2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

class ViewDataprovider
{
    public function getTestGet()
    {
	    if (!defined('APATH_BASE'))
	    {
		    define('APATH_BASE', realpath(__DIR__ . '/../Stubs/Fakeapp'));
	    }

	    $_SERVER['HTTPS'] = 'off';
	    $_SERVER['HTTP_HOST'] = 'www.example.com';
	    $_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';

	    $container = \Awf\Application\Application::getInstance('Fakeapp')->getContainer();

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'foobars',
                    'instances' => array(
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub($container)
                    )
                ),
                'property' => 'foobar',
                'default'  => null,
                'model'    => null
            ),
            array(
                'case'   => 'Using default model, get<Property>() exists in the model',
                'result' => 'ok'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'foobars',
                    'instances' => array(
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub($container)
                    )
                ),
                'property' => 'dummy',
                'default'  => null,
                'model'    => null
            ),
            array(
                'case'   => 'Using default model, <Property>() exists in the model',
                'result' => 'ok'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'foobars',
                    'instances' => array(
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub($container)
                    )
                ),
                'property' => 'nothere',
                'default'  => 'default',
                'model'    => null
            ),
            array(
                'case'   => "Using default model, there isn't any method in the model",
                'result' => null
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'dummy',
                    'instances' => array(
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub($container)
                    )
                ),
                'property' => 'foobar',
                'default'  => null,
                'model'    => 'foobars'
            ),
            array(
                'case'   => 'Requesting for a specific model, get<Property>() exists in the model',
                'result' => 'ok'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'dummy',
                    'instances' => array(
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub($container)
                    )
                ),
                'property' => 'dummy',
                'default'  => null,
                'model'    => 'foobars'
            ),
            array(
                'case'   => 'Requesting for a specific model, <Property>() exists in the model',
                'result' => 'ok'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(
                        'key'   => 'foobar',
                        'value' => 'test'
                    ),
                    'defaultModel' => 'foobars',
                    'instances' => array()
                ),
                'property' => 'foobar',
                'default'  => 'default',
                'model'    => null
            ),
            array(
                'case'   => 'Model not found, getting (existing) view property',
                'result' => 'test'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'foobars',
                    'instances' => array()
                ),
                'property' => 'foobar',
                'default'  => 'default',
                'model'    => null
            ),
            array(
                'case'   => 'Model not found, getting (non-existing) view property',
                'result' => 'default'
            )
        );

        return $data;
    }

    public static function getTestGetModel()
    {
        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'name' => null,
                    'defaultModel' => null,
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name passed, model not cached, internal reference are empty',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array('foo' => 'bar'),
                'mock' => array(
                    'name' => null,
                    'defaultModel' => null,
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name and config passed, model not cached, internal reference are empty',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array(
                    'foo' => 'bar'
                )
            )
        );

        $data[] = array(
            array(
                'name' => null,
                'config' => array(),
                'mock' => array(
                    'name' => null,
                    'defaultModel' => 'foobar',
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name not passed, model not cached, using modelName property',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => null,
                'config' => array(),
                'mock' => array(
                    'name' => 'foobar',
                    'defaultModel' => null,
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name not passed, model not cached, using view property',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'name' => null,
                    'defaultModel' => null,
                    'instances' => array(
                        'foobar' => new \Awf\Tests\Stubs\Mvc\ModelStub()
                    )
                )
            ),
            array(
                'case'   => 'Name passed, fetching the model from the cache',
                'result' => '\\Awf\\Tests\\Stubs\\Mvc\\ModelStub',
                'config' => null
            )
        );

        return $data;
    }

    public static function getTestDisplay()
    {
        // No template, everything is going smooth
        $data[] = array(
            array(
                'mock' => array(
                    'doTask' => 'Foobar',
                    'before' => null,
                    'after'  => null,
                    'output' => 'test'
                ),
                'tpl' => null
            ),
            array(
                'case'      => 'No template, everything is going smooth',
                'output'    => 'test',
                'tpl'       => null,
                'exception' => false,
                'load'      => true,
                'before'    => array('counter' => 0, 'tpl' => null),
                'after'     => array('counter' => 0, 'tpl' => null),
            )
        );

        // With template, everything is going smooth
        $data[] = array(
            array(
                'mock' => array(
                    'doTask' => 'Foobar',
                    'before' => null,
                    'after'  => null,
                    'output' => 'test'
                ),
                'tpl' => 'test'
            ),
            array(
                'case'      => 'With template, everything is going smooth',
                'output'    => 'test',
                'tpl'       => 'test',
                'exception' => false,
                'load'      => true,
                'before'    => array('counter' => 0, 'tpl' => null),
                'after'     => array('counter' => 0, 'tpl' => null),
            )
        );

        // With template, before/after methods are correctly called
        $data[] = array(
            array(
                'mock' => array(
                    'doTask' => 'Dummy',
                    'before' => true,
                    'after'  => 'test-after',
                    'output' => 'test'
                ),
                'tpl' => 'test'
            ),
            array(
                'case'      => 'With template, before/after methods are correctly called',
                'output'    => 'test-after',
                'tpl'       => 'test',
                'exception' => false,
                'load'      => true,
                'before'    => array('counter' => 1, 'tpl' => 'test'),
                'after'     => array('counter' => 1, 'tpl' => 'test'),
            )
        );

        // No template, before returns false
        $data[] = array(
            array(
                'mock' => array(
                    'doTask' => 'Dummy',
                    'before' => false,
                    'after'  => true,
                    'output' => 'test'
                ),
                'tpl' => null
            ),
            array(
                'case'      => 'No template, before returns false',
                'output'    => null,
                'tpl'       => null,
                'exception' => 403,
                'load'      => false,
                'before'    => array('counter' => 1, 'tpl' => null),
                'after'     => array('counter' => 0, 'tpl' => null),
            )
        );

        // No template, after returns false
        $data[] = array(
            array(
                'mock' => array(
                    'doTask' => 'Dummy',
                    'before' => true,
                    'after'  => false,
                    'output' => 'test'
                ),
                'tpl' => null
            ),
            array(
                'case'      => 'No template, after returns false',
                'output'    => null,
                'tpl'       => null,
                'exception' => 403,
                'load'      => true,
                'before'    => array('counter' => 1, 'tpl' => null),
                'after'     => array('counter' => 1, 'tpl' => null),
            )
        );

        // No template, loadTemplate returns an exception
        $data[] = array(
            array(
                'mock' => array(
                    'doTask' => 'Foobar',
                    'before' => null,
                    'after'  => null,
                    'output' => new \Exception('', 500)
                ),
                'tpl' => null
            ),
            array(
                'case'      => 'No template, loadTemplate returns an exception',
                'output'    => null,
                'tpl'       => null,
                'exception' => 500,
                'load'      => true,
                'before'    => array('counter' => 0, 'tpl' => null),
                'after'     => array('counter' => 0, 'tpl' => null),
            )
        );

        return $data;
    }

    public static function getTestLoadTemplate()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array('test')
                ),
                'tpl'    => null,
                'strict' => false
            ),
            array(
                'case'   => 'No template, no strict, we immediatly have a result',
                'result' => 'test'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array('throw', 'throw', 'throw', 'throw', 'throw', 'throw')
                ),
                'tpl'    => null,
                'strict' => false
            ),
            array(
                'case'   => 'No template, no strict, we immediatly throw an exception',
                'result' => new \Exception()
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array(new \Exception(), new \Exception(), new \Exception(), new \Exception(), new \Exception(), new \Exception())
                ),
                'tpl'    => null,
                'strict' => false
            ),
            array(
                'case'   => 'No template, no strict, we immediatly return an exception',
                'result' => new \Exception()
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array('throw', 'test')
                ),
                'tpl'    => null,
                'strict' => false
            ),
            array(
                'case'   => 'No template, no strict, we have a result after throwing some exceptions',
                'result' => 'test'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array(new \Exception(), 'test')
                ),
                'tpl'    => null,
                'strict' => false
            ),
            array(
                'case'   => 'No template, no strict, we have a result after returning some exceptions',
                'result' => 'test'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array('test')
                ),
                'tpl'    => 'dummy',
                'strict' => false
            ),
            array(
                'case'   => 'With template, no strict, we immediatly have a result',
                'result' => 'test'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'foobar',
                    'any'    => array('test')
                ),
                'tpl'    => 'dummy',
                'strict' => true
            ),
            array(
                'case'   => 'With template and strict, we immediatly have a result',
                'result' => 'test'
            )
        );

        return $data;
    }

    public static function getTestSetLayout()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'layout' => null
                ),
                'layout' => 'foobar'
            ),
            array(
                'case'   => 'Internal layout is null, passing simple layout',
                'result' => null,
                'layout' => 'foobar',
                'tmpl'   => '_'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => 'previous'
                ),
                'layout' => 'foobar'
            ),
            array(
                'case'   => 'Internal layout is set, passing simple layout',
                'result' => 'previous',
                'layout' => 'foobar',
                'tmpl'   => '_'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'layout' => null
                ),
                'layout' => 'foo:bar'
            ),
            array(
                'case'   => 'Internal layout is null, passing layout + template',
                'result' => null,
                'layout' => 'bar',
                'tmpl'   => 'foo'
            )
        );

        return $data;
    }
}