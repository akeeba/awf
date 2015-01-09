<?php

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
}