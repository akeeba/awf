<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver\Sqlite;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * SQLite rejects invalid SQL when the statement is prepared, i.e. in setQuery(). The error must still surface as a
 * RuntimeException from execute() (or whatever executes the query), never as a raw PDOException from setQuery().
 */
final class SqliteExecuteErrorTest extends AbstractIntegrationTestCase
{
    public static function badQueries(): array
    {
        $rows = [];

        foreach (['execute', 'loadResult', 'loadObjectList', 'loadAssoc', 'getIterator'] as $method) {
            $rows["syntax error, $method"]  = ['SELEC 1', $method, 'syntax error'];
            $rows["missing table, $method"] = ['SELECT * FROM no_such_table', $method, 'no such table: no_such_table'];
        }

        return $rows;
    }

    private function build(): Sqlite
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('The pdo_sqlite extension is not available.');
        }

        return new Sqlite(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'tst_']);
    }

    #[DataProvider('badQueries')]
    public function testErrorsSurfaceFromExecutionAsRuntimeException(string $sql, string $method, string $sqliteMessage): void
    {
        $db = $this->build();

        // setQuery() itself must not throw.
        $db->setQuery($sql);

        try {
            $db->$method();
            self::fail('The query must throw.');
        } catch (RuntimeException $e) {
            self::assertNotInstanceOf(\PDOException::class, $e);
            self::assertSame(1, $e->getCode(), 'The code is SQLite\'s own error number.');
            self::assertStringContainsString($sqliteMessage, $e->getMessage());
            self::assertStringEndsWith(' SQL=' . $sql, $e->getMessage());
            self::assertInstanceOf(\PDOException::class, $e->getPrevious());
        }
    }

    public function testErrorDoesNotLeakIntoTheNextQuery(): void
    {
        $db = $this->build();

        $db->setQuery('SELEC 1');

        try {
            $db->execute();
            self::fail('The query must throw.');
        } catch (RuntimeException $e) {
            // Expected.
        }

        $db->setQuery('SELECT 42');
        self::assertSame('42', (string) $db->loadResult());

        // A second bad query reports itself, not the first one.
        $db->setQuery('SELECT * FROM no_such_table');

        try {
            $db->execute();
            self::fail('The query must throw.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('no_such_table', $e->getMessage());
            self::assertStringNotContainsString('SELEC 1', $e->getMessage());
        }

        $db->setQuery('SELECT 7');
        self::assertSame('7', (string) $db->loadResult());
    }

    public function testConnectedDoesNotForgetAPendingPrepareError(): void
    {
        $db = $this->build();

        $db->setQuery('SELEC 1');

        // connected() runs its own query and must restore the state of the failed one.
        self::assertTrue($db->connected());

        try {
            $db->execute();
            self::fail('The query must throw.');
        } catch (RuntimeException $e) {
            self::assertSame(1, $e->getCode());
            self::assertInstanceOf(\PDOException::class, $e->getPrevious());
        }
    }
}
