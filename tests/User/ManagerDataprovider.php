<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class ManagerDataprovider
{
    public static function getTestGetUser()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 0,
                    'user'    => null
                ),
                'privileges'      => array('foobar' => new \Awf\Tests\Stubs\User\PrivilegeMock()),
                'authentications' => array('foobar' => new \Awf\Tests\Stubs\User\AuthenticationMock()),
                'user' => 2
            ),
            array(
                'case'    => "User id passed, but it's not in the table",
                'result'  => false,
                'loaded'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 2,
                    'user'    => null
                ),
                'privileges'      => array('foobar' => new \Awf\Tests\Stubs\User\PrivilegeMock()),
                'authentications' => array('foobar' => new \Awf\Tests\Stubs\User\AuthenticationMock()),
                'user' => null
            ),
            array(
                'case'    => "Retrieved from the session, but it's not in the table",
                'result'  => false,
                'loaded'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 0,
                    'user'    => null
                ),
                'privileges'      => array('foobar' => new \Awf\Tests\Stubs\User\PrivilegeMock()),
                'authentications' => array('foobar' => new \Awf\Tests\Stubs\User\AuthenticationMock()),
                'user' => 1
            ),
            array(
                'case'    => "User id passed, it's in the table",
                'result'  => true,
                'loaded'  => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 1,
                    'user'    => null
                ),
                'privileges'      => array('foobar' => new \Awf\Tests\Stubs\User\PrivilegeMock()),
                'authentications' => array('foobar' => new \Awf\Tests\Stubs\User\AuthenticationMock()),
                'user' => null
            ),
            array(
                'case'    => "Retrieved from the session, it's in the table",
                'result'  => true,
                'loaded'  => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 1,
                    'user'    => new \Awf\User\User()
                ),
                'privileges'      => array(),
                'authentications' => array(),
                'user' => null
            ),
            array(
                'case'    => "Retrieved from the cache",
                'result'  => true,
                'loaded'  => false
            )
        );

        return $data;
    }

    public static function getTestGetUserByUsername()
    {
        $data[] = array(
            array(
                'username' => 'test'
            ),
            array(
                'case'   => 'User exists',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'username' => 'wrong'
            ),
            array(
                'case'   => 'User does not exist',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestLoginUser()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'verify'   => false,
                    'username' => false
                )
            ),
            array(
                'case'      => 'Username not found',
                'verify'    => false,
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'verify'   => false,
                    'username' => true
                )
            ),
            array(
                'case'      => 'Passwords do not match',
                'verify'    => true,
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'verify'   => true,
                    'username' => true
                )
            ),
            array(
                'case'      => 'Everything is ok',
                'verify'    => true,
                'exception' => false
            )
        );

        return $data;
    }

    public static function getTestSaveUser()
    {
        $data[] = array(
            array(
                'data' => array(
                    'username' => 'changed'
                )
            ),
            array(
                'case'    => 'Update current user',
                'user_id' => 1,
                'user'    => array(
                    'id' => 1,
                    'name' => 'test',
                    'email' => 'test@example.com',
                    'username' => 'changed',
                    'password' => '$2y$10$1bZNcHV4m11lL2vHOQsQau7I50J.QgOBRFp2W8NoL7fC/SsFBXw86',
                    'parameters' => '{}'
                )
            )
        );

        $data[] = array(
            array(
                'data' => array(
                    'id'       => null,
                    'username' => 'new',
                    'password' => 'new'
                )
            ),
            array(
                'case'    => 'Insert new user',
                'user_id' => 2,
                'user'    => array(
                    'id' => 2,
                    'name' => 'test',
                    'email' => 'test@example.com',
                    'username' => 'new',
                    'password' => 'new',
                    'parameters' => '{}'
                )
            )
        );

        return $data;
    }

    public static function getTestDeleteUser()
    {
        $data[] = array(
            array(
                'id' => ''
            ),
            array(
                'case' => 'Passed id is empty',
                'delete' => false,
                'result' => null
            )
        );

        $data[] = array(
            array(
                'id' => '100'
            ),
            array(
                'case' => 'Passed id is not empty',
                'delete' => true,
                'result' => true
            )
        );

        return $data;
    }
}
