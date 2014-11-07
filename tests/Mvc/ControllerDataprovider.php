<?php

class ControllerDataprovider
{
    public static function getTestExecute()
    {
        $data[] = array(
            array(
                'task' => 'foobar',
                'mock' => array(
                    'before' => true,
                    'task'  => true,
                    'after' => true,
                    'taskMap' => array(
                        'foobar' => 'foobar',
                        '__default' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Task is defined inside the taskMap array',
                'doTask' => 'foobar',
                'before' => 0,
                'task' => 1,
                'after' => 0,
                'result' => true
            )
        );

        $data[] = array(
            array(
                'task' => 'foobar',
                'mock' => array(
                    'before' => true,
                    'task'  => false,
                    'after' => true,
                    'taskMap' => array(
                        'foobar' => 'foobar',
                        '__default' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Task is defined inside the taskMap array',
                'doTask' => 'foobar',
                'before' => 0,
                'task' => 1,
                'after' => 0,
                'result' => false
            )
        );

        $data[] = array(
            array(
                'task' => 'foobar',
                'mock' => array(
                    'before' => true,
                    'task'  => true,
                    'after' => true,
                    'taskMap' => array(
                        '__default' => 'foobar'
                    )
                )
            ),
            array(
                'case' => 'Task is defined as default inside the taskMap array',
                'doTask' => 'foobar',
                'before' => 0,
                'task' => 1,
                'after' => 0,
                'result' => true
            )
        );

        $data[] = array(
            array(
                'task' => 'dummy',
                'mock' => array(
                    'before' => true,
                    'task'  => true,
                    'after' => true,
                    'taskMap' => array(
                        'dummy' => 'dummy',
                        '__default' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Task is defined inside the taskMap array, onBefore and onAfter return true',
                'doTask' => 'dummy',
                'before' => 1,
                'task' => 1,
                'after' => 1,
                'result' => true
            )
        );

        $data[] = array(
            array(
                'task' => 'dummy',
                'mock' => array(
                    'before' => false,
                    'task'  => true,
                    'after' => true,
                    'taskMap' => array(
                        'dummy' => 'dummy',
                        '__default' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Task is defined inside the taskMap array, onBefore returns false and onAfter returns true',
                'doTask' => null,
                'before' => 1,
                'task' => 0,
                'after' => 0,
                'result' => false
            )
        );

        $data[] = array(
            array(
                'task' => 'dummy',
                'mock' => array(
                    'before' => true,
                    'task'  => true,
                    'after' => false,
                    'taskMap' => array(
                        'dummy' => 'dummy',
                        '__default' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Task is defined inside the taskMap array, onBefore returns true and onAfter returns false',
                'doTask' => 'dummy',
                'before' => 1,
                'task' => 1,
                'after' => 1,
                'result' => false
            )
        );

        $data[] = array(
            array(
                'task' => 'dummy',
                'mock' => array(
                    'before' => true,
                    'task'  => false,
                    'after' => false,
                    'taskMap' => array(
                        'dummy' => 'dummy',
                        '__default' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Task is defined inside the taskMap array, task returns false onBefore returns true and onAfter returns false',
                'doTask' => 'dummy',
                'before' => 1,
                'task' => 1,
                'after' => 1,
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestDisplay()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'getModel'  => false,
                    'task'      => 'foobar',
                    'doTask'    => 'foobar',
                    'layout'    => null
                )
            ),
            array(
                'case'          => 'Model not created, layout is null',
                'modelCounter'  => 0,
                'layoutCounter' => 0,
                'layout'        => null,
                'task'          => 'foobar',
                'doTask'        => 'foobar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getModel'  => new \Awf\Tests\Stubs\Mvc\ModelStub(),
                    'task'      => 'foobar',
                    'doTask'    => 'foobar',
                    'layout'    => 'dummy'
                )
            ),
            array(
                'case'          => 'Model created, layout is not null',
                'modelCounter'  => 1,
                'layoutCounter' => 1,
                'layout'        => 'dummy',
                'task'          => 'foobar',
                'doTask'        => 'foobar'
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
                    'view' => null,
                    'modelName' => null,
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name passed, model not cached, internal reference are empty',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array(
                    'modelTemporaryInstance' => false,
                    'modelClearState'        => true,
                    'modelClearInput'        => true
                )
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array('foo' => 'bar'),
                'mock' => array(
                    'view' => null,
                    'modelName' => null,
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name and config passed, model not cached, internal reference are empty',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array(
                    'foo' => 'bar',
                    'modelTemporaryInstance' => false,
                    'modelClearState'        => true,
                    'modelClearInput'        => true
                )
            )
        );

        $data[] = array(
            array(
                'name' => null,
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'modelName' => 'foobar',
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name not passed, model not cached, using modelName property',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array(
                    'modelTemporaryInstance' => true
                )
            )
        );

        $data[] = array(
            array(
                'name' => null,
                'config' => array(),
                'mock' => array(
                    'view' => 'foobar',
                    'modelName' => null,
                    'instances' => array()
                )
            ),
            array(
                'case'   => 'Name not passed, model not cached, using view property',
                'result' => '\\Fakeapp\\Model\\Foobar',
                'config' => array(
                    'modelTemporaryInstance' => true
                )
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'modelName' => null,
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

    public static function getTestGetView()
    {
        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'viewName' => null,
                    'instances' => array(),
                    'format'    => null
                )
            ),
            array(
                'case'   => 'Creating HTML view, name passed, view not cached, internal reference are empty',
                'result' => '\\Fakeapp\\View\\Foobar\\Html',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'viewName' => null,
                    'instances' => array(),
                    'format'    => 'html'
                )
            ),
            array(
                'case'   => 'Creating HTML view, name passed, view not cached, internal reference are empty',
                'result' => '\\Fakeapp\\View\\Foobar\\Html',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => null,
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'viewName' => 'foobar',
                    'instances' => array(),
                    'format'    => null
                )
            ),
            array(
                'case'   => 'Creating HTML view, name not passed, fetched from the viewName property',
                'result' => '\\Fakeapp\\View\\Foobar\\Html',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => null,
                'config' => array(),
                'mock' => array(
                    'view' => 'foobar',
                    'viewName' => null,
                    'instances' => array(),
                    'format'    => null
                )
            ),
            array(
                'case'   => 'Creating HTML view, name not passed, fetched from the view property',
                'result' => '\\Fakeapp\\View\\Foobar\\Html',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'viewName' => null,
                    'instances' => array(),
                    'format'    => 'json'
                )
            ),
            array(
                'case'   => 'Creating JSON view, name passed, view not cached, internal reference are empty',
                'result' => '\\Fakeapp\\View\\Foobar\\Json',
                'config' => array()
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array(),
                'mock' => array(
                    'view' => null,
                    'viewName' => null,
                    'instances' => array('foobar' => new \Awf\Tests\Stubs\Mvc\ViewStub()),
                    'format'    => null
                )
            ),
            array(
                'case'   => 'Creating HTML view, fetched from the cache',
                'result' => '\\Awf\Tests\\Stubs\\Mvc\\ViewStub',
                'config' => null
            )
        );

        $data[] = array(
            array(
                'name' => 'foobar',
                'config' => array('foo' => 'bar'),
                'mock' => array(
                    'view' => null,
                    'viewName' => null,
                    'instances' => array(),
                    'format'    => null
                )
            ),
            array(
                'case'   => 'Creating HTML view, name and config passed, view not cached, internal reference are empty',
                'result' => '\\Fakeapp\\View\\Foobar\\Html',
                'config' => array('foo' => 'bar')
            )
        );

        return $data;
    }

    public static function getTestRedirect()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'redirect' => 'index.php'
                )
            ),
            array(
                'case'     => 'A redirect as been set',
                'result'   => null,
                'redirect' => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'redirect' => null
                )
            ),
            array(
                'case'     => 'No redirection set',
                'result'   => false,
                'redirect' => 0
            )
        );

        return $data;
    }

    public static function getTestRegisterTask()
    {
        $data[] = array(
            array(
                'task'   => 'dummy',
                'method' => 'Foobar',
                'mock'   => array(
                    'methods' => array('foobar')
                )
            ),
            array(
                'case'     => 'Method is mapped inside the controller',
                'register' => true
            )
        );

        $data[] = array(
            array(
                'task'   => 'dummy',
                'method' => 'Foobar',
                'mock'   => array(
                    'methods' => array()
                )
            ),
            array(
                'case'     => 'Method is not mapped inside the controller',
                'register' => false
            )
        );

        return $data;
    }

    public static function getTestSetMessage()
    {
        $data[] = array(
            array(
                'message' => 'foo',
                'type'    => null,
                'mock' => array(
                    'previous' => 'bar'
                )
            ),
            array(
                'case'      => '$type argument is null',
                'result'    => 'bar',
                'message'   => 'foo',
                'type'      => 'message'
            )
        );

        $data[] = array(
            array(
                'message' => 'foo',
                'type'    => 'warning',
                'mock' => array(
                    'previous' => 'bar'
                )
            ),
            array(
                'case'      => 'Message type is defined',
                'result'    => 'bar',
                'message'   => 'foo',
                'type'      => 'warning'
            )
        );

        return $data;
    }

    public static function getTestSetRedirect()
    {
        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => null,
                'type' => null,
                'mock' => array(
                    'type' => null
                )
            ),
            array(
                'case'     => 'Url is set, message and type are null; controller messageType is null',
                'redirect' => 'index.php',
                'message'  => null,
                'type'     => 'info'
            )
        );

        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => null,
                'type' => null,
                'mock' => array(
                    'type' => 'warning'
                )
            ),
            array(
                'case'     => 'Url is set, message and type are null; controller messageType is not null',
                'redirect' => 'index.php',
                'message'  => null,
                'type'     => 'warning'
            )
        );

        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => null,
                'type' => 'info',
                'mock' => array(
                    'type' => 'warning'
                )
            ),
            array(
                'case'     => 'Url and type are set, message is null; controller messageType is not null',
                'redirect' => 'index.php',
                'message'  => null,
                'type'     => 'info'
            )
        );

        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => 'Foobar',
                'type' => 'info',
                'mock' => array(
                    'type' => 'warning'
                )
            ),
            array(
                'case'     => 'Url, type and message are set, controller messageType is not null',
                'redirect' => 'index.php',
                'message'  => 'Foobar',
                'type'     => 'info'
            )
        );

        return $data;
    }
}