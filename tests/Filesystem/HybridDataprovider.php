<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class HybridDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'options' => array(
                    'driver' => 'Fake'
                )
            ),
            array(
                'case'    => 'Passing a driver, everything works fine',
                'adapter' => 'Awf\Filesystem\Fake',
            )
        );

        $data[] = array(
            array(
                'options' => array(
                    'driver' => 'Ftp'
                )
            ),
            array(
                'case'    => 'Passing a driver, it triggers an error',
                'adapter' => false,
            )
        );

        $data[] = array(
            array(
                'options' => array()
            ),
            array(
                'case'    => 'No additional driver',
                'adapter' => false,
            )
        );

        return $data;
    }

    public static function getTestWrite()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestDelete()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestCopy()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestMove()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestChmod()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestMkdir()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestRmdir()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file returns an error',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, file returns an error, adapter fails, too',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestTranslatePath()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file works fine',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => true
                ),
                'adapter' => false
            ),
            array(
                'case'   => 'No adapter, file fails',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => true
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, adapter works fine',
                'result' => true,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => true,
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'   => 'With adapter, adapter fails',
                'result' => false,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestListFolders()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file'    => array('foobar'),
                    'adapter' => ''
                ),
                'adapter' => false
            ),
            array(
                'case'      => 'No adapter, file works',
                'exception' => false,
                'result'    => array('foobar'),
                'count'     => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => ''
                ),
                'adapter' => false
            ),
            array(
                'case'      => 'No adapter, file fails',
                'exception' => false,
                'result'    => false,
                'count'     => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => 'exception',
                    'adapter' => ''
                ),
                'adapter' => false
            ),
            array(
                'case'      => 'No adapter, file throws an exception',
                'exception' => true,
                'result'    => false,
                'count'     => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => false,
                    'adapter' => ''
                ),
                'adapter' => true
            ),
            array(
                'case'      => 'With adapter, file fails',
                'exception' => false,
                'result'    => false,
                'count'     => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => 'exception',
                    'adapter' => array('foobar')
                ),
                'adapter' => true
            ),
            array(
                'case'      => 'With adapter, file throws an exception, adapter works',
                'exception' => false,
                'result'    => array('foobar'),
                'count'     => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => 'exception',
                    'adapter' => false
                ),
                'adapter' => true
            ),
            array(
                'case'      => 'With adapter, file throws an exception, adapter fails',
                'exception' => false,
                'result'    => false,
                'count'     => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file'    => 'exception',
                    'adapter' => 'exception'
                ),
                'adapter' => true
            ),
            array(
                'case'      => 'With adapter, file throws an exception, adapter throws an exception',
                'exception' => true,
                'result'    => false,
                'count'     => 1
            )
        );

        return $data;
    }
}
