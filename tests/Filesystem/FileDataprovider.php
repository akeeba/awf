<?php

class FileDataprovider
{
    public static function getTestWrite()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file_put_contents' => 48
                )
            ),
            array(
                'case'   => 'Write was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file_put_contents' => false
                )
            ),
            array(
                'case'   => 'Write had an error',
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
                    'unlink' => true
                )
            ),
            array(
                'case'   => 'Delete was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'unlink' => false
                )
            ),
            array(
                'case'   => 'Delete had an error',
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
                    'copy' => true
                )
            ),
            array(
                'case'   => 'Copy was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'copy' => false
                )
            ),
            array(
                'case'   => 'Copy had an error',
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
                    'rename' => true
                )
            ),
            array(
                'case'   => 'Move was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'rename' => false
                )
            ),
            array(
                'case'   => 'Move had an error',
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
                    'chmod' => true
                )
            ),
            array(
                'case'   => 'Chmod was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'chmod' => false
                )
            ),
            array(
                'case'   => 'Chmod had an error',
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
                    'mkdir' => true
                )
            ),
            array(
                'case'   => 'Mkdir was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'mkdir' => false
                )
            ),
            array(
                'case'   => 'Mkdir had an error',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestRmdir()
    {
        $paths = array(
            'foobar/first/second/third',
            'foobar/dummy/test.txt',
        );

        $data[] = array(
            array(
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/foobar/first/second/third',
                'recursive'  => false
            ),
            array(
                'case'   => 'Trying to delete a leaf dir, not recursive',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/foobar',
                'recursive'  => false
            ),
            array(
                'case'   => 'Trying to delete a parent dir, not recursive',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/foobar/dummy/test.txt',
                'recursive'  => true
            ),
            array(
                'case'   => 'Path points to a file, recursive',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/foobar/dummy/test.txt',
                'recursive'  => false
            ),
            array(
                'case'   => 'Path points to a file, not recursive',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'root/foobar',
                'recursive'  => true
            ),
            array(
                'case'   => 'Trying to delete a parent dir, recursive',
                'result' => true
            )
        );

        return $data;
    }

    public static function getTestListFolders()
    {
        $paths = array(
            'foobar/dummy/test.txt',
            'foobar/first/second/third',
            'foobar/file.txt',
            'foobar/.',
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getcwd' => ''
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => 'vfs://root/foobar'
            ),
            array(
                'case'   => 'Passed folder with folders and files',
                'result' => array('dummy', 'first')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getcwd' => 'vfs://root/foobar'
                ),
                'filesystem' => \Awf\Tests\Stubs\Utils\VfsHelper::createArrayDir($paths),
                'path'       => null
            ),
            array(
                'case'   => 'Dir not passed',
                'result' => array('dummy', 'first')
            )
        );

        return $data;
    }
}