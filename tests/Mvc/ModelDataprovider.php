<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class ModelDataprovider
{
    public static function getTestgetInstance()
    {
        $data[] = array(
            array(
                'appName'    => null,
                'container'  => null,
                'model'      => null,
                'view'       => ''
            ),
            array(
                'case'   => 'No application, no container nor model. No view set in the input',
                'result' => 'Fakeapp\\Model\\DefaultModel',
                'getClone' => 0,
                'savestate' => 0,
                'clearState' => 0,
                'clearInput' => 0
            )
        );

        $data[] = array(
            array(
                'appName'    => 'Fakeapp',
                'container'  => null,
                'model'      => null,
                'view'       => ''
            ),
            array(
                'case'   => 'With application, no container nor model. No view set in the input',
                'result' => 'Fakeapp\\Model\\DefaultModel',
                'getClone' => 0,
                'savestate' => 0,
                'clearState' => 0,
                'clearInput' => 0
            )
        );

        $data[] = array(
            array(
                'appName'    => null,
                'container'  => null,
                'model'      => 'foobar',
                'view'       => ''
            ),
            array(
                'case'   => 'No application, no container and model is set (singular). No view set in the input',
                'result' => 'Fakeapp\\Model\\Foobar',
                'getClone' => 0,
                'savestate' => 0,
                'clearState' => 0,
                'clearInput' => 0
            )
        );

        $data[] = array(
            array(
                'appName'    => null,
                'container'  => true,
                'model'      => 'foobar',
                'view'       => ''
            ),
            array(
                'case'   => 'No application, no container and model is set (singular). No view set in the input',
                'result' => 'Fakeapp\\Model\\Foobar',
                'getClone' => 0,
                'savestate' => 0,
                'clearState' => 0,
                'clearInput' => 0
            )
        );

        $data[] = array(
            array(
                'appName'    => null,
                'container'  => true,
                'model'      => null,
                'view'       => ''
            ),
            array(
                'case'   => 'No application, with container and no model. No view set in the input',
                'result' => 'Fakeapp\\Model\\DefaultModel',
                'getClone' => 0,
                'savestate' => 0,
                'clearState' => 0,
                'clearInput' => 0
            )
        );

        $data[] = array(
            array(
                'appName'    => null,
                'container'  => true,
                'model'      => null,
                'view'       => 'dummy'
            ),
            array(
                'case'   => 'No application, with container and no model. View set in the input',
                'result' => 'Fakeapp\\Model\\Dummies',
                'getClone' => 0,
                'savestate' => 0,
                'clearState' => 0,
                'clearInput' => 0
            )
        );

        $data[] = array(
            array(
                'appName'    => null,
                'container'  => true,
                'model'      => 'foobar',
                'view'       => '',
                'tempInstance' => true,
                'clearState' => true,
                'clearInput' => true,
            ),
            array(
                'case'   => 'No application, with container and model is set (singular). No view set in the input. Passing extra options in the config',
                'result' => 'Fakeapp\\Model\\Foobar',
                'getClone' => 1,
                'savestate' => 1,
                'clearState' => 1,
                'clearInput' => 1
            )
        );

        return $data;
    }

    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'container' => false,
                'mvc'       => array()
            ),
            array(
                'case'       => 'Container not passed, state is not set in the mvc_config, no populate, no ignore',
                'state'      => (object) array(),
                'populate'   => false,
                'ignore'     => false,
                'counterApp' => 1
            )
        );

        $data[] = array(
            array(
                'container' => true,
                'mvc'       => array(
                    'state' => array(
                        'dummy' => 'test'
                    )
                )
            ),
            array(
                'case'       => 'Passed container, state is set in the mvc_config (array), no populate, no ignore',
                'state'      => (object) array(
                    'dummy' => 'test'
                ),
                'populate'   => false,
                'ignore'     => false,
                'counterApp' => 0
            )
        );

        $data[] = array(
            array(
                'container' => true,
                'mvc'       => array(
                    'state' => 'wrong'
                )
            ),
            array(
                'case'       => 'Passed container, state is set in the mvc_config (string - wrong), no populate, no ignore',
                'state'      => (object) array(),
                'populate'   => false,
                'ignore'     => false,
                'counterApp' => 0
            )
        );

        $data[] = array(
            array(
                'container' => true,
                'mvc'       => array(
                    'state' => (object) array(
                        'dummy' => 'test'
                    )
                )
            ),
            array(
                'case'       => 'Passed container, state is set in the mvc_config (object), no populate, no ignore',
                'state'      => (object) array(
                    'dummy' => 'test'
                ),
                'populate'   => false,
                'ignore'     => false,
                'counterApp' => 0
            )
        );

        $data[] = array(
            array(
                'container' => true,
                'mvc'       => array(
                    'state' => (object) array(
                        'dummy' => 'test'
                    ),
                    'use_populate' => true,
                    'ignore_request' => true
                )
            ),
            array(
                'case'       => 'Passed container, state is set in the mvc_config (object), with populate and ignore',
                'state'      => (object) array(
                    'dummy' => 'test'
                ),
                'populate'   => true,
                'ignore'     => true,
                'counterApp' => 0
            )
        );

        $data[] = array(
            array(
                'container' => true,
                'mvc'       => array(
                    'state' => (object) array(
                        'dummy' => 'test'
                    ),
                    'use_populate'   => false,
                    'ignore_request' => false
                )
            ),
            array(
                'case'       => 'Passed container, state is set in the mvc_config (object), with populate and ignore (they are set to false)',
                'state'      => (object) array(
                    'dummy' => 'test'
                ),
                'populate'   => false,
                'ignore'     => false,
                'counterApp' => 0
            )
        );

        return $data;
    }

    public static function getTestSetState()
    {
        $data[] = array(
            array(
                'property' => 'foo',
                'value'    => 'bar',
                'mock'     => array(
                    'state' => null
                )
            ),
            array(
                'case' => 'Setting a propery to a value, internal state is empty',
                'result' => 'bar',
                'state' => (object) array(
                    'foo' => 'bar'
                )
            )
        );

        $data[] = array(
            array(
                'property' => 'foo',
                'value'    => 'bar',
                'mock'     => array(
                    'state' => (object) array(
                        'dummy' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Setting a propery to a value, internal state is not empty',
                'result' => 'bar',
                'state' => (object) array(
                    'foo' => 'bar',
                    'dummy' => 'test'
                )
            )
        );

        $data[] = array(
            array(
                'property' => 'foo',
                'value'    => 'bar',
                'mock'     => array(
                    'state' => (object) array(
                        'foo' => 'test'
                    )
                )
            ),
            array(
                'case' => 'Trying to overwrite a propery value, internal state is not empty',
                'result' => 'bar',
                'state' => (object) array(
                    'foo' => 'bar'
                )
            )
        );

        return $data;
    }

    public static function getTestSavestate()
    {
        $data[] = array(
            array(
                'state' => true
            ),
            array(
                'case'  => 'New state is boolean true',
                'state' => true
            )
        );

        $data[] = array(
            array(
                'state' => false
            ),
            array(
                'case'  => 'New state is boolean false',
                'state' => false
            )
        );

        $data[] = array(
            array(
                'state' => 1
            ),
            array(
                'case'  => 'New state is int 1',
                'state' => true
            )
        );

        $data[] = array(
            array(
                'state' => 0
            ),
            array(
                'case'  => 'New state is int 0',
                'state' => false
            )
        );

        return $data;
    }

    public static function getTestPopulatesavestate()
    {
        // Savestate is -999 => we are going to save the state
        $data[] = array(
            array(
                'state' => -999,
                'mock'  => array(
                    'state' => null
                )
            ),
            array(
                'savestate' => 1,
                'state'     => true
            )
        );

        // We already saved the state, nothing happens
        $data[] = array(
            array(
                'state' => -999,
                'mock'  => array(
                    'state' => true
                )
            ),
            array(
                'savestate' => 0,
                'state'     => null
            )
        );

        // Savestate is 1 => we are going to save the state
        $data[] = array(
            array(
                'state' => 1,
                'mock'  => array(
                    'state' => null
                )
            ),
            array(
                'savestate' => 1,
                'state'     => 1
            )
        );

        // Savestate is -1 => we are NOT going to save the state
        $data[] = array(
            array(
                'state' => -1,
                'mock'  => array(
                    'state' => null
                )
            ),
            array(
                'savestate' => 1,
                'state'     => -1
            )
        );

        return $data;
    }
}
