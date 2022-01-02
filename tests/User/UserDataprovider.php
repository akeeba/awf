<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class UserDataprovider
{
    public static function getTestBind()
    {
        $data[] = array(
            array(
                'mock'       => array(),
                'privileges' => false,
                'data'       => array(
                    'username'   => 'test',
                    'name'       => 'test',
                    'email'      => 'user@example.com',
                    'password'   => 'test',
                    'wrong'      => 'test',
                    'privileges' => 'test'
                )
            ),
            array(
                'case'   => 'Passing an array, no attached events',
                'events' => array('before' => 0, 'after' => 0),
                'user'   => array(
                    'username' => 'test',
                    'name'     => 'test',
                    'email'    => 'user@example.com',
                    'password' => 'test'
                )
            )
        );

        $data[] = array(
            array(
                'mock'       => array(),
                'privileges' => false,
                'data'       => (object) array(
                    'username'   => 'test',
                    'name'       => 'test',
                    'email'      => 'user@example.com',
                    'password'   => 'test',
                    'wrong'      => 'test',
                    'privileges' => 'test'
                )
            ),
            array(
                'case'   => 'Passing an object, no attached events',
                'events' => array('before' => 0, 'after' => 0),
                'user'   => array(
                    'username' => 'test',
                    'name'     => 'test',
                    'email'    => 'user@example.com',
                    'password' => 'test'
                )
            )
        );

        $data[] = array(
            array(
                'mock'       => array(),
                'privileges' => true,
                'data'       => array(
                    'username'   => 'test',
                    'name'       => 'test',
                    'email'      => 'user@example.com',
                    'password'   => 'test'
                )
            ),
            array(
                'case'   => 'Passing an array, with events',
                'events' => array('before' => 1, 'after' => 1),
                'user'   => array(
                    'username' => 'test',
                    'name'     => 'test',
                    'email'    => 'user@example.com',
                    'password' => 'test'
                )
            )
        );

        $data[] = array(
            array(
                'mock'       => array(
                    'data' => array(
                        'username' => 'foobar'
                    )
                ),
                'privileges' => true,
                'data'       => array(
                    'username'   => 'test',
                    'name'       => 'test',
                    'email'      => 'user@example.com',
                    'password'   => 'test'
                )
            ),
            array(
                'case'   => 'Passing an array, with events, modifying the data',
                'events' => array('before' => 1, 'after' => 1),
                'user'   => array(
                    'username' => 'foobar',
                    'name'     => 'test',
                    'email'    => 'user@example.com',
                    'password' => 'test'
                )
            )
        );

        return $data;
    }

    public static function getTestSetPassword()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists' => array(
                        'crypt' => true
                    ),
                    'hash_algos' => array()
                )
            ),
            array(
                'case'    => 'We can use bcrypt',
                'encType' => 'bcrypt',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'function_exists' => array(
                        'crypt' => false
                    ),
                    'hash_algos' => array('sha512')
                )
            ),
            array(
                'case'    => 'Using sha512',
                'encType' => 'sha512',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'function_exists' => array(
                        'crypt' => false
                    ),
                    'hash_algos' => array('sha256')
                )
            ),
            array(
                'case'    => 'Using sha256',
                'encType' => 'sha256',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'function_exists' => array(
                        'crypt' => false,
                        'sha1'  => true
                    ),
                    'hash_algos' => array()
                )
            ),
            array(
                'case'    => 'Using sha1',
                'encType' => 'sha1',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'function_exists' => array(
                        'crypt' => false,
                        'sha1'  => false,
                        'md5'   => true,
                    ),
                    'hash_algos' => array()
                )
            ),
            array(
                'case'    => 'Using md5',
                'encType' => 'md5',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'function_exists' => array(
                        'crypt' => false,
                        'sha1'  => false,
                        'md5'   => false,
                    ),
                    'hash_algos' => array()
                )
            ),
            array(
                'case'    => "There isn't any available algorithm",
                'encType' => '',
                'exception' => true
            )
        );

        return $data;
    }

    public static function getTestGetParameters()
    {
        $data[] = array(
            array(
                'parameters' => ''
            ),
            array(
                'case' => 'Parameters is empty'
            )
        );

        $data[] = array(
            array(
                'parameters' => 'test'
            ),
            array(
                'case' => 'Parameters is not an object'
            )
        );

        $data[] = array(
            array(
                'parameters' => new stdClass()
            ),
            array(
                'case' => 'Parameters is not an instance of Registry'
            )
        );

        $data[] = array(
            array(
                'parameters' => new \Awf\Registry\Registry()
            ),
            array(
                'case' => 'Parameters is an instance of Registry'
            )
        );

        return $data;
    }

    public static function getTestGetPrivilege()
    {
        $data[] = array(
            array(
                'privilege' => 'wrong'
            ),
            array(
                'case'   => 'Passing an invalid privilege',
                'result' => 'default'
            )
        );

        $data[] = array(
            array(
                'privilege' => 'nothere.test'
            ),
            array(
                'case'   => 'Privilege does not exist',
                'result' => 'default'
            )
        );

        $data[] = array(
            array(
                'privilege' => 'foobar.test'
            ),
            array(
                'case'   => 'Privilege is there',
                'result' => 'closure'
            )
        );

        return $data;
    }

    public static function getTestSetPrivilege()
    {
        $data[] = array(
            array(
                'privilege' => 'wrong'
            ),
            array(
                'case'   => 'Passing an invalid privilege',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'privilege' => 'nothere.test'
            ),
            array(
                'case'   => 'Privilege does not exist',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'privilege' => 'foobar.test'
            ),
            array(
                'case'   => 'Privilege is there',
                'result' => 'closure'
            )
        );

        return $data;
    }

    public static function getTestTriggerAuthenticationEvent()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'auth1' => false,
                    'auth2' => false
                ),
                'auth' => false,
            ),
            array(
                'case'    => 'No plugins attached',
                'result'  => false,
                'counter' => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'auth1' => false,
                    'auth2' => true
                ),
                'auth' => true,
            ),
            array(
                'case'    => 'Plugins attached, the first one returns false',
                'result'  => false,
                'counter' => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'auth1' => true,
                    'auth2' => false
                ),
                'auth' => true,
            ),
            array(
                'case'    => 'Plugins attached, the second one returns false',
                'result'  => false,
                'counter' => 2
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'auth1' => true,
                    'auth2' => true
                ),
                'auth' => true,
            ),
            array(
                'case'    => 'Plugins attached, they all return true',
                'result'  => true,
                'counter' => 2
            )
        );

        return $data;
    }
}
