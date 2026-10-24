<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database\Driver;

use Awf\Database\Driver\Mysqli;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MysqliDriverLifecycleTest extends TestCase
{
    public function testFailedConnectionDoesNotKeepInvalidMysqliObject(): void
    {
        if (!Mysqli::isSupported()) {
            self::markTestSkipped('The mysqli extension is not available');
        }

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        });

        $db = new Mysqli([
            'driver'   => 'mysqli',
            'host'     => '127.0.0.1:1',
            'user'     => 'root',
            'password' => 'wrong',
            'database' => '',
            'prefix'   => '',
        ]);

        try {
            try {
                $db->connect();
                self::fail('Connecting to a closed port must fail');
            } catch (RuntimeException $e) {
                self::assertSame('Could not connect to MySQL.', $e->getMessage());
            }

            self::assertNull($db->getConnection(), 'A failed connection must not leave an invalid mysqli object');

            $db->disconnect();
            unset($db);
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);
    }
}
