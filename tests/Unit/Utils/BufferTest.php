<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\Buffer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BufferTest extends TestCase
{
    /**
     * Whether this test run registered the wrapper itself (and thus owns unregistering it).
     */
    private bool $registeredHere = false;

    protected function setUp(): void
    {
        // Always start from a clean buffer store.
        Buffer::$buffers = [];

        // The awf:// wrapper auto-registers when Buffer.php is loaded. Make sure it is
        // available; register it ourselves only if it is not already there.
        if (!in_array('awf', stream_get_wrappers(), true))
        {
            if (!Buffer::canRegisterWrapper())
            {
                self::markTestSkipped('Cannot register stream wrappers on this host.');
            }

            stream_wrapper_register('awf', Buffer::class);
            $this->registeredHere = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->registeredHere)
        {
            stream_wrapper_unregister('awf');
            $this->registeredHere = false;
        }

        Buffer::$buffers = [];
    }

    // -------------------------------------------------------------------------
    // Basic write / read round-trip
    // -------------------------------------------------------------------------

    public function testWriteReadRoundTrip(): void
    {
        $fp = fopen('awf://test', 'w');
        self::assertIsResource($fp);

        $data    = 'Hello, world!';
        $written = fwrite($fp, $data);
        self::assertSame(strlen($data), $written);
        fclose($fp);

        $fp   = fopen('awf://test', 'r');
        $read = stream_get_contents($fp);
        fclose($fp);

        self::assertSame($data, $read);
    }

    public function testWritePopulatesBufferStore(): void
    {
        $fp = fopen('awf://store', 'w');
        fwrite($fp, 'payload');
        fclose($fp);

        self::assertArrayHasKey('store', Buffer::$buffers);
        self::assertSame('payload', Buffer::$buffers['store']);
    }

    public function testReadRespectsByteCount(): void
    {
        $fp = fopen('awf://chunked', 'w');
        fwrite($fp, 'abcdef');
        fclose($fp);

        $fp = fopen('awf://chunked', 'r');
        self::assertSame('abc', fread($fp, 3));
        self::assertSame('def', fread($fp, 3));
        self::assertSame('', fread($fp, 3));
        fclose($fp);
    }

    public function testReadEmptyBufferReturnsEmptyString(): void
    {
        $fp   = fopen('awf://empty', 'r');
        $read = fread($fp, 16);
        fclose($fp);

        self::assertSame('', $read);
    }

    public function testWriteBinaryData(): void
    {
        $binary = random_bytes(64);

        $fp = fopen('awf://binary', 'w');
        fwrite($fp, $binary);
        fclose($fp);

        $fp   = fopen('awf://binary', 'r');
        $read = stream_get_contents($fp);
        fclose($fp);

        self::assertSame($binary, $read);
    }

    // -------------------------------------------------------------------------
    // ftell / feof
    // -------------------------------------------------------------------------

    public function testTellAdvancesWithWrites(): void
    {
        $fp = fopen('awf://tell', 'w');
        self::assertSame(0, ftell($fp));

        fwrite($fp, 'abc');
        self::assertSame(3, ftell($fp));

        fwrite($fp, 'de');
        self::assertSame(5, ftell($fp));
        fclose($fp);
    }

    public function testTellAdvancesWithReads(): void
    {
        $fp = fopen('awf://tellread', 'w');
        fwrite($fp, 'abcdef');
        fclose($fp);

        $fp = fopen('awf://tellread', 'r');
        self::assertSame(0, ftell($fp));
        fread($fp, 2);
        self::assertSame(2, ftell($fp));
        fclose($fp);
    }

    public function testEofIsFalseUntilAllDataConsumed(): void
    {
        $fp = fopen('awf://eof', 'w');
        fwrite($fp, 'abc');
        fclose($fp);

        $fp = fopen('awf://eof', 'r');
        self::assertFalse(feof($fp));

        // Read everything we know is there.
        self::assertSame('abc', fread($fp, 3));

        // PHP marks EOF after a read that returns less than requested; force it.
        self::assertSame('', fread($fp, 1));
        self::assertTrue(feof($fp));
        fclose($fp);
    }

    // -------------------------------------------------------------------------
    // fseek (SEEK_SET / SEEK_CUR / SEEK_END)
    // -------------------------------------------------------------------------

    public function testSeekSet(): void
    {
        $fp = fopen('awf://seekset', 'w');
        fwrite($fp, 'abcdef');
        fclose($fp);

        $fp = fopen('awf://seekset', 'r');
        self::assertSame(0, fseek($fp, 2, SEEK_SET));
        self::assertSame(2, ftell($fp));
        self::assertSame('cdef', stream_get_contents($fp));
        fclose($fp);
    }

    public function testSeekCur(): void
    {
        $fp = fopen('awf://seekcur', 'w');
        fwrite($fp, 'abcdef');
        fclose($fp);

        $fp = fopen('awf://seekcur', 'r');
        fread($fp, 2);                       // position = 2
        self::assertSame(0, fseek($fp, 2, SEEK_CUR)); // position = 4
        self::assertSame(4, ftell($fp));
        self::assertSame('ef', stream_get_contents($fp));
        fclose($fp);
    }

    public function testSeekEnd(): void
    {
        $fp = fopen('awf://seekend', 'w');
        fwrite($fp, 'abcdef');
        fclose($fp);

        $fp = fopen('awf://seekend', 'r');
        // SEEK_END with a negative offset positions before the end.
        self::assertSame(0, fseek($fp, -2, SEEK_END));
        self::assertSame(4, ftell($fp));
        self::assertSame('ef', stream_get_contents($fp));
        fclose($fp);
    }

    public function testSeekSetOutOfRangeFails(): void
    {
        $fp = fopen('awf://seekfail', 'w');
        fwrite($fp, 'abc');
        fclose($fp);

        $fp = fopen('awf://seekfail', 'r');
        // Offset >= length is rejected by stream_seek (returns false → fseek returns -1).
        self::assertSame(-1, @fseek($fp, 10, SEEK_SET));
        // Position should remain unchanged.
        self::assertSame(0, ftell($fp));
        fclose($fp);
    }

    public function testSeekEndPastBeginningFails(): void
    {
        $fp = fopen('awf://seekendfail', 'w');
        fwrite($fp, 'abc');
        fclose($fp);

        $fp = fopen('awf://seekendfail', 'r');
        self::assertSame(-1, @fseek($fp, -10, SEEK_END));
        fclose($fp);
    }

    public function testSeekThenReadWrite(): void
    {
        $fp = fopen('awf://seekwrite', 'w');
        fwrite($fp, 'abcdef');
        // Seek back and overwrite a middle portion.
        fseek($fp, 2, SEEK_SET);
        fwrite($fp, 'XY');
        fclose($fp);

        self::assertSame('abXYef', Buffer::$buffers['seekwrite']);
    }

    // -------------------------------------------------------------------------
    // stat
    // -------------------------------------------------------------------------

    public function testStatReportsBufferSize(): void
    {
        $fp = fopen('awf://stat', 'w');
        fwrite($fp, 'abcdef');

        $stat = fstat($fp);
        fclose($fp);

        self::assertSame(6, $stat['size']);
        self::assertSame(0644, $stat['mode']);
    }

    public function testStatEmptyBufferReportsZeroSize(): void
    {
        $fp   = fopen('awf://statempty', 'w');
        $stat = fstat($fp);
        fclose($fp);

        self::assertSame(0, $stat['size']);
    }

    // -------------------------------------------------------------------------
    // Independent buffers
    // -------------------------------------------------------------------------

    public function testDistinctBuffersAreIsolated(): void
    {
        $fp = fopen('awf://alpha', 'w');
        fwrite($fp, 'AAA');
        fclose($fp);

        $fp = fopen('awf://beta', 'w');
        fwrite($fp, 'BBB');
        fclose($fp);

        self::assertSame('AAA', Buffer::$buffers['alpha']);
        self::assertSame('BBB', Buffer::$buffers['beta']);
    }

    public function testReopeningPreservesData(): void
    {
        $fp = fopen('awf://persist', 'w');
        fwrite($fp, 'kept');
        fclose($fp);

        // Opening for reading must not clear the existing buffer.
        $fp   = fopen('awf://persist', 'r');
        $read = stream_get_contents($fp);
        fclose($fp);

        self::assertSame('kept', $read);
    }

    public function testWriteAtPositionOverwritesInPlace(): void
    {
        $fp = fopen('awf://overwrite', 'w');
        fwrite($fp, '12345');
        fclose($fp);

        // Open append-like: seek to start and overwrite shorter content.
        $fp = fopen('awf://overwrite', 'r+');
        fseek($fp, 0, SEEK_SET);
        fwrite($fp, 'AB');
        fclose($fp);

        self::assertSame('AB345', Buffer::$buffers['overwrite']);
    }

    // -------------------------------------------------------------------------
    // unlink
    // -------------------------------------------------------------------------

    public function testUnlinkRemovesBuffer(): void
    {
        $fp = fopen('awf://removeme', 'w');
        fwrite($fp, 'gone');
        fclose($fp);

        self::assertArrayHasKey('removeme', Buffer::$buffers);

        self::assertTrue(unlink('awf://removeme'));
        self::assertArrayNotHasKey('removeme', Buffer::$buffers);
    }

    // -------------------------------------------------------------------------
    // canRegisterWrapper
    // -------------------------------------------------------------------------

    public function testCanRegisterWrapperReturnsBool(): void
    {
        self::assertIsBool(Buffer::canRegisterWrapper());
    }

    // -------------------------------------------------------------------------
    // Multi-line / larger payloads via data provider
    // -------------------------------------------------------------------------

    public static function payloadProvider(): array
    {
        return [
            'empty string'  => [''],
            'single char'   => ['a'],
            'multibyte'     => ['héllo wörld ☃'],
            'newlines'      => ["line1\nline2\nline3"],
            'large'         => [str_repeat('x', 100000)],
        ];
    }

    #[DataProvider('payloadProvider')]
    public function testRoundTripPayloads(string $payload): void
    {
        $fp = fopen('awf://payload', 'w');
        fwrite($fp, $payload);
        fclose($fp);

        $fp   = fopen('awf://payload', 'r');
        $read = stream_get_contents($fp);
        fclose($fp);

        self::assertSame($payload, $read);
    }
}
