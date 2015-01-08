<?php

class FtpDataprovider
{
    public static function getTestConnect()
    {
        // SSL connection works fine
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_ssl_connect' => true,
                    'ftp_connect'     => true,
                    'ftp_login'       => true,
                    'ftp_chdir'       => true,
                ),
                'ssl' => true
            ),
            array(
                'exception' => false
            )
        );

        // SSL throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_ssl_connect' => false,
                    'ftp_connect'     => true,
                    'ftp_login'       => true,
                    'ftp_chdir'       => true,
                ),
                'ssl' => true
            ),
            array(
                'exception' => true
            )
        );

        // Standard connection
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_ssl_connect' => true,
                    'ftp_connect'     => true,
                    'ftp_login'       => true,
                    'ftp_chdir'       => true,
                ),
                'ssl' => false
            ),
            array(
                'exception' => false
            )
        );

        // Standard connection throws an error
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_ssl_connect' => true,
                    'ftp_connect'     => false,
                    'ftp_login'       => true,
                    'ftp_chdir'       => true,
                ),
                'ssl' => false
            ),
            array(
                'exception' => true
            )
        );

        // Error while logging in
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_ssl_connect' => true,
                    'ftp_connect'     => true,
                    'ftp_login'       => false,
                    'ftp_chdir'       => true,
                ),
                'ssl' => false
            ),
            array(
                'exception' => true
            )
        );

        // Error while changing the directory
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_ssl_connect' => true,
                    'ftp_connect'     => true,
                    'ftp_login'       => true,
                    'ftp_chdir'       => false,
                ),
                'ssl' => false
            ),
            array(
                'exception' => true
            )
        );

        return $data;
    }

    public static function getTest__destruct()
    {
        $data[] = array(
            array(
                'connection' => '',
            ),
            array(
                'case'  => 'Connection is not set',
                'count' => 0
            )
        );

        $data[] = array(
            array(
                'connection' => 'foobar',
            ),
            array(
                'case'  => 'Connection is set',
                'count' => 1
            )
        );

        return $data;
    }

    public static function getTestWrite()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_fput' => true
                )
            ),
            array(
                'case'  => 'FTP put successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_fput' => false
                )
            ),
            array(
                'case'  => 'FTP put failed',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestDelete()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_delete' => true
                )
            ),
            array(
                'case'  => 'FTP delete successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_delete' => false
                )
            ),
            array(
                'case'  => 'FTP delete failed',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestCopy()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_fget' => true,
                    'ftp_fput' => true,
                )
            ),
            array(
                'case'  => 'FTP copy successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_fget' => false,
                    'ftp_fput' => true,
                )
            ),
            array(
                'case'  => 'FTP copy failed to read the file',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_fget' => true,
                    'ftp_fput' => false,
                )
            ),
            array(
                'case'  => 'FTP copy failed to put the file',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestMove()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_rename' => true
                )
            ),
            array(
                'case'  => 'FTP move successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_rename' => false
                )
            ),
            array(
                'case'  => 'FTP move failed',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestChmod()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_chmod' => 0644
                )
            ),
            array(
                'case'  => 'FTP chmod successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_chmod' => false
                )
            ),
            array(
                'case'  => 'FTP chmod failed',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestMkdir()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'ftp_mkdir' => array()
                ),
                'path' => 'vfs://root/site/'
            ),
            array(
                'case'   => 'Destination directory is the starting directory',
                'result' => true,
                'mkdir'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_mkdir' => array(true)
                ),
                'path' => 'dummy'
            ),
            array(
                'case'   => 'Creating a single directory',
                'result' => true,
                'mkdir'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_mkdir' => array(false)
                ),
                'path' => 'dummy'
            ),
            array(
                'case'   => 'Creation fails',
                'result' => false,
                'mkdir'  => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'ftp_mkdir' => array(true, true)
                ),
                'path' => 'dummy/foobar'
            ),
            array(
                'case'   => 'Creating multiple directories',
                'result' => true,
                'mkdir'  => 2
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
                    'ftp_rmdir' => array(true)
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
                    'ftp_rmdir' => array(false)
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
                    'ftp_rmdir' => array(false)
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
                    'ftp_rmdir' => array(false)
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
                    'ftp_rmdir' => array(false)
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
                    'ftp_rmdir' => array(true, true, true, true, true)
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
                    'ftp_rmdir' => array(true, true, false, true, true)
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
}