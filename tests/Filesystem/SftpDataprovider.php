<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class SftpDataprovider
{
    public static function getTest__destruct()
    {
        $data[] = array(
            array(
                'connection' => null,
            ),
            array(
                'case'  => 'Connection is not set',
                'count' => 0
            )
        );

        $data[] = array(
            array(
                'connection' => fopen('php://stdout', 'r'),
            ),
            array(
                'case'  => 'Connection is set',
                'count' => 1
            )
        );

        return $data;
    }

    public static function getTestConnect()
    {
        // SSH2 module not loaded
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => false,
                    'ssh2_connect'       => false,
                    'ssh2_auth_pubkey_file' => false,
                    'ssh2_auth_password' => false,
                    'ssh2_sftp'          => false,
                    'ssh2_sftp_stat'     => false,
                ),
                'private' => '',
                'public'  => '',
            ),
            array(
                'exception' => true
            )
        );

        // Connection throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => true,
                    'ssh2_connect'       => false,
                    'ssh2_auth_pubkey_file' => false,
                    'ssh2_auth_password' => false,
                    'ssh2_sftp'          => false,
                    'ssh2_sftp_stat'     => false,
                ),
                'private' => '',
                'public'  => '',
            ),
            array(
                'exception' => true
            )
        );

        // Authentication with public key fails
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => true,
                    'ssh2_connect'       => true,
                    'ssh2_auth_pubkey_file' => false,
                    'ssh2_auth_password' => false,
                    'ssh2_sftp'          => false,
                    'ssh2_sftp_stat'     => false,
                ),
                'private' => 'foo',
                'public'  => 'bar',
            ),
            array(
                'exception' => true
            )
        );

        // Username/password authentication fails
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => true,
                    'ssh2_connect'       => true,
                    'ssh2_auth_pubkey_file' => false,
                    'ssh2_auth_password' => false,
                    'ssh2_sftp'          => false,
                    'ssh2_sftp_stat'     => false,
                ),
                'private' => '',
                'public'  => '',
            ),
            array(
                'exception' => true
            )
        );

        // Failing to get SFTP handle
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => true,
                    'ssh2_connect'       => true,
                    'ssh2_auth_pubkey_file' => true,
                    'ssh2_auth_password' => true,
                    'ssh2_sftp'          => false,
                    'ssh2_sftp_stat'     => false,
                ),
                'private' => '',
                'public'  => '',
            ),
            array(
                'exception' => true
            )
        );

        // Failing to change the directory
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => true,
                    'ssh2_connect'       => true,
                    'ssh2_auth_pubkey_file' => true,
                    'ssh2_auth_password' => true,
                    'ssh2_sftp'          => true,
                    'ssh2_sftp_stat'     => false,
                ),
                'private' => '',
                'public'  => '',
            ),
            array(
                'exception' => true
            )
        );

        // Everything works fine
        $data[] = array(
            array(
                'mock' => array(
                    'function_exists'    => true,
                    'ssh2_connect'       => true,
                    'ssh2_auth_pubkey_file' => true,
                    'ssh2_auth_password' => true,
                    'ssh2_sftp'          => true,
                    'ssh2_sftp_stat'     => true,
                ),
                'private' => '',
                'public'  => '',
            ),
            array(
                'exception' => false
            )
        );

        return $data;
    }

    public static function getTestWrite()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'fopen'  => false,
                    'fwrite' => false
                )
            ),
            array(
                'case'   => 'Fails to open the strem',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'fopen'  => true,
                    'fwrite' => false
                )
            ),
            array(
                'case'   => 'Fails to write',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'fopen'  => true,
                    'fwrite' => 100
                )
            ),
            array(
                'case'   => 'Everything works fine',
                'result' => 100
            )
        );

        return $data;
    }

    public static function getTestDelete()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_unlink' => false
                )
            ),
            array(
                'case'   => 'Unlink returns false',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_unlink' => 'exception'
                )
            ),
            array(
                'case'   => 'Unlink throws an exception',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_unlink' => true
                )
            ),
            array(
                'case'   => 'Everything works fine',
                'result' => true
            )
        );

        return $data;
    }

    public static function getTestCopy()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'write' => false
                )
            ),
            array(
                'case'   => 'Write return false',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'write' => true
                )
            ),
            array(
                'case'   => 'Write return true',
                'result' => true
            )
        );

        return $data;
    }

    public static function getTestMove()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'copy'   => false,
                    'delete' => false
                )
            ),
            array(
                'case'   => 'Copy returns false',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'copy'   => true,
                    'delete' => false
                )
            ),
            array(
                'case'   => 'Delete returns false',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'copy'   => true,
                    'delete' => true
                )
            ),
            array(
                'case'   => 'Everything went ok',
                'result' => true
            )
        );

        return $data;
    }

    public static function getTestChmod()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_chmod' => false,
                    'ssh2_exec'       => false,
                    'function_exists' => true
                )
            ),
            array(
                'case'   => 'ssh2_sftp_chmod exists and returns false',
                'result' => false,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_chmod' => true,
                    'ssh2_exec'       => false,
                    'function_exists' => true
                )
            ),
            array(
                'case'   => 'ssh2_sftp_chmod exists and returns true',
                'result' => true,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_chmod' => false,
                    'ssh2_exec'       => false,
                    'function_exists' => false
                )
            ),
            array(
                'case'   => 'does not exist, chmod fails',
                'result' => false,
                'count'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_chmod' => false,
                    'ssh2_exec'       => true,
                    'function_exists' => false
                )
            ),
            array(
                'case'   => 'does not exist, chmod succeds',
                'result' => true,
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
                    'ssh2_sftp_mkdir' => false
                )
            ),
            array(
                'case'   => 'Mkdir returns false',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ssh2_sftp_mkdir' => true
                )
            ),
            array(
                'case'   => 'Mkdir returns true',
                'result' => true
            )
        );

        return $data;
    }

    public static function getTestRmdir()
    {
        $paths = array(
            'site/first/second/third',
            'site/dummy/test.txt',
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => '',
                    'ssh2_sftp_rmdir' => array(true)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site/first/second/third',
                'recursive'  => false
            ),
            array(
                'case'   => 'Trying to delete a leaf dir, not recursive',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => '',
                    'ssh2_sftp_rmdir' => array(false)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site/first/second/third',
                'recursive'  => false
            ),
            array(
                'case'   => 'Trying to delete a leaf dir, not recursive - Error',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => '',
                    'ssh2_sftp_rmdir' => array(false)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site',
                'recursive'  => false
            ),
            array(
                'case'   => 'Trying to delete a parent dir, not recursive',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => true,
                    'ssh2_sftp_rmdir' => array(false)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site/dummy/test.txt',
                'recursive'  => true
            ),
            array(
                'case'   => 'Path points to a file, recursive',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => true,
                    'ssh2_sftp_rmdir' => array(false)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site/dummy/test.txt',
                'recursive'  => false
            ),
            array(
                'case'   => 'Path points to a file, not recursive',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => true,
                    'ssh2_sftp_rmdir' => array(true, true, true, true, true)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site',
                'recursive'  => true
            ),
            array(
                'case'   => 'Trying to delete a parent dir, recursive',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'delete'    => true,
                    'ssh2_sftp_rmdir' => array(true, true, false, true, true)
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/site',
                'recursive'  => true
            ),
            array(
                'case'   => 'Trying to delete a parent dir, recursive, but something goes wrong',
                'result' => false
            )
        );

        return $data;
    }

    public function getTestTranslatePath()
    {
        $data[] = array(
            array(
                'path' => 'foobar/dummy.txt'
            ),
            array(
                'case'   => 'Normal path',
                'result' => '/site/foobar/dummy.txt'
            )
        );

        $data[] = array(
            array(
                'path' => 'foobar\dummy.txt'
            ),
            array(
                'case'   => 'Path with backslashes',
                'result' => '/site/foobar/dummy.txt'
            )
        );

        return $data;
    }
}
