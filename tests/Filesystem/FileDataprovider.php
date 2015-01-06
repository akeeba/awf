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
}