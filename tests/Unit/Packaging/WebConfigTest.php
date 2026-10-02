<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Packaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The IIS web.config deny files under src/ must use request filtering. ASP.NET's <authorization> element only
 * applies to managed handlers, so it does not stop direct requests for PHP (or any other) files.
 */
class WebConfigTest extends TestCase
{
    public static function webConfigProvider(): array
    {
        $root     = dirname(__DIR__, 3) . '/src';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        $files    = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'web.config') {
                $files[substr($file->getPathname(), strlen($root) + 1)] = [$file->getPathname()];
            }
        }

        return $files;
    }

    public function testThereIsAtLeastOneWebConfig(): void
    {
        self::assertNotEmpty(self::webConfigProvider());
    }

    #[DataProvider('webConfigProvider')]
    public function testWebConfigDoesNotUseAspNetAuthorization(string $path): void
    {
        $contents = (string) file_get_contents($path);

        self::assertStringNotContainsString('<system.web>', $contents);
        self::assertStringNotContainsString('<authorization>', $contents);
    }

    #[DataProvider('webConfigProvider')]
    public function testWebConfigUsesRequestFiltering(string $path): void
    {
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('requestFiltering', $contents);
        self::assertMatchesRegularExpression('/<fileExtensions\s+allowUnlisted="false"/', $contents);
    }

    #[DataProvider('webConfigProvider')]
    public function testWebConfigIsWellFormedXml(string $path): void
    {
        $previous = libxml_use_internal_errors(true);
        $xml      = simplexml_load_file($path);
        libxml_use_internal_errors($previous);

        self::assertNotFalse($xml);
    }
}
