<?php

class ViewDataprovider
{
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