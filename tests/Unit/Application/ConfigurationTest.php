<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Application;

use Awf\Application\Configuration;
use Awf\Container\Container;
use Awf\Filesystem\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    private ?Container $container = null;
    private string $tmpDir = '';

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/awf_config_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);
        mkdir($this->tmpDir . '/assets/private', 0755, true);

        $this->container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $this->tmpDir,
            'languagePath'         => $this->tmpDir,
            'temporaryPath'        => $this->tmpDir,
            'templatePath'         => $this->tmpDir,
            'filesystemBase'       => $this->tmpDir,
        ]);

        // Override the fileSystem service with a simple File adapter so
        // saveConfiguration() can write to the temp directory.
        $tmpDir    = $this->tmpDir;
        $container = $this->container;
        $this->container['fileSystem'] = new File([], $container);
    }

    protected function tearDown(): void
    {
        $this->container = null;
        $this->rmdirRecursive($this->tmpDir);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function makeConfig(mixed $data = null): Configuration
    {
        return new Configuration($this->container, $data);
    }

    // -------------------------------------------------------------------------
    // Constructor / Registry integration
    // -------------------------------------------------------------------------

    public function testConstructorWithNoDataCreatesEmptyRegistry(): void
    {
        $config = $this->makeConfig();

        self::assertNull($config->get('any.key'));
    }

    public function testConstructorWithArrayPreloadsData(): void
    {
        $config = $this->makeConfig(['foo' => 'bar', 'num' => 42]);

        self::assertSame('bar', $config->get('foo'));
        self::assertSame(42, $config->get('num'));
    }

    public function testConstructorWithObjectPreloadsData(): void
    {
        $obj      = new \stdClass();
        $obj->key = 'value';
        $config   = $this->makeConfig($obj);

        self::assertSame('value', $config->get('key'));
    }

    public function testContainerIsAccessible(): void
    {
        $config = $this->makeConfig();

        self::assertSame($this->container, $config->getContainer());
    }

    // -------------------------------------------------------------------------
    // get / set with defaults
    // -------------------------------------------------------------------------

    public function testGetReturnDefaultWhenKeyAbsent(): void
    {
        $config = $this->makeConfig();

        self::assertSame('default_val', $config->get('missing', 'default_val'));
    }

    public function testGetReturnNullWhenKeyAbsentAndNoDefault(): void
    {
        $config = $this->makeConfig();

        self::assertNull($config->get('missing'));
    }

    public function testSetAndGet(): void
    {
        $config = $this->makeConfig();
        $config->set('section.key', 'hello');

        self::assertSame('hello', $config->get('section.key'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $config = $this->makeConfig(['x' => 'original']);
        $config->set('x', 'updated');

        self::assertSame('updated', $config->get('x'));
    }

    public function testGetNestedKeyWithDotNotation(): void
    {
        $config = $this->makeConfig(['db' => ['host' => 'localhost', 'port' => 3306]]);

        self::assertSame('localhost', $config->get('db.host'));
        self::assertSame(3306, $config->get('db.port'));
    }

    // -------------------------------------------------------------------------
    // setDefaultPath / getDefaultPath
    // -------------------------------------------------------------------------

    public function testGetDefaultPathFallsBackToContainerBasePath(): void
    {
        $config = $this->makeConfig();

        $expected = $this->tmpDir . '/assets/private/config.php';
        self::assertSame($expected, $config->getDefaultPath());
    }

    public function testSetDefaultPathOverridesDefault(): void
    {
        $config   = $this->makeConfig();
        $custom   = $this->tmpDir . '/custom_config.php';
        $config->setDefaultPath($custom);

        self::assertSame($custom, $config->getDefaultPath());
    }

    public function testGetDefaultPathCachesResult(): void
    {
        $config = $this->makeConfig();

        $first  = $config->getDefaultPath();
        $second = $config->getDefaultPath();

        self::assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // loadConfiguration
    // -------------------------------------------------------------------------

    public function testLoadConfigurationFromExplicitPath(): void
    {
        $filePath = $this->tmpDir . '/test_config.php';
        // The file format is: PHP die header on line 1, JSON on line 2+.
        file_put_contents($filePath, "<?php die; ?>\n" . json_encode(['name' => 'TestApp', 'debug' => true]));

        $config = $this->makeConfig();
        $config->loadConfiguration($filePath);

        self::assertSame('TestApp', $config->get('name'));
        self::assertTrue($config->get('debug'));
    }

    public function testLoadConfigurationUsesDefaultPathWhenNoArgument(): void
    {
        $defaultPath = $this->tmpDir . '/assets/private/config.php';
        file_put_contents($defaultPath, "<?php die; ?>\n" . json_encode(['env' => 'production']));

        $config = $this->makeConfig();
        $config->loadConfiguration();

        self::assertSame('production', $config->get('env'));
    }

    public function testLoadConfigurationResetsExistingData(): void
    {
        $config = $this->makeConfig(['old_key' => 'old_value']);

        $filePath = $this->tmpDir . '/reset_test.php';
        file_put_contents($filePath, "<?php die; ?>\n" . json_encode(['new_key' => 'new_value']));

        $config->loadConfiguration($filePath);

        self::assertNull($config->get('old_key'));
        self::assertSame('new_value', $config->get('new_key'));
    }

    public function testLoadConfigurationFromNonExistentFileResultsInEmptyRegistry(): void
    {
        $config = $this->makeConfig(['existing' => 'data']);
        $config->loadConfiguration($this->tmpDir . '/does_not_exist.php');

        // The data is reset at the top of loadConfiguration, so existing data is gone too.
        self::assertNull($config->get('existing'));
    }

    public function testLoadConfigurationWithSingleLineFileLeavesEmptyRegistry(): void
    {
        // A file without a newline means there is no JSON part to load.
        $filePath = $this->tmpDir . '/single_line.php';
        file_put_contents($filePath, '<?php die; ?>');

        $config = $this->makeConfig(['key' => 'value']);
        $config->loadConfiguration($filePath);

        self::assertNull($config->get('key'));
    }

    // -------------------------------------------------------------------------
    // saveConfiguration
    // -------------------------------------------------------------------------

    public function testSaveConfigurationWritesHeaderAndJson(): void
    {
        $filePath = $this->tmpDir . '/save_test.php';
        $config   = $this->makeConfig(['app' => 'MyApp', 'version' => '1.0']);
        $config->saveConfiguration($filePath);

        self::assertFileExists($filePath);
        $raw = file_get_contents($filePath);
        // First line must be the PHP die guard.
        self::assertStringStartsWith("<?php die; ?>\n", $raw);
        // The rest must be parseable JSON.
        $lines = explode("\n", $raw, 2);
        $data  = json_decode($lines[1], true);
        self::assertSame('MyApp', $data['app']);
        self::assertSame('1.0', $data['version']);
    }

    public function testSaveConfigurationUsesDefaultPathWhenNoArgument(): void
    {
        $config = $this->makeConfig(['site' => 'example.com']);
        $config->saveConfiguration(); // writes to basePath/assets/private/config.php

        $defaultPath = $this->tmpDir . '/assets/private/config.php';
        self::assertFileExists($defaultPath);
    }

    public function testSaveAndLoadRoundTrip(): void
    {
        $filePath = $this->tmpDir . '/roundtrip.php';
        $original = [
            'database' => [
                'host'   => 'db.example.com',
                'port'   => 5432,
                'name'   => 'mydb',
            ],
            'debug'    => false,
            'version'  => '2.3.4',
        ];

        $config = $this->makeConfig($original);
        $config->saveConfiguration($filePath);

        $loaded = $this->makeConfig();
        $loaded->loadConfiguration($filePath);

        self::assertSame('db.example.com', $loaded->get('database.host'));
        self::assertSame(5432, $loaded->get('database.port'));
        self::assertSame('mydb', $loaded->get('database.name'));
        self::assertFalse($loaded->get('debug'));
        self::assertSame('2.3.4', $loaded->get('version'));
    }

    public function testSaveConfigurationThrowsRuntimeExceptionWhenWriteFails(): void
    {
        // Use a path in a non-existent directory so the write will fail.
        $config = $this->makeConfig(['key' => 'val']);

        $this->expectException(RuntimeException::class);
        $config->saveConfiguration($this->tmpDir . '/nonexistent_dir/config.php');
    }

    // -------------------------------------------------------------------------
    // Registry interface behaviour
    // -------------------------------------------------------------------------

    public function testExistsReturnsTrueForSetKey(): void
    {
        $config = $this->makeConfig(['foo' => 'bar']);

        self::assertTrue($config->exists('foo'));
    }

    public function testExistsReturnsFalseForMissingKey(): void
    {
        $config = $this->makeConfig();

        self::assertFalse($config->exists('nonexistent'));
    }

    public function testToArrayReturnsAllData(): void
    {
        $data   = ['a' => 1, 'b' => ['c' => 3]];
        $config = $this->makeConfig($data);

        self::assertSame($data, $config->toArray());
    }

    public function testDefSetsDefaultOnlyWhenKeyAbsent(): void
    {
        $config = $this->makeConfig(['existing' => 'original']);
        $config->def('existing', 'new_default');
        $config->def('fresh', 'default_value');

        self::assertSame('original', $config->get('existing'));
        self::assertSame('default_value', $config->get('fresh'));
    }
}
