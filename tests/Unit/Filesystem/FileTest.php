<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Filesystem;

use Awf\Filesystem\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Filesystem\File — local filesystem operations only.
 * FTP/SFTP/Hybrid adapters are out of scope and deferred to the integration phase.
 */
class FileTest extends TestCase
{
    private string $tmpDir;
    private File $file;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/awf_file_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);

        $this->file = new File([]);
    }

    protected function tearDown(): void
    {
        // Clean up temp dir recursively
        $this->rmdirRecursive($this->tmpDir);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->rmdirRecursive($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function testConstructorAcceptsNullContainer(): void
    {
        $f = new File([], null);
        self::assertInstanceOf(File::class, $f);
    }

    public function testConstructorIgnoresOptions(): void
    {
        // Options are ignored by this adapter; passing arbitrary values must not throw.
        $f = new File(['foo' => 'bar', 'baz' => 42]);
        self::assertInstanceOf(File::class, $f);
    }

    // -------------------------------------------------------------------------
    // write
    // -------------------------------------------------------------------------

    public function testWriteCreatesFileWithContents(): void
    {
        $path = $this->tmpDir . '/hello.txt';
        $result = $this->file->write($path, 'hello world');

        self::assertTrue($result);
        self::assertFileExists($path);
        self::assertSame('hello world', file_get_contents($path));
    }

    public function testWriteOverwritesExistingFile(): void
    {
        $path = $this->tmpDir . '/overwrite.txt';
        $this->file->write($path, 'original');
        $result = $this->file->write($path, 'updated');

        self::assertTrue($result);
        self::assertSame('updated', file_get_contents($path));
    }

    public function testWriteEmptyStringCreatesEmptyFile(): void
    {
        $path = $this->tmpDir . '/empty.txt';
        $result = $this->file->write($path, '');

        // file_put_contents('', '') returns 0 (bytes written), which is !== false → true
        self::assertTrue($result);
        self::assertFileExists($path);
        self::assertSame('', file_get_contents($path));
    }

    public function testWriteReturnsFalseForUnwritablePath(): void
    {
        // A path inside a non-existent deep directory that cannot be created.
        $path = $this->tmpDir . '/no_such_dir/deeper/file.txt';
        $result = $this->file->write($path, 'data');

        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    public function testDeleteRemovesExistingFile(): void
    {
        $path = $this->tmpDir . '/todelete.txt';
        file_put_contents($path, 'bye');

        $result = $this->file->delete($path);

        self::assertTrue($result);
        self::assertFileDoesNotExist($path);
    }

    public function testDeleteReturnsFalseForNonExistentFile(): void
    {
        $result = $this->file->delete($this->tmpDir . '/ghost.txt');
        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // copy
    // -------------------------------------------------------------------------

    public function testCopyDuplicatesFile(): void
    {
        $src = $this->tmpDir . '/source.txt';
        $dst = $this->tmpDir . '/dest.txt';
        file_put_contents($src, 'copy me');

        $result = $this->file->copy($src, $dst);

        self::assertTrue($result);
        self::assertFileExists($src);
        self::assertFileExists($dst);
        self::assertSame('copy me', file_get_contents($dst));
    }

    public function testCopyReturnsFalseWhenSourceMissing(): void
    {
        $result = $this->file->copy(
            $this->tmpDir . '/nonexistent.txt',
            $this->tmpDir . '/dest.txt'
        );
        self::assertFalse($result);
    }

    public function testCopyOverwritesExistingDestination(): void
    {
        $src = $this->tmpDir . '/src.txt';
        $dst = $this->tmpDir . '/dst.txt';
        file_put_contents($src, 'new content');
        file_put_contents($dst, 'old content');

        $this->file->copy($src, $dst);

        self::assertSame('new content', file_get_contents($dst));
    }

    // -------------------------------------------------------------------------
    // move
    // -------------------------------------------------------------------------

    public function testMoveRenamesFile(): void
    {
        $src = $this->tmpDir . '/moveme.txt';
        $dst = $this->tmpDir . '/moved.txt';
        file_put_contents($src, 'move me');

        $result = $this->file->move($src, $dst);

        self::assertTrue($result);
        self::assertFileDoesNotExist($src);
        self::assertFileExists($dst);
        self::assertSame('move me', file_get_contents($dst));
    }

    public function testMoveReturnsFalseWhenSourceMissing(): void
    {
        $result = $this->file->move(
            $this->tmpDir . '/ghost.txt',
            $this->tmpDir . '/target.txt'
        );
        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // chmod
    // -------------------------------------------------------------------------

    public function testChmodChangesFilePermissions(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('chmod is not meaningful on Windows.');
        }

        $path = $this->tmpDir . '/perms.txt';
        file_put_contents($path, 'data');

        $result = $this->file->chmod($path, 0600);

        self::assertTrue($result);
        // Read the mode bits (mask with 0777 to ignore file-type bits)
        self::assertSame(0600, fileperms($path) & 0777);
    }

    public function testChmodReturnsFalseForNonExistentFile(): void
    {
        $result = $this->file->chmod($this->tmpDir . '/ghost.txt', 0644);
        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // cwd
    // -------------------------------------------------------------------------

    public function testCwdReturnsNonEmptyString(): void
    {
        $cwd = $this->file->cwd();
        self::assertIsString($cwd);
        self::assertNotEmpty($cwd);
    }

    // -------------------------------------------------------------------------
    // mkdir
    // -------------------------------------------------------------------------

    public function testMkdirCreatesDirectory(): void
    {
        $dir = $this->tmpDir . '/newdir';
        $result = $this->file->mkdir($dir);

        self::assertTrue($result);
        self::assertDirectoryExists($dir);
    }

    public function testMkdirCreatesIntermediateDirectories(): void
    {
        $dir = $this->tmpDir . '/a/b/c';
        $result = $this->file->mkdir($dir);

        self::assertTrue($result);
        self::assertDirectoryExists($dir);
    }

    public function testMkdirReturnsFalseWhenDirectoryAlreadyExists(): void
    {
        // mkdir on an already-existing directory returns false (PHP native behaviour,
        // which the adapter passes through via @mkdir).
        $dir = $this->tmpDir;
        $result = $this->file->mkdir($dir);
        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // rmdir
    // -------------------------------------------------------------------------

    public function testRmdirNonRecursiveRemovesEmptyDirectory(): void
    {
        $dir = $this->tmpDir . '/emptydir';
        mkdir($dir);

        $result = $this->file->rmdir($dir, false);

        self::assertTrue($result);
        self::assertDirectoryDoesNotExist($dir);
    }

    public function testRmdirNonRecursiveReturnsFalseForNonEmptyDirectory(): void
    {
        $dir = $this->tmpDir . '/nonempty';
        mkdir($dir);
        file_put_contents($dir . '/file.txt', 'x');

        $result = $this->file->rmdir($dir, false);

        self::assertFalse($result);
        self::assertDirectoryExists($dir);
    }

    public function testRmdirRecursiveRemovesDirectoryTree(): void
    {
        $dir = $this->tmpDir . '/tree';
        mkdir($dir . '/sub', 0755, true);
        file_put_contents($dir . '/file.txt', 'a');
        file_put_contents($dir . '/sub/child.txt', 'b');

        $result = $this->file->rmdir($dir, true);

        self::assertTrue($result);
        self::assertDirectoryDoesNotExist($dir);
    }

    public function testRmdirRecursiveOnFileDelegatesToDelete(): void
    {
        // Passing a file path to rmdir(…, true) should delete the file.
        $path = $this->tmpDir . '/lone.txt';
        file_put_contents($path, 'x');

        $result = $this->file->rmdir($path, true);

        self::assertTrue($result);
        self::assertFileDoesNotExist($path);
    }

    public function testRmdirDefaultIsRecursive(): void
    {
        $dir = $this->tmpDir . '/autotree';
        mkdir($dir . '/sub', 0755, true);
        file_put_contents($dir . '/sub/deep.txt', 'z');

        // Default $recursive = true
        $result = $this->file->rmdir($dir);

        self::assertTrue($result);
        self::assertDirectoryDoesNotExist($dir);
    }

    // -------------------------------------------------------------------------
    // translatePath
    // -------------------------------------------------------------------------

    public function testTranslatePathReturnsInputUnchanged(): void
    {
        $path = '/some/absolute/path/to/file.txt';
        self::assertSame($path, $this->file->translatePath($path));
    }

    public function testTranslatePathPreservesRelativePath(): void
    {
        $path = 'relative/path/file.php';
        self::assertSame($path, $this->file->translatePath($path));
    }

    // -------------------------------------------------------------------------
    // listFolders
    // -------------------------------------------------------------------------

    public function testListFoldersReturnsOnlySubdirectories(): void
    {
        mkdir($this->tmpDir . '/alpha', 0755);
        mkdir($this->tmpDir . '/beta', 0755);
        file_put_contents($this->tmpDir . '/file.txt', 'x');

        $result = $this->file->listFolders($this->tmpDir);

        // Files must not appear; only directory names (sorted)
        self::assertContains('alpha', $result);
        self::assertContains('beta', $result);
        self::assertNotContains('file.txt', $result);
    }

    public function testListFoldersReturnsSortedList(): void
    {
        mkdir($this->tmpDir . '/zebra', 0755);
        mkdir($this->tmpDir . '/apple', 0755);
        mkdir($this->tmpDir . '/mango', 0755);

        $result = $this->file->listFolders($this->tmpDir);

        // asort() preserves keys; normalise both sides to sequential keys for comparison.
        $sorted = array_values($result);
        sort($sorted);
        self::assertSame($sorted, array_values($result));
    }

    public function testListFoldersThrowsForNonExistentDirectory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->file->listFolders($this->tmpDir . '/nonexistent_dir');
    }

    public function testListFoldersExcludesDotEntries(): void
    {
        $result = $this->file->listFolders($this->tmpDir);

        // Dot and dot-dot must never appear.
        self::assertNotContains('.', $result);
        self::assertNotContains('..', $result);
    }

    public function testListFoldersReturnsEmptyArrayForEmptyDirectory(): void
    {
        $empty = $this->tmpDir . '/emptydir';
        mkdir($empty);

        $result = $this->file->listFolders($empty);

        self::assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // directoryFiles
    // -------------------------------------------------------------------------

    public function testDirectoryFilesReturnsFiles(): void
    {
        file_put_contents($this->tmpDir . '/a.php', '<?php');
        file_put_contents($this->tmpDir . '/b.php', '<?php');
        mkdir($this->tmpDir . '/sub', 0755);

        $result = $this->file->directoryFiles($this->tmpDir, '.', false, false);

        self::assertContains('a.php', $result);
        self::assertContains('b.php', $result);
        // Sub-directory must not appear in the file listing.
        self::assertNotContains('sub', $result);
    }

    public function testDirectoryFilesThrowsForNonDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->file->directoryFiles($this->tmpDir . '/not_a_dir');
    }

    public function testDirectoryFilesReturnsFullPaths(): void
    {
        file_put_contents($this->tmpDir . '/full.txt', 'x');

        $result = $this->file->directoryFiles($this->tmpDir, '.', false, true);

        self::assertNotEmpty($result);
        // Every returned item must be an absolute path.
        foreach ($result as $item) {
            self::assertStringStartsWith($this->tmpDir, $item);
        }
    }

    public function testDirectoryFilesRecursiveFindsNestedFiles(): void
    {
        mkdir($this->tmpDir . '/sub', 0755);
        file_put_contents($this->tmpDir . '/top.txt', 'a');
        file_put_contents($this->tmpDir . '/sub/nested.txt', 'b');

        $result = $this->file->directoryFiles($this->tmpDir, '.', true, false);

        self::assertContains('top.txt', $result);
        self::assertContains('nested.txt', $result);
    }

    public function testDirectoryFilesFilterByExtension(): void
    {
        file_put_contents($this->tmpDir . '/a.php', '<?php');
        file_put_contents($this->tmpDir . '/b.txt', 'text');

        $result = $this->file->directoryFiles($this->tmpDir, '\.php$', false, false);

        self::assertContains('a.php', $result);
        self::assertNotContains('b.txt', $result);
    }

    public function testDirectoryFilesNaturalSort(): void
    {
        file_put_contents($this->tmpDir . '/file10.txt', '');
        file_put_contents($this->tmpDir . '/file2.txt', '');
        file_put_contents($this->tmpDir . '/file1.txt', '');

        $result = $this->file->directoryFiles($this->tmpDir, '.', false, false, [], [], true);

        // Natural sort: file1, file2, file10
        $index1 = array_search('file1.txt', $result);
        $index2 = array_search('file2.txt', $result);
        $index10 = array_search('file10.txt', $result);

        self::assertLessThan($index2, $index1);
        self::assertLessThan($index10, $index2);
    }

    public function testDirectoryFilesExcludesByName(): void
    {
        file_put_contents($this->tmpDir . '/keep.txt', '');
        file_put_contents($this->tmpDir . '/CVS', '');

        $result = $this->file->directoryFiles($this->tmpDir, '.', false, false);

        self::assertContains('keep.txt', $result);
        self::assertNotContains('CVS', $result);
    }

    public function testDirectoryFilesExcludesByFilter(): void
    {
        file_put_contents($this->tmpDir . '/normal.txt', '');
        file_put_contents($this->tmpDir . '/.hidden', '');

        $result = $this->file->directoryFiles($this->tmpDir, '.', false, false);

        self::assertContains('normal.txt', $result);
        self::assertNotContains('.hidden', $result);
    }

    public function testDirectoryFilesAlphaSort(): void
    {
        file_put_contents($this->tmpDir . '/charlie.txt', '');
        file_put_contents($this->tmpDir . '/alpha.txt', '');
        file_put_contents($this->tmpDir . '/bravo.txt', '');

        $result = $this->file->directoryFiles($this->tmpDir, '.', false, false, [], [], false);

        $sorted = $result;
        sort($sorted);
        self::assertSame($sorted, $result);
    }
}
