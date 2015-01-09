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
}