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
}