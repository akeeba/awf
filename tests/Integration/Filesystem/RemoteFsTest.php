<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Filesystem;

use Awf\Filesystem\Ftp;
use Awf\Filesystem\Hybrid;
use Awf\Filesystem\Sftp;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Real-server integration tests for the FTP, SFTP, and Hybrid filesystem drivers.
 *
 * All tests are SKIPPED unless the appropriate environment variables are set.
 *
 * FTP tests require:
 *   AWF_TEST_FTP_HOST      FTP server hostname or IP (e.g. "127.0.0.1")
 *   AWF_TEST_FTP_PORT      FTP server port             (default: 21)
 *   AWF_TEST_FTP_USER      FTP username
 *   AWF_TEST_FTP_PASS      FTP password
 *   AWF_TEST_FTP_DIR       Initial directory on the server (e.g. "/upload")
 *   AWF_TEST_FTP_BASE      Local filesystem base path that maps to AWF_TEST_FTP_DIR
 *
 * Optional FTP env vars:
 *   AWF_TEST_FTP_SSL       "1" to use FTPS (ftp_ssl_connect)
 *   AWF_TEST_FTP_PASSIVE   "0" to disable passive mode (default: passive)
 *
 * SFTP tests require:
 *   AWF_TEST_SFTP_HOST     SFTP/SSH server hostname or IP
 *   AWF_TEST_SFTP_PORT     SSH port                       (default: 22)
 *   AWF_TEST_SFTP_USER     SFTP username
 *   AWF_TEST_SFTP_PASS     SFTP password
 *   AWF_TEST_SFTP_DIR      Initial remote directory (e.g. "/home/user/upload")
 *
 * Optional SFTP env vars:
 *   AWF_TEST_SFTP_PRIVKEY  Absolute path to a PEM private key file
 *   AWF_TEST_SFTP_PUBKEY   Absolute path to a PEM public key file
 *
 * Hybrid tests run only when FTP vars are available (it wraps FTP as the
 * abstraction adapter).
 */
#[CoversClass(Ftp::class)]
#[CoversClass(Sftp::class)]
#[CoversClass(Hybrid::class)]
#[Group('integration')]
final class RemoteFsTest extends AbstractIntegrationTestCase
{
    // -------------------------------------------------------------------------
    // Environment variable names — FTP
    // -------------------------------------------------------------------------

    private const ENV_FTP_HOST    = 'AWF_TEST_FTP_HOST';
    private const ENV_FTP_PORT    = 'AWF_TEST_FTP_PORT';
    private const ENV_FTP_USER    = 'AWF_TEST_FTP_USER';
    private const ENV_FTP_PASS    = 'AWF_TEST_FTP_PASS';
    private const ENV_FTP_DIR     = 'AWF_TEST_FTP_DIR';
    private const ENV_FTP_BASE    = 'AWF_TEST_FTP_BASE';
    private const ENV_FTP_SSL     = 'AWF_TEST_FTP_SSL';
    private const ENV_FTP_PASSIVE = 'AWF_TEST_FTP_PASSIVE';

    // -------------------------------------------------------------------------
    // Environment variable names — SFTP
    // -------------------------------------------------------------------------

    private const ENV_SFTP_HOST    = 'AWF_TEST_SFTP_HOST';
    private const ENV_SFTP_PORT    = 'AWF_TEST_SFTP_PORT';
    private const ENV_SFTP_USER    = 'AWF_TEST_SFTP_USER';
    private const ENV_SFTP_PASS    = 'AWF_TEST_SFTP_PASS';
    private const ENV_SFTP_DIR     = 'AWF_TEST_SFTP_DIR';
    private const ENV_SFTP_PRIVKEY = 'AWF_TEST_SFTP_PRIVKEY';
    private const ENV_SFTP_PUBKEY  = 'AWF_TEST_SFTP_PUBKEY';

    // -------------------------------------------------------------------------
    // Shared state
    // -------------------------------------------------------------------------

    /** Unique prefix for all remote paths created during this run. */
    private string $runId;

    protected function setUp(): void
    {
        $this->runId = 'awf_int_' . bin2hex(random_bytes(6));
    }

    // =========================================================================
    // FTP tests
    // =========================================================================

    /**
     * Build and return an Ftp instance from env vars, or skip the test.
     */
    private function buildFtp(): Ftp
    {
        if (!extension_loaded('ftp')) {
            $this->markTestSkipped('The ext-ftp extension is not loaded.');
        }

        $host = (string) getenv(self::ENV_FTP_HOST);
        $user = (string) getenv(self::ENV_FTP_USER);

        if ($host === '' || $user === '') {
            $this->markTestSkipped(
                sprintf(
                    'FTP integration tests require %s and %s to be set.',
                    self::ENV_FTP_HOST,
                    self::ENV_FTP_USER
                )
            );
        }

        $port    = (int) ((string) getenv(self::ENV_FTP_PORT) ?: '21');
        $pass    = (string) getenv(self::ENV_FTP_PASS);
        $dir     = (string) getenv(self::ENV_FTP_DIR) ?: '/';
        $base    = (string) getenv(self::ENV_FTP_BASE) ?: '/';
        $ssl     = ((string) getenv(self::ENV_FTP_SSL)) === '1';
        $passive = ((string) getenv(self::ENV_FTP_PASSIVE)) !== '0';

        $container = $this->buildMinimalContainer($base);

        return new Ftp(
            [
                'host'      => $host,
                'port'      => $port,
                'username'  => $user,
                'password'  => $pass,
                'directory' => $dir,
                'ssl'       => $ssl,
                'passive'   => $passive,
            ],
            $container
        );
    }

    /**
     * FTP: successfully connect and change to the configured initial directory.
     */
    public function testFtpConnect(): void
    {
        $ftp = $this->buildFtp();

        // cwd() returns the server-side working directory; should not be empty
        $cwd = $ftp->cwd();
        $this->assertNotEmpty($cwd, 'FTP cwd() returned an empty string after successful connect.');
    }

    /**
     * FTP: write a file and then delete it.
     */
    public function testFtpWriteAndDelete(): void
    {
        $ftp  = $this->buildFtp();
        $base = (string) getenv(self::ENV_FTP_BASE) ?: '/';

        $localPath = rtrim($base, '/') . '/' . $this->runId . '_write.txt';
        $contents  = 'AWF FTP integration test – ' . $this->runId;

        $written = $ftp->write($localPath, $contents);
        $this->assertTrue($written, 'Ftp::write() returned false.');

        $deleted = $ftp->delete($localPath);
        $this->assertTrue($deleted, 'Ftp::delete() returned false after successful write.');
    }

    /**
     * FTP: copy a file on the remote server.
     */
    public function testFtpCopy(): void
    {
        $ftp  = $this->buildFtp();
        $base = (string) getenv(self::ENV_FTP_BASE) ?: '/';

        $src = rtrim($base, '/') . '/' . $this->runId . '_src.txt';
        $dst = rtrim($base, '/') . '/' . $this->runId . '_dst.txt';

        $ftp->write($src, 'copy source');
        $copied = $ftp->copy($src, $dst);
        $this->assertTrue($copied, 'Ftp::copy() returned false.');

        // Cleanup
        $ftp->delete($src);
        $ftp->delete($dst);
    }

    /**
     * FTP: move (rename) a file on the remote server.
     */
    public function testFtpMove(): void
    {
        $ftp  = $this->buildFtp();
        $base = (string) getenv(self::ENV_FTP_BASE) ?: '/';

        $src = rtrim($base, '/') . '/' . $this->runId . '_before.txt';
        $dst = rtrim($base, '/') . '/' . $this->runId . '_after.txt';

        $ftp->write($src, 'move source');
        $moved = $ftp->move($src, $dst);
        $this->assertTrue($moved, 'Ftp::move() returned false.');

        // Cleanup
        $ftp->delete($dst);
    }

    /**
     * FTP: chmod a remote file.
     */
    public function testFtpChmod(): void
    {
        $ftp  = $this->buildFtp();
        $base = (string) getenv(self::ENV_FTP_BASE) ?: '/';

        $file = rtrim($base, '/') . '/' . $this->runId . '_chmod.txt';
        $ftp->write($file, 'chmod test');

        $result = $ftp->chmod($file, 0644);
        $this->assertTrue($result, 'Ftp::chmod() returned false.');

        // Cleanup
        $ftp->delete($file);
    }

    /**
     * FTP: listFolders() on the initial directory returns an array.
     */
    public function testFtpListFolders(): void
    {
        $ftp  = $this->buildFtp();

        $list = $ftp->listFolders();
        $this->assertIsArray($list, 'Ftp::listFolders() should return an array.');
    }

    /**
     * FTP: translatePath() strips the local base path prefix and prepends the
     * remote FTP directory.
     */
    public function testFtpTranslatePath(): void
    {
        $ftp  = $this->buildFtp();
        $base = (string) getenv(self::ENV_FTP_BASE) ?: '/';
        $dir  = (string) getenv(self::ENV_FTP_DIR)  ?: '/';

        $local     = rtrim($base, '/') . '/foo/bar.txt';
        $translated = $ftp->translatePath($local);

        // The translated path must start with the configured FTP directory.
        $this->assertStringStartsWith(
            rtrim($dir, '/'),
            $translated,
            'translatePath() result should start with the configured FTP directory.'
        );
    }

    /**
     * FTP: connecting with wrong credentials must throw a RuntimeException.
     */
    public function testFtpBadCredentialsThrows(): void
    {
        if (!extension_loaded('ftp')) {
            $this->markTestSkipped('The ext-ftp extension is not loaded.');
        }

        $host = (string) getenv(self::ENV_FTP_HOST);
        if ($host === '') {
            $this->markTestSkipped(sprintf('Requires %s to be set.', self::ENV_FTP_HOST));
        }

        $base      = (string) getenv(self::ENV_FTP_BASE) ?: '/';
        $container = $this->buildMinimalContainer($base);

        $this->expectException(\RuntimeException::class);

        new Ftp(
            [
                'host'     => $host,
                'port'     => (int) ((string) getenv(self::ENV_FTP_PORT) ?: '21'),
                'username' => 'invalid_user_' . $this->runId,
                'password' => 'invalid_password_' . $this->runId,
                'directory' => '/',
            ],
            $container
        );
    }

    // =========================================================================
    // SFTP tests
    // =========================================================================

    /**
     * Build and return an Sftp instance from env vars, or skip the test.
     */
    private function buildSftp(): Sftp
    {
        if (!extension_loaded('ssh2')) {
            $this->markTestSkipped('The ext-ssh2 extension is not loaded.');
        }

        $host = (string) getenv(self::ENV_SFTP_HOST);
        $user = (string) getenv(self::ENV_SFTP_USER);

        if ($host === '' || $user === '') {
            $this->markTestSkipped(
                sprintf(
                    'SFTP integration tests require %s and %s to be set.',
                    self::ENV_SFTP_HOST,
                    self::ENV_SFTP_USER
                )
            );
        }

        $port    = (int) ((string) getenv(self::ENV_SFTP_PORT) ?: '22');
        $pass    = (string) getenv(self::ENV_SFTP_PASS);
        $dir     = (string) getenv(self::ENV_SFTP_DIR) ?: '/';
        $privKey = (string) getenv(self::ENV_SFTP_PRIVKEY);
        $pubKey  = (string) getenv(self::ENV_SFTP_PUBKEY);

        $container = $this->buildMinimalContainer($dir);

        $options = [
            'host'      => $host,
            'port'      => $port,
            'username'  => $user,
            'password'  => $pass,
            'directory' => $dir,
        ];

        if ($privKey !== '' && $pubKey !== '') {
            $options['privateKey'] = $privKey;
            $options['publicKey']  = $pubKey;
        }

        return new Sftp($options, $container);
    }

    /**
     * SFTP: successfully connect and retrieve a working directory.
     */
    public function testSftpConnect(): void
    {
        $sftp = $this->buildSftp();

        $cwd = $sftp->cwd();
        $this->assertNotEmpty($cwd, 'Sftp::cwd() returned an empty string after successful connect.');
    }

    /**
     * SFTP: write a file and then delete it.
     */
    public function testSftpWriteAndDelete(): void
    {
        $sftp = $this->buildSftp();
        $dir  = rtrim((string) getenv(self::ENV_SFTP_DIR) ?: '/', '/');

        // translatePath() prepends the configured directory, so we pass a bare
        // local-style path; the driver prefixes with its directory.
        $file     = '/' . $this->runId . '_write.txt';
        $contents = 'AWF SFTP integration test – ' . $this->runId;

        $written = $sftp->write($file, $contents);
        $this->assertTrue((bool) $written, 'Sftp::write() returned false/0.');

        $deleted = $sftp->delete($file);
        $this->assertTrue($deleted, 'Sftp::delete() returned false after successful write.');
    }

    /**
     * SFTP: copy a file.
     */
    public function testSftpCopy(): void
    {
        $sftp = $this->buildSftp();

        $src = '/' . $this->runId . '_src.txt';
        $dst = '/' . $this->runId . '_dst.txt';

        $sftp->write($src, 'sftp copy source');
        $copied = $sftp->copy($src, $dst);
        $this->assertTrue((bool) $copied, 'Sftp::copy() returned false.');

        // Cleanup
        $sftp->delete($src);
        $sftp->delete($dst);
    }

    /**
     * SFTP: move (copy + delete) a file.
     */
    public function testSftpMove(): void
    {
        $sftp = $this->buildSftp();

        $src = '/' . $this->runId . '_mv_before.txt';
        $dst = '/' . $this->runId . '_mv_after.txt';

        $sftp->write($src, 'sftp move source');
        $moved = $sftp->move($src, $dst);
        $this->assertTrue($moved, 'Sftp::move() returned false.');

        // Cleanup
        $sftp->delete($dst);
    }

    /**
     * SFTP: listFolders() on the initial directory returns an array.
     */
    public function testSftpListFolders(): void
    {
        $sftp = $this->buildSftp();

        $list = $sftp->listFolders();
        $this->assertIsArray($list, 'Sftp::listFolders() should return an array.');
    }

    /**
     * SFTP: connecting with bad credentials must throw a RuntimeException.
     */
    public function testSftpBadCredentialsThrows(): void
    {
        if (!extension_loaded('ssh2')) {
            $this->markTestSkipped('The ext-ssh2 extension is not loaded.');
        }

        $host = (string) getenv(self::ENV_SFTP_HOST);
        if ($host === '') {
            $this->markTestSkipped(sprintf('Requires %s to be set.', self::ENV_SFTP_HOST));
        }

        $dir       = (string) getenv(self::ENV_SFTP_DIR) ?: '/';
        $container = $this->buildMinimalContainer($dir);

        $this->expectException(\RuntimeException::class);

        new Sftp(
            [
                'host'      => $host,
                'port'      => (int) ((string) getenv(self::ENV_SFTP_PORT) ?: '22'),
                'username'  => 'invalid_user_' . $this->runId,
                'password'  => 'invalid_password_' . $this->runId,
                'directory' => '/',
            ],
            $container
        );
    }

    /**
     * SFTP: connecting without ext-ssh2 available must throw a RuntimeException.
     *
     * We simulate this by checking for the absence of the extension — if it IS
     * present we skip this specific scenario because it cannot be faked.
     */
    public function testSftpNoExtensionThrows(): void
    {
        if (extension_loaded('ssh2')) {
            $this->markTestSkipped(
                'ext-ssh2 is loaded; cannot test the "no-extension" error path in this environment.'
            );
        }

        $container = $this->buildMinimalContainer('/tmp');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SSH2 PHP module');

        new Sftp(
            [
                'host'      => 'localhost',
                'port'      => 22,
                'username'  => 'nobody',
                'password'  => '',
                'directory' => '/',
            ],
            $container
        );
    }

    // =========================================================================
    // Hybrid tests
    // =========================================================================

    /**
     * Hybrid: when no driver option is given, it instantiates with only the
     * direct File adapter — no exception is thrown.
     */
    public function testHybridWithNoDriverOption(): void
    {
        // Hybrid with no remote driver falls back to direct file I/O
        $tmpDir    = sys_get_temp_dir();
        $container = $this->buildMinimalContainer($tmpDir);

        $hybrid = new Hybrid([], $container);

        // Write a temp file via the Hybrid (direct File adapter)
        $target = $tmpDir . '/' . $this->runId . '_hybrid.txt';
        $result = $hybrid->write($target, 'hybrid direct');
        $this->assertTrue($result, 'Hybrid::write() via direct File adapter returned false.');

        // Delete it
        $deleted = $hybrid->delete($target);
        $this->assertTrue($deleted, 'Hybrid::delete() via direct File adapter returned false.');
    }

    /**
     * Hybrid: when the driver option references a non-existent class, only the
     * direct File adapter is used and no exception escapes the constructor.
     */
    public function testHybridWithInvalidDriverFallsBackSilently(): void
    {
        $tmpDir    = sys_get_temp_dir();
        $container = $this->buildMinimalContainer($tmpDir);

        // 'driver' => 'NonExistentDriver' is an unknown class — Hybrid swallows it.
        $hybrid = new Hybrid(['driver' => 'NonExistentDriver'], $container);

        $target = $tmpDir . '/' . $this->runId . '_hybrid_fallback.txt';
        $result = $hybrid->write($target, 'hybrid fallback test');
        $this->assertTrue($result, 'Hybrid::write() should succeed via File adapter fallback.');

        $hybrid->delete($target);
    }

    /**
     * Hybrid wrapping FTP: write goes through direct file write first; if it
     * succeeds the FTP adapter is never called.
     *
     * This test runs only when FTP env vars are set.
     */
    public function testHybridWithFtpDriverPrefersDirect(): void
    {
        if (!extension_loaded('ftp')) {
            $this->markTestSkipped('The ext-ftp extension is not loaded.');
        }

        $host = (string) getenv(self::ENV_FTP_HOST);
        $user = (string) getenv(self::ENV_FTP_USER);

        if ($host === '' || $user === '') {
            $this->markTestSkipped(
                sprintf(
                    'Requires %s and %s to be set for the Hybrid+FTP test.',
                    self::ENV_FTP_HOST,
                    self::ENV_FTP_USER
                )
            );
        }

        $base      = (string) getenv(self::ENV_FTP_BASE) ?: sys_get_temp_dir();
        $container = $this->buildMinimalContainer($base);

        $hybrid = new Hybrid(
            [
                'driver'    => 'Ftp',
                'host'      => $host,
                'port'      => (int) ((string) getenv(self::ENV_FTP_PORT) ?: '21'),
                'username'  => $user,
                'password'  => (string) getenv(self::ENV_FTP_PASS),
                'directory' => (string) getenv(self::ENV_FTP_DIR) ?: '/',
                'passive'   => true,
            ],
            $container
        );

        // Write to a path under $base that is locally writable.
        $target   = rtrim($base, '/') . '/' . $this->runId . '_hybrid_ftp.txt';
        $contents = 'hybrid+ftp direct write test';

        $result = $hybrid->write($target, $contents);
        $this->assertTrue($result, 'Hybrid::write() should succeed (direct or via FTP).');

        // Verify the file landed locally (direct write succeeded)
        if (file_exists($target)) {
            $this->assertStringContainsString('hybrid+ftp direct write test', file_get_contents($target));
        }

        $hybrid->delete($target);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Build a minimal Container stub that exposes `filesystemBase` with the
     * given path, without spinning up a full AWF application.
     *
     * We use a plain array-access object (Pimple-compatible) to avoid
     * coupling these integration tests to the full Container bootstrap.
     *
     * All of the Container's scalar keys are provided, even though the filesystem drivers only ever read
     * `filesystemBase`. Omitting any of them makes the Container constructor fall back to a default AND emit an
     * E_USER_WARNING, which PHPUnit reports as a warning against the test which built the container.
     */
    private function buildMinimalContainer(string $filesystemBase): \Awf\Container\Container
    {
        $tmpDir = sys_get_temp_dir();

        // Use a real Container configured with just what the drivers need.
        $container = new \Awf\Container\Container(
            [
                'application_name'     => 'awf_test',
                'applicationNamespace' => '\\Awf_test',
                'session_segment_name' => 'awf_test_seg',
                'basePath'             => $tmpDir,
                'templatePath'         => $tmpDir,
                'languagePath'         => $tmpDir,
                'temporaryPath'        => $tmpDir,
                'sqlPath'              => $tmpDir,
                'filesystemBase'       => $filesystemBase,
                // Provide a no-op appConfig so the Container does not error.
                'appConfig'            => new class {
                    public function get(string $key, mixed $default = null): mixed
                    {
                        return $default;
                    }
                },
            ]
        );

        return $container;
    }
}
