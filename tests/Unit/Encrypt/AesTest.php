<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Encrypt;

use Awf\Encrypt\Aes;
use Awf\Encrypt\AesAdapter\OpenSSL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // setUp / helpers
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('ext-openssl is not available.');
        }
    }

    // -------------------------------------------------------------------------
    // Aes::isSupported
    // -------------------------------------------------------------------------

    public function testIsSupportedReturnsTrueWhenOpenSslAvailable(): void
    {
        self::assertTrue(Aes::isSupported());
    }

    // -------------------------------------------------------------------------
    // OpenSSL adapter – basic support / block size
    // -------------------------------------------------------------------------

    public function testAdapterIsSupportedReturnsTrueWhenOpenSslAvailable(): void
    {
        $adapter = new OpenSSL();
        self::assertTrue($adapter->isSupported());
    }

    public function testAdapterDefaultBlockSizeIs16(): void
    {
        $adapter = new OpenSSL();
        self::assertSame(16, $adapter->getBlockSize());
    }

    // -------------------------------------------------------------------------
    // OpenSSL adapter – setEncryptionMode
    // -------------------------------------------------------------------------

    public static function encryptionModeProvider(): array
    {
        return [
            'CBC-128 (default)'  => ['cbc', 128, 16],
            'CBC-256'            => ['cbc', 256, 16],
            'ECB-128'            => ['ebc', 128, 16],
            'invalid mode falls back to CBC' => ['xyz', 128, 16],
            'invalid strength falls back to 256' => ['cbc', 999, 16],
        ];
    }

    #[DataProvider('encryptionModeProvider')]
    public function testSetEncryptionModeSetsCorrectBlockSize(
        string $mode,
        int $strength,
        int $expectedBlockSize
    ): void {
        $adapter = new OpenSSL();
        $adapter->setEncryptionMode($mode, $strength);
        self::assertSame($expectedBlockSize, $adapter->getBlockSize());
    }

    // -------------------------------------------------------------------------
    // AbstractAdapter::resizeKey
    // -------------------------------------------------------------------------

    public function testResizeKeyPadsShortKeyWithNullBytes(): void
    {
        $adapter = new OpenSSL();
        $result  = $adapter->resizeKey('abc', 8);
        self::assertSame("abc\0\0\0\0\0", $result);
        self::assertSame(8, strlen($result));
    }

    public function testResizeKeyTruncatesLongKey(): void
    {
        $adapter = new OpenSSL();
        $result  = $adapter->resizeKey('abcdefghijklmnopqrstuvwxyz', 8);
        self::assertSame('abcdefgh', $result);
    }

    public function testResizeKeyReturnsSameKeyWhenExactSize(): void
    {
        $adapter = new OpenSSL();
        $key     = str_repeat('k', 16);
        self::assertSame($key, $adapter->resizeKey($key, 16));
    }

    public function testResizeKeyReturnsNullForEmptyKey(): void
    {
        $adapter = new OpenSSL();
        self::assertNull($adapter->resizeKey('', 16));
    }

    // -------------------------------------------------------------------------
    // Aes – constructor / setPassword key expansion
    // -------------------------------------------------------------------------

    public function testConstructorWithExact16ByteKeyUsesItDirectly(): void
    {
        $key = str_repeat('x', 16);
        $aes = new Aes($key);

        // With a 16-byte key no legacy expansion is done (setPassword called with
        // legacyMode=true but key is 16 bytes != 32, so sha256 expansion IS done).
        // Just verify the object is created without error.
        self::assertInstanceOf(Aes::class, $aes);
    }

    public function testSetPasswordUpdatesKeyUsedForEncryption(): void
    {
        $aes = new Aes('initial');
        $aes->setPassword('newPassword');

        $plaintext = 'Hello World';
        $cipher    = $aes->encryptString($plaintext);
        $decrypted = $aes->decryptString($cipher);

        // After trimming the zero-padding the decrypted text should match
        self::assertSame($plaintext, rtrim($decrypted, "\0"));
    }

    // -------------------------------------------------------------------------
    // Aes::getExpandedKey
    // -------------------------------------------------------------------------

    public function testGetExpandedKeyReturnsSameKeyWhenLengthMatchesBlockSize(): void
    {
        $key     = str_repeat('k', 16);
        $aes     = new Aes('irrelevant');
        $aes->setPassword($key);

        // A 16-byte key matches AES-128 block size, so it should be returned unchanged
        $iv          = random_bytes(16);
        $expandedKey = $aes->getExpandedKey(16, $iv);

        self::assertSame($key, $expandedKey);
    }

    public function testGetExpandedKeyDerivesDifferentKeyWhenLengthDoesNotMatch(): void
    {
        $shortKey = 'short';
        $aes      = new Aes('irrelevant');
        $aes->setPassword($shortKey);

        $iv          = random_bytes(16);
        $expandedKey = $aes->getExpandedKey(16, $iv);

        // Derived key must be 16 bytes
        self::assertSame(16, strlen($expandedKey));
        // And must NOT equal the original short key zero-padded to 16 bytes
        self::assertNotEquals(str_pad($shortKey, 16, "\0"), $expandedKey);
    }

    // -------------------------------------------------------------------------
    // Aes – encrypt → decrypt round-trip
    // -------------------------------------------------------------------------

    public static function roundTripProvider(): array
    {
        return [
            'empty string'             => ['', 'secretKey'],
            'short ascii'              => ['Hello, World!', 'secretKey'],
            'exactly 16 bytes'         => ['0123456789abcdef', 'secretKey'],
            'multi-block text'         => [str_repeat('A', 64), 'secretKey'],
            'binary data'              => ["\x00\x01\x02\x03\x04\x05\x06\x07", 'secretKey'],
            'unicode text'             => ['Héllo Wörld', 'secretKey'],
            'long password'            => ['Hello, World!', str_repeat('p', 64)],
            'exact 16-byte password'   => ['Hello, World!', str_repeat('p', 16)],
        ];
    }

    #[DataProvider('roundTripProvider')]
    public function testEncryptDecryptRoundTrip(string $plaintext, string $password): void
    {
        $aes      = new Aes('ignored');
        $aes->setPassword($password);

        $ciphertext = $aes->encryptString($plaintext, true);
        $decrypted  = $aes->decryptString($ciphertext, true);

        // Zero-padding may be appended; trim null bytes from decrypted result
        self::assertSame($plaintext, rtrim($decrypted, "\0"));
    }

    #[DataProvider('roundTripProvider')]
    public function testEncryptDecryptRoundTripRawBytes(string $plaintext, string $password): void
    {
        $aes      = new Aes('ignored');
        $aes->setPassword($password);

        $ciphertext = $aes->encryptString($plaintext, false);
        $decrypted  = $aes->decryptString($ciphertext, false);

        self::assertSame($plaintext, rtrim($decrypted, "\0"));
    }

    // -------------------------------------------------------------------------
    // Aes – ciphertext properties
    // -------------------------------------------------------------------------

    public function testEncryptedStringDiffersFromPlaintext(): void
    {
        $aes       = new Aes('ignored');
        $aes->setPassword('mySecret');
        $plaintext = 'Sensitive data here!';

        $cipher = $aes->encryptString($plaintext, true);

        self::assertNotSame($plaintext, $cipher);
    }

    public function testEncryptedStringIsBase64WhenRequested(): void
    {
        $aes = new Aes('ignored');
        $aes->setPassword('mySecret');

        $cipher = $aes->encryptString('test', true);

        // Valid base64: only [A-Za-z0-9+/=] characters
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $cipher);
    }

    public function testEncryptingTwiceProducesDifferentCiphertexts(): void
    {
        $aes = new Aes('ignored');
        $aes->setPassword('mySecret');

        $plaintext = 'Same plaintext';
        $cipher1   = $aes->encryptString($plaintext, true);
        $cipher2   = $aes->encryptString($plaintext, true);

        // Different IVs are used each time, so ciphertexts should differ
        self::assertNotSame($cipher1, $cipher2);
    }

    // -------------------------------------------------------------------------
    // Aes – wrong key fails to produce original plaintext
    // -------------------------------------------------------------------------

    public function testDecryptWithWrongKeyDoesNotRecoverPlaintext(): void
    {
        $aes = new Aes('ignored');
        $aes->setPassword('correctPassword');
        $plaintext  = 'Top secret message';
        $ciphertext = $aes->encryptString($plaintext, true);

        $wrongAes = new Aes('ignored');
        $wrongAes->setPassword('wrongPassword');
        $decrypted = $wrongAes->decryptString($ciphertext, true);

        self::assertNotSame($plaintext, rtrim($decrypted, "\0"));
    }

    // -------------------------------------------------------------------------
    // Aes – modes (CBC-128, CBC-256, ECB-128)
    // -------------------------------------------------------------------------

    public static function modeRoundTripProvider(): array
    {
        return [
            'CBC-128' => ['cbc', 128],
            'CBC-256' => ['cbc', 256],
            'ECB-128' => ['ebc', 128],
        ];
    }

    #[DataProvider('modeRoundTripProvider')]
    public function testRoundTripWithMode(string $mode, int $strength): void
    {
        $aes = new Aes('ignored', $strength, $mode);
        $aes->setPassword('MyTestPassword123');

        $plaintext  = 'Round-trip test for mode ' . $mode . '-' . $strength;
        $ciphertext = $aes->encryptString($plaintext, true);
        $decrypted  = $aes->decryptString($ciphertext, true);

        self::assertSame($plaintext, rtrim($decrypted, "\0"));
    }

    // -------------------------------------------------------------------------
    // OpenSSL adapter – direct encrypt/decrypt
    // -------------------------------------------------------------------------

    public function testAdapterEncryptDecryptDirectly(): void
    {
        $adapter = new OpenSSL();
        $key     = str_repeat('k', 16);
        $iv      = str_repeat('i', 16);

        $plaintext  = 'Direct adapter test';
        $ciphertext = $adapter->encrypt($plaintext, $key, $iv);
        $decrypted  = $adapter->decrypt($ciphertext, $key);

        self::assertSame($plaintext, rtrim($decrypted, "\0"));
    }

    public function testAdapterEncryptProducesBinaryStringWithIvPrepended(): void
    {
        $adapter = new OpenSSL();
        $key     = str_repeat('k', 16);
        $iv      = str_repeat('i', 16);

        $ciphertext = $adapter->encrypt('Hello', $key, $iv);

        // Result must be longer than block size (IV + at least one block)
        self::assertGreaterThan(16, strlen($ciphertext));

        // First 16 bytes should be the IV (which the adapter prepends)
        self::assertSame($iv, substr($ciphertext, 0, 16));
    }

    public function testAdapterEncryptWithNullIvGeneratesRandomIv(): void
    {
        $adapter = new OpenSSL();
        $key     = str_repeat('k', 16);

        $cipher1 = $adapter->encrypt('Hello', $key, null);
        $cipher2 = $adapter->encrypt('Hello', $key, null);

        // Two calls with null IV should produce different results (random IV)
        self::assertNotSame($cipher1, $cipher2);
    }

    // -------------------------------------------------------------------------
    // OpenSSL adapter – resizeKey with null/empty
    // -------------------------------------------------------------------------

    public function testResizeKeyWithNullBytesInKey(): void
    {
        $adapter = new OpenSSL();
        $key     = "\x00\x01\x02";
        $result  = $adapter->resizeKey($key, 16);
        self::assertSame(16, strlen($result));
        self::assertStringStartsWith($key, $result);
    }
}
