<?php

class ViewDataprovider
{
    public function getTestGet()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'viewProperty' => array(),
                    'defaultModel' => 'foobars',
                    'instances' => array(
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub()
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
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub()
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
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub()
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
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub()
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
                        'foobars' => new \Awf\Tests\Stubs\Mvc\ModelStub()
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