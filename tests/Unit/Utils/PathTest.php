<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\Path;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/awf_path_test_' . uniqid('', true);

        if (!is_dir($this->tempDir))
        {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tempDir);
    }

    private function recursiveDelete(string $path): void
    {
        if (!file_exists($path))
        {
            return;
        }

        if (is_file($path) || is_link($path))
        {
            @unlink($path);

            return;
        }

        $entries = scandir($path) ?: [];

        foreach ($entries as $entry)
        {
            if ($entry === '.' || $entry === '..')
            {
                continue;
            }

            $this->recursiveDelete($path . '/' . $entry);
        }

        @rmdir($path);
    }

    // -------------------------------------------------------------------------
    // clean — using a forward-slash separator so the result is platform stable
    // -------------------------------------------------------------------------

    public static function cleanForwardSlashProvider(): array
    {
        return [
            'no change needed'              => ['/foo/bar/baz', '/foo/bar/baz'],
            'collapses double slashes'      => ['/foo//bar', '/foo/bar'],
            'collapses many slashes'        => ['/foo/////bar', '/foo/bar'],
            'converts backslashes'          => ['\\foo\\bar', '/foo/bar'],
            'mixed slashes'                 => ['/foo\\bar//baz', '/foo/bar/baz'],
            'trailing double slash'         => ['/foo/bar//', '/foo/bar/'],
            'leading double slash'          => ['//foo/bar', '/foo/bar'],
            'single segment'                => ['foo', 'foo'],
            'relative dot segments kept'    => ['/foo/./bar', '/foo/./bar'],
            'relative dotdot segments kept' => ['/foo/../bar', '/foo/../bar'],
        ];
    }

    #[DataProvider('cleanForwardSlashProvider')]
    public function testCleanWithForwardSlash(string $input, string $expected): void
    {
        self::assertSame($expected, Path::clean($input, '/'));
    }

    public static function cleanBackslashProvider(): array
    {
        return [
            'collapses double backslashes' => ['C:\\\\foo\\\\bar', 'C:\\foo\\bar'],
            'converts forward slashes'     => ['C:/foo/bar', 'C:\\foo\\bar'],
            'mixed slashes'                => ['C:/foo\\\\bar//baz', 'C:\\foo\\bar\\baz'],
        ];
    }

    #[DataProvider('cleanBackslashProvider')]
    public function testCleanWithBackslash(string $input, string $expected): void
    {
        self::assertSame($expected, Path::clean($input, '\\'));
    }

    public function testCleanTrimsSurroundingWhitespace(): void
    {
        self::assertSame('/foo/bar', Path::clean('   /foo/bar   ', '/'));
    }

    public function testCleanPreservesUncPrefixWithBackslashSeparator(): void
    {
        // A UNC path keeps its leading double backslash, with the remainder collapsed.
        self::assertSame('\\\\server\\share\\folder', Path::clean('\\\\server\\share\\\\folder', '\\'));
    }

    public function testCleanDoesNotApplyUncPrefixWithForwardSlashSeparator(): void
    {
        // The UNC special-casing only triggers for the backslash separator.
        self::assertSame('/server/share', Path::clean('\\\\server\\share', '/'));
    }

    public function testCleanDefaultSeparatorMatchesDirectorySeparator(): void
    {
        $cleaned = Path::clean('foo' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'bar');

        self::assertSame('foo' . DIRECTORY_SEPARATOR . 'bar', $cleaned);
    }

    // -------------------------------------------------------------------------
    // check — the relative-path guard fires before any Application access
    // -------------------------------------------------------------------------

    public function testCheckThrowsOnRelativeDotDotPath(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Use of relative paths not permitted');

        Path::check('/foo/../bar');
    }

    public function testCheckThrowsWithCode20(): void
    {
        try
        {
            Path::check('../etc/passwd');

            self::fail('Expected exception was not thrown.');
        }
        catch (\Exception $e)
        {
            self::assertSame(20, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // find
    // -------------------------------------------------------------------------

    public function testFindReturnsFullPathForExistingFile(): void
    {
        $file = $this->tempDir . '/needle.txt';
        file_put_contents($file, 'x');

        $result = Path::find($this->tempDir, 'needle.txt');

        self::assertSame(realpath($file), $result);
    }

    public function testFindReturnsFalseWhenFileMissing(): void
    {
        self::assertFalse(Path::find($this->tempDir, 'does-not-exist.txt'));
    }

    public function testFindAcceptsSinglePathString(): void
    {
        $file = $this->tempDir . '/single.txt';
        file_put_contents($file, 'x');

        // A bare string (not an array) must be coerced to an array internally.
        self::assertSame(realpath($file), Path::find($this->tempDir, 'single.txt'));
    }

    public function testFindSearchesMultiplePathsInOrder(): void
    {
        $dirA = $this->tempDir . '/a';
        $dirB = $this->tempDir . '/b';
        mkdir($dirA);
        mkdir($dirB);

        $target = $dirB . '/file.txt';
        file_put_contents($target, 'x');

        $result = Path::find([$dirA, $dirB], 'file.txt');

        self::assertSame(realpath($target), $result);
    }

    public function testFindReturnsFirstMatchWhenFileInMultiplePaths(): void
    {
        $dirA = $this->tempDir . '/a';
        $dirB = $this->tempDir . '/b';
        mkdir($dirA);
        mkdir($dirB);

        file_put_contents($dirA . '/file.txt', 'first');
        file_put_contents($dirB . '/file.txt', 'second');

        $result = Path::find([$dirA, $dirB], 'file.txt');

        self::assertSame(realpath($dirA . '/file.txt'), $result);
    }

    public function testFindReturnsFalseForEmptyPathSet(): void
    {
        self::assertFalse(Path::find([], 'whatever.txt'));
    }

    public function testFindLocatesFileInNestedSubdirectoryName(): void
    {
        $sub = $this->tempDir . '/nested';
        mkdir($sub);
        $file = $sub . '/deep.txt';
        file_put_contents($file, 'x');

        self::assertSame(realpath($file), Path::find($this->tempDir, 'nested/deep.txt'));
    }

    public function testFindGuardsAgainstDirectoryTraversal(): void
    {
        // Create a sibling file outside the registered search directory.
        $outside = $this->tempDir . '/secret.txt';
        file_put_contents($outside, 'secret');

        $registered = $this->tempDir . '/registered';
        mkdir($registered);

        // Trying to climb out of the registered directory must fail the
        // substr() containment check and return false.
        self::assertFalse(Path::find($registered, '../secret.txt'));
    }
}
