<?php

class DataControllerDataprovider
{
    public static function getTestAdd()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'flash' => ''
                )
            ),
            array(
                'bind' => ''
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'flash' => array('foo' => 'bar')
                )
            ),
            array(
                'bind' => array('foo' => 'bar')
            )
        );

        return $data;
    }

    public static function getTestEdit()
    {
        // Getting the id from the model, no layout, no data in the session, everything works fine
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'flash' => null,
                    'getId' => true ,
                    'lock'  => true,
                    'layout'=> ''
                )
            ),
            array(
                'bind'       => false,
                'getFromReq' => false,
                'redirect'   => false,
                'url'        => '',
                'msg'        => '',
                'display'    => true,
                'layout'     => 'form'
            )
        );

        // Getting the id from the request, with layout, fetch data from the session, everything works fine
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'flash' => array('foo' => 'bar'),
                    'getId' => false ,
                    'lock'  => true,
                    'layout'=> 'custom'
                )
            ),
            array(
                'bind'       => array('foo' => 'bar'),
                'getFromReq' => true,
                'redirect'   => false,
                'url'        => '',
                'msg'        => '',
                'display'    => true,
                'layout'     => 'custom'
            )
        );

        // Lock throws an error, no custom url
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'flash' => null,
                    'getId' => true ,
                    'lock'  => 'throw',
                    'layout'=> ''
                )
            ),
            array(
                'bind'       => false,
                'getFromReq' => false,
                'redirect'   => true,
                'url'        => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'        => 'Exception thrown while locking',
                'display'    => false,
                'layout'     => ''
            )
        );

        // Lock throws an error, custom url set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'flash' => null,
                    'getId' => true ,
                    'lock'  => 'throw',
                    'layout'=> ''
                )
            ),
            array(
                'bind'       => false,
                'getFromReq' => false,
                'redirect'   => true,
                'url'        => 'http://www.example.com/index.php?view=custom',
                'msg'        => 'Exception thrown while locking',
                'display'    => false,
                'layout'     => ''
            )
        );

        return $data;
    }

    public static function getTestApply()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'id'        => 3,
                    'returnurl' => '',
                    'apply'     => true
                )
            ),
            array(
                'redirect' => true,
                'url'      => 'http://www.example.com/index.php?view=dummycontroller&task=edit&id=3',
                'msg'      => 'FAKEAPP_LBL_DUMMYCONTROLLER_SAVED'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id'        => 3,
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'apply'     => true
                )
            ),
            array(
                'redirect' => true,
                'url'      => 'http://www.example.com/index.php?view=custom',
                'msg'      => 'FAKEAPP_LBL_DUMMYCONTROLLER_SAVED'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id'        => 3,
                    'returnurl' => '',
                    'apply'     => false
                )
            ),
            array(
                'redirect' => false,
                'url'      => '',
                'msg'      => ''
            )
        );

        return $data;
    }

    public static function getTestCopy()
    {
        // Everything works fine, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'find'      => array(true),
                    'copy'      => array(true),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'  => 'FAKEAPP_LBL_DUMMYCONTROLLER_COPIED',
                'type' => null
            )
        );

        // Everything works fine, custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'find'      => array(true),
                    'copy'      => array(true),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=custom',
                'msg'  => 'FAKEAPP_LBL_DUMMYCONTROLLER_COPIED',
                'type' => null
            )
        );

        // Copy throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'find'      => array(true),
                    'copy'      => array('throw'),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'  => 'Exception in copy',
                'type' => 'error'
            )
        );

        // Find throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'find'      => array('throw'),
                    'copy'      => array(true),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'  => 'Exception in find',
                'type' => 'error'
            )
        );

        return $data;
    }

    public static function getTestSave()
    {
        // Everything is fine, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'apply'     => true
                )
            ),
            array(
                'redirect' => true,
                'url' => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg' => 'FAKEAPP_LBL_DUMMYCONTROLLER_SAVED'
            )
        );

        // Everything is fine, custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'apply'     => true
                )
            ),
            array(
                'redirect' => true,
                'url' => 'http://www.example.com/index.php?view=custom',
                'msg' => 'FAKEAPP_LBL_DUMMYCONTROLLER_SAVED'
            )
        );

        // An error occurs, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'apply'     => false
                )
            ),
            array(
                'redirect' => false,
                'url' => '',
                'msg' => ''
            )
        );

        return $data;
    }

    public static function getTestSavenew()
    {
        // Everything is fine, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'apply'     => true
                )
            ),
            array(
                'redirect' => true,
                'url' => 'http://www.example.com/index.php?view=dummycontroller&task=add',
                'msg' => 'FAKEAPP_LBL_DUMMYCONTROLLER_SAVED'
            )
        );

        // Everything is fine, custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'apply'     => true
                )
            ),
            array(
                'redirect' => true,
                'url' => 'http://www.example.com/index.php?view=custom',
                'msg' => 'FAKEAPP_LBL_DUMMYCONTROLLER_SAVED'
            )
        );

        // An error occurs, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'apply'     => false
                )
            ),
            array(
                'redirect' => false,
                'url' => '',
                'msg' => ''
            )
        );

        return $data;
    }

    public function getTestCancel()
    {
        // Getting the id from the model, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'getFromReq' => false,
                'url'        => 'http://www.example.com/index.php?view=dummycontrollers'
            )
        );

        // Getting the id from request, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'getId'     => null,
                    'ids'       => array(3)
                )
            ),
            array(
                'getFromReq' => true,
                'url'        => 'http://www.example.com/index.php?view=dummycontrollers'
            )
        );

        // Getting the id from the model, custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'getFromReq' => false,
                'url'        => 'http://www.example.com/index.php?view=custom'
            )
        );

        return $data;
    }

    public function getTestOrderdown()
    {
        // Everything works fine, no custom redirect set, getting the id from the model
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'move'      => array(true),
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'getFromReq' => false,
                'msg'  => null,
                'type' => null
            )
        );

        // Everything works fine, no custom redirect set, getting the id from the request
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'move'      => array(true),
                    'getId'     => null,
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'getFromReq' => true,
                'msg'  => null,
                'type' => null
            )
        );

        // Everything works fine, custom redirect set, getting the id from the model
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'move'      => array(true),
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=custom',
                'getFromReq' => false,
                'msg'  => null,
                'type' => null
            )
        );

        // Move throws an error, no custom redirect set, getting the id from the model
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'move'      => array('throw'),
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'getFromReq' => false,
                'msg'  => 'Exception in move',
                'type' => 'error'
            )
        );

        return $data;
    }

    public function getTestOrderup()
    {
        // Everything works fine, no custom redirect set, getting the id from the model
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'move'      => array(true),
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'getFromReq' => false,
                'msg'  => null,
                'type' => null
            )
        );

        // Everything works fine, no custom redirect set, getting the id from the request
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'move'      => array(true),
                    'getId'     => null,
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'getFromReq' => true,
                'msg'  => null,
                'type' => null
            )
        );

        // Everything works fine, custom redirect set, getting the id from the model
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'move'      => array(true),
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=custom',
                'getFromReq' => false,
                'msg'  => null,
                'type' => null
            )
        );

        // Move throws an error, no custom redirect set, getting the id from the model
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'move'      => array('throw'),
                    'getId'     => 3,
                    'ids'       => array()
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'getFromReq' => false,
                'msg'  => 'Exception in move',
                'type' => 'error'
            )
        );

        return $data;
    }

    public static function getTestRemove()
    {
        // Everything works fine, no custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'find'      => array(true),
                    'delete'    => array(true),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'  => 'FAKEAPP_LBL_DUMMYCONTROLLER_DELETED',
                'type' => null
            )
        );

        // Everything works fine, custom redirect set
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => 'http://www.example.com/index.php?view=custom',
                    'find'      => array(true),
                    'delete'    => array(true),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=custom',
                'msg'  => 'FAKEAPP_LBL_DUMMYCONTROLLER_DELETED',
                'type' => null
            )
        );

        // Delete throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'find'      => array(true),
                    'delete'    => array('throw'),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'  => 'Exception in delete',
                'type' => 'error'
            )
        );

        // Find throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'returnurl' => '',
                    'find'      => array('throw'),
                    'delete'    => array(true),
                    'ids'       => array(3)
                )
            ),
            array(
                'url'  => 'http://www.example.com/index.php?view=dummycontrollers',
                'msg'  => 'Exception in find',
                'type' => 'error'
            )
        );

        return $data;
    }

    public static function getTestGetModel()
    {
        $data[] = array(
            array(
                'model' => 'datafoobars'
            ),
            array(
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'model' => null
            ),
            array(
                'exception' => true
            )
        );

        return $data;
    }

    public static function getTestGetIDsFromRequest()
    {
        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Everything is empty, not asked for loading',
                'result' => array(),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Everything is empty, asked for loading',
                'result' => array(),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(3,4,5),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed an array of id (cid), not asked for loading',
                'result' => array(3,4,5),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(3,4,5),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed an array of id (cid), asked for loading',
                'result' => array(3,4,5),
                'load'   => true,
                'loadid' => array('id' => 3)
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 3,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed a single id (id) , not asked for loading',
                'result' => array(3),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 3,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed a single id (id), asked for loading',
                'result' => array(3),
                'load'   => true,
                'loadid' => array('id' => 3)
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passed a single id (kid) , not asked for loading',
                'result' => array(3),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passed a single id (kid), asked for loading',
                'result' => array(3),
                'load'   => true,
                'loadid' => array('id' => 3)
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(4,5,6),
                    'id'  => 3,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passing an array of id (cid) and a single id (id), not asked for loading',
                'result' => array(4,5,6),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(4,5,6),
                    'id'  => 0,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passing an array of id (cid) and a single id (kid), not asked for loading',
                'result' => array(4,5,6),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 4,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passing a single id (id and kid), not asked for loading',
                'result' => array(4),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(4,5,6),
                    'id'  => 3,
                    'kid' => 7
                )
            ),
            array(
                'case'   => 'Passing everything, not asked for loading',
                'result' => array(4,5,6),
                'load'   => false,
                'loadid' => null
            )
        );

        return $data;
    }
}