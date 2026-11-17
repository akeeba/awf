<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;


/**
 * Schema for PostgreSQL. Connection details come from AWF_TEST_PG_DSN / _USER / _PASS.
 */
abstract class PostgresqlHyphenatedPrefixTestCase extends HyphenatedPrefixTestCase
{
    protected function createSchema(): void
    {
        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__widgets') . ' (
                widget_id SERIAL       PRIMARY KEY,
                title     VARCHAR(191) NOT NULL DEFAULT \'\'
            )'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__branches') . ' (
                id    SERIAL       PRIMARY KEY,
                title VARCHAR(191) NOT NULL DEFAULT \'\',
                lft   INT          NOT NULL DEFAULT 0,
                rgt   INT          NOT NULL DEFAULT 0
            )'
        )->execute();
    }

    /**
     * @return array{host: string, port: int, dbname: string, user: string, pass: string}
     */
    protected function pgConnection(): array
    {
        $conn = $this->requirePostgresql();

        return self::parseDsn($conn['dsn'], 5432) + ['user' => $conn['user'], 'pass' => $conn['pass']];
    }
}
