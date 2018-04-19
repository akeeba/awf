<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

class PublishDataprovider
{
    public static function getTestArchive()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'before' => '',
                    'after'  => '',
                    'alias'  => array()
                ),
                'table' => '#__dbtest'
            ),
            array(
                'case'       => 'Table with no enabled field',
                'dispatcher' => 0,
                'save'       => false,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => '',
                    'after'  => '',
                    'alias'  => array(
                        'enabled' => 'xx_enabled'
                    )
                ),
                'table' => '#__dbtest_alias'
            ),
            array(
                'case'       => 'Table with enabled field (alias)',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => '',
                    'after'  => '',
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ return false;},
                    'after'  => '',
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onBefore returns false',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ return true;},
                    'after'  => function(){ return false;},
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onAfter returns false',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ throw new \Exception();},
                    'after'  => function(){ return false;},
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onBefore throws an exception',
                'dispatcher' => 0,
                'save'       => false,
                'exception'  => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ return true;},
                    'after'  => function(){ throw new \Exception();},
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onAfter throws an exception',
                'dispatcher' => 1,
                'save'       => true,
                'exception'  => true
            )
        );

        return $data;
    }

    public static function getTestTrash()
    {
        $data[] = array(
            array(
                'id' => null
            ),
            array(
                'case'   => 'Table with publish support, already loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => false,
                'dispatcher' => 2,
                'enabled' => -2
            )
        );

        $data[] = array(
            array(
                'id' => 1
            ),
            array(
                'case'   => 'Table with publish support, not loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => true,
                'dispatcher' => 2,
                'enabled' => -2
            )
        );

        return $data;
    }

    public static function getTestTrashException()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
                'id' => 1
            ),
            array(
                'case'      => 'Table with no publish support',
                'exception' => '\\Awf\\Mvc\\DataModel\\Exception\\SpecialColumnMissing'
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest',
                'id' => null
            ),
            array(
                'case'      => 'Table not loaded',
                'exception' => 'Awf\Mvc\DataModel\Exception\RecordNotLoaded'
            )
        );

        return $data;
    }

    public static function getTestPublish()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
                'state' => 1
            ),
            array(
                'case'    => 'Table with no publish support',
                'dispatcher' => 0,
                'before'  => 0,
                'after'   => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'state' => 1
            ),
            array(
                'case'    => 'Table with publish support (record enabling)',
                'dispatcher' => 2,
                'before'  => 1,
                'after'   => 1,
                'enabled' => 1
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'state' => 0
            ),
            array(
                'case'    => 'Table with publish support (record disabling)',
                'dispatcher' => 2,
                'before'  => 1,
                'after'   => 1,
                'enabled' => 0
            )
        );

        return $data;
    }

    public static function getTestRestore()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
                'id' => ''
            ),
            array(
                'case'   => 'Table with no publish support',
                'before' => 0,
                'after'  => 0,
                'find'   => false,
                'dispatcher' => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'id' => null
            ),
            array(
                'case'   => 'Table with publish support, already loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => false,
                'dispatcher' => 2,
                'enabled' => 0
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'id' => 1
            ),
            array(
                'case'   => 'Table with publish support, not loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => true,
                'dispatcher' => 2,
                'enabled' => 0
            )
        );

        return $data;
    }

    public static function getTestUnpublish()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
            ),
            array(
                'case'   => 'Table with no publish support',
                'before' => 0,
                'after'  => 0,
                'dispatcher' => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
            ),
            array(
                'case'   => 'Table with publish support',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'enabled' => 0
            )
        );

        return $data;
    }
}
