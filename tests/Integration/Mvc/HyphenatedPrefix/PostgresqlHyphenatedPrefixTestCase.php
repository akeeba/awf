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

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__flags') . ' (
                flag_id SERIAL   PRIMARY KEY,
                flag    SMALLINT NOT NULL DEFAULT 0
            )'
        )->execute();
    }

    public function testGetTableColumnsRaisesNoDeprecationForColumnWithoutDefault(): void
    {
        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__nodefaults') . ' (
                id   SERIAL PRIMARY KEY,
                note VARCHAR(191)
            )'
        )->execute();

        $deprecations = [];

        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;

                return true;
            },
            E_DEPRECATED | E_USER_DEPRECATED
        );

        try {
            $columns = $this->db->getTableColumns('#__nodefaults', false);
        } finally {
            restore_error_handler();
            $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->qn('#__nodefaults'))->execute();
        }

        self::assertSame(['id', 'note'], array_keys($columns));
        self::assertNull($columns['note']->Default);
        self::assertSame([], $deprecations);
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
