<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;


/**
 * Schema for the MySQL / MariaDB family. Connection details come from AWF_TEST_MYSQL_DSN / _USER / _PASS.
 */
abstract class MysqlHyphenatedPrefixTestCase extends HyphenatedPrefixTestCase
{
    protected function createSchema(): void
    {
        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__widgets') . ' (
                widget_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title     VARCHAR(191) NOT NULL DEFAULT \'\',
                PRIMARY KEY (widget_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__branches') . ' (
                id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(191) NOT NULL DEFAULT \'\',
                lft   INT          NOT NULL DEFAULT 0,
                rgt   INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__flags') . ' (
                flag_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                flag    TINYINT      NOT NULL DEFAULT 0,
                PRIMARY KEY (flag_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();
    }

    /**
     * @return array{host: string, port: int, dbname: string, user: string, pass: string}
     */
    protected function mysqlConnection(): array
    {
        $conn = $this->requireMysql();

        return self::parseDsn($conn['dsn'], 3306) + ['user' => $conn['user'], 'pass' => $conn['pass']];
    }
}
