<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Input;

use Awf\Input\Cli;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cli::class)]
class CliTest extends TestCase
{
    /** @var array Original $_SERVER['argv'] value */
    private array $originalArgv;

    protected function setUp(): void
    {
        $this->originalArgv = $_SERVER['argv'] ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->originalArgv;
    }

    /**
     * Build a Cli instance from a synthetic argv array.
     * The first element is always the "script name" (executable).
     */
    private function makeCliFromArgv(array $argv): Cli
    {
        $_SERVER['argv'] = $argv;

        return new Cli();
    }

    // -------------------------------------------------------------------------
    // Executable & args properties
    // -------------------------------------------------------------------------

    public function testExecutableIsFirstArgvElement(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar']);

        self::assertSame('/usr/bin/script.php', $cli->executable);
    }

    public function testArgsStartEmpty(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php']);

        self::assertSame([], $cli->args);
    }

    // -------------------------------------------------------------------------
    // Long options: --key=value
    // -------------------------------------------------------------------------

    public function testLongOptionWithEqualsSign(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar']);

        self::assertSame('bar', $cli->get('foo', null, 'raw'));
    }

    public function testLongOptionWithEqualsSignMultiple(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--name=Alice', '--count=5']);

        self::assertSame('Alice', $cli->get('name', null, 'raw'));
        self::assertSame('5', $cli->get('count', null, 'raw'));
    }

    public function testLongOptionValueContainingEquals(): void
    {
        // --url=https://example.com/path?a=1 — value should include the extra equals sign
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--url=https://example.com/path?a=1']);

        self::assertSame('https://example.com/path?a=1', $cli->get('url', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // Long options: --flag (boolean) and --flag value
    // -------------------------------------------------------------------------

    public function testLongFlagWithNoValueDefaultsToTrue(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--verbose']);

        self::assertTrue($cli->get('verbose', null, 'raw'));
    }

    public function testLongFlagFollowedByValue(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--output', 'result.txt']);

        self::assertSame('result.txt', $cli->get('output', null, 'raw'));
    }

    public function testLongFlagFollowedByAnotherFlag(): void
    {
        // --verbose --dry-run: both should be true; second is not consumed as a value
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--verbose', '--dry-run']);

        self::assertTrue($cli->get('verbose', null, 'raw'));
        self::assertTrue($cli->get('dry-run', null, 'raw'));
    }

    public function testLongFlagFollowedByDashArgDoesNotConsumeValue(): void
    {
        // --flag -x: '-x' starts with '-' so it should NOT be consumed as the flag's value
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--flag', '-x']);

        self::assertTrue($cli->get('flag', null, 'raw'));
        self::assertTrue($cli->get('x', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // Short options: -k=value
    // -------------------------------------------------------------------------

    public function testShortOptionWithEqualsSign(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '-n=5']);

        self::assertSame('5', $cli->get('n', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // Short options: -abc (combined flags) and -a value
    // -------------------------------------------------------------------------

    public function testShortCombinedFlagsAllSetToTrue(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '-abc']);

        self::assertTrue($cli->get('a', null, 'raw'));
        self::assertTrue($cli->get('b', null, 'raw'));
        self::assertTrue($cli->get('c', null, 'raw'));
    }

    public function testShortSingleFlagFollowedByValue(): void
    {
        // Single short flag followed by a non-dash argument consumes the value
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '-f', 'myfile.txt']);

        self::assertSame('myfile.txt', $cli->get('f', null, 'raw'));
    }

    public function testShortSingleFlagFollowedByDashArgDoesNotConsumeValue(): void
    {
        // -f --bar: '--bar' starts with '-', so it should NOT be consumed as -f's value
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '-f', '--bar']);

        self::assertTrue($cli->get('f', null, 'raw'));
        self::assertTrue($cli->get('bar', null, 'raw'));
    }

    public function testMultipleShortFlagsNotCombined(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '-v', '-q']);

        self::assertTrue($cli->get('v', null, 'raw'));
        self::assertTrue($cli->get('q', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // Positional arguments
    // -------------------------------------------------------------------------

    public function testPositionalArgsAreCollected(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', 'arg1', 'arg2', 'arg3']);

        self::assertSame(['arg1', 'arg2', 'arg3'], $cli->args);
    }

    public function testPositionalArgsMixedWithOptions(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar', 'positional', '-v', 'another']);

        // 'positional' is a plain arg; 'another' is consumed as -v's value
        self::assertContains('positional', $cli->args);
        self::assertSame('another', $cli->get('v', null, 'raw'));
    }

    public function testPositionalArgAfterDoubleDashLikeOption(): void
    {
        // All positional args end up in args; no option key detected
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', 'plain1', 'plain2']);

        self::assertSame(['plain1', 'plain2'], $cli->args);
        self::assertSame(0, count($cli->getData()));
    }

    // -------------------------------------------------------------------------
    // getData / count
    // -------------------------------------------------------------------------

    public function testGetDataReturnsAllParsedOptions(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar', '--count=3', '-v']);

        $data = $cli->getData();

        self::assertArrayHasKey('foo', $data);
        self::assertArrayHasKey('count', $data);
        self::assertArrayHasKey('v', $data);
        self::assertSame('bar', $data['foo']);
        self::assertSame('3', $data['count']);
        self::assertTrue($data['v']);
    }

    public function testCountReflectsNumberOfParsedOptions(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar', '--baz=qux']);

        self::assertSame(2, count($cli));
    }

    public function testCountIsZeroWithNoOptions(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php']);

        self::assertSame(0, count($cli));
    }

    // -------------------------------------------------------------------------
    // get() with default and filter
    // -------------------------------------------------------------------------

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar']);

        self::assertSame('fallback', $cli->get('missing', 'fallback', 'raw'));
    }

    public function testGetWithIntFilterReturnsInteger(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--count=42']);

        self::assertSame(42, $cli->get('count', 0, 'int'));
    }

    // -------------------------------------------------------------------------
    // set() and def()
    // -------------------------------------------------------------------------

    public function testSetOverridesValue(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=original']);
        $cli->set('foo', 'overridden');

        self::assertSame('overridden', $cli->get('foo', null, 'raw'));
    }

    public function testDefDoesNotOverrideExistingValue(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=original']);
        $cli->def('foo', 'default');

        self::assertSame('original', $cli->get('foo', null, 'raw'));
    }

    public function testDefSetsValueWhenKeyAbsent(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php']);
        $cli->def('newkey', 'newvalue');

        self::assertSame('newvalue', $cli->get('newkey', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // Serialization / unserialization
    // -------------------------------------------------------------------------

    public function testSerializeAndUnserializeRoundTrip(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--foo=bar', 'positional']);

        $serialized = $cli->serialize();

        /** @var Cli $cli2 */
        $cli2 = new Cli();
        $cli2->unserialize($serialized);

        self::assertSame('/usr/bin/script.php', $cli2->executable);
        self::assertSame(['positional'], $cli2->args);
        self::assertSame('bar', $cli2->get('foo', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testEmptyArgvOnlyExecutable(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php']);

        self::assertSame('/usr/bin/script.php', $cli->executable);
        self::assertSame([], $cli->args);
        self::assertSame([], $cli->getData());
    }

    public function testLongOptionEmptyValue(): void
    {
        $cli = $this->makeCliFromArgv(['/usr/bin/script.php', '--key=']);

        // Value after '=' is empty string
        self::assertSame('', $cli->get('key', null, 'raw'));
    }

    public function testMixOfLongShortAndPositional(): void
    {
        $cli = $this->makeCliFromArgv([
            '/usr/bin/script.php',
            '--verbose',
            '-n=3',
            '--output=result.txt',
            'file1.txt',
            'file2.txt',
        ]);

        self::assertTrue($cli->get('verbose', null, 'raw'));
        self::assertSame('3', $cli->get('n', null, 'raw'));
        self::assertSame('result.txt', $cli->get('output', null, 'raw'));
        self::assertSame(['file1.txt', 'file2.txt'], $cli->args);
    }

    // -------------------------------------------------------------------------
    // Data provider–driven argv parsing
    // -------------------------------------------------------------------------

    public static function longOptionProvider(): array
    {
        return [
            'simple --key=value'        => [['script', '--key=value'], 'key', 'value'],
            'numeric value'             => [['script', '--count=99'], 'count', '99'],
            'value with spaces (raw)'   => [['script', '--msg=hello world'], 'msg', 'hello world'],
        ];
    }

    #[DataProvider('longOptionProvider')]
    public function testLongOptionParsing(array $argv, string $key, string $expectedValue): void
    {
        $cli = $this->makeCliFromArgv($argv);

        self::assertSame($expectedValue, $cli->get($key, null, 'raw'));
    }

    public static function shortFlagProvider(): array
    {
        return [
            'single flag -v'   => [['script', '-v'], 'v', true],
            'combined -abc: a' => [['script', '-abc'], 'a', true],
            'combined -abc: b' => [['script', '-abc'], 'b', true],
            'combined -abc: c' => [['script', '-abc'], 'c', true],
        ];
    }

    #[DataProvider('shortFlagProvider')]
    public function testShortFlagParsing(array $argv, string $key, bool $expectedValue): void
    {
        $cli = $this->makeCliFromArgv($argv);

        self::assertSame($expectedValue, $cli->get($key, null, 'raw'));
    }
}
