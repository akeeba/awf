<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\HashHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HashHelperTest extends TestCase
{
	private string $tempFile = '';

	protected function tearDown(): void
	{
		if ($this->tempFile !== '' && is_file($this->tempFile))
		{
			@unlink($this->tempFile);
		}

		$this->tempFile = '';
	}

	// -------------------------------------------------------------------------
	// md5 (string)
	// -------------------------------------------------------------------------

	public static function md5Provider(): array
	{
		return [
			'empty string'  => ['', 'd41d8cd98f00b204e9800998ecf8427e'],
			'abc'           => ['abc', '900150983cd24fb0d6963f7d28e17f72'],
			'message digest' => ['message digest', 'f96b697d7cb7938d525a2f31aaf161d0'],
			'abcdefghijklmnopqrstuvwxyz' => ['abcdefghijklmnopqrstuvwxyz', 'c3fcd3d76192e4007dfb496cca67e13b'],
		];
	}

	#[DataProvider('md5Provider')]
	public function testMd5(string $input, string $expected): void
	{
		self::assertSame($expected, HashHelper::md5($input));
	}

	public function testMd5MatchesNativeFunction(): void
	{
		self::assertSame(md5('The quick brown fox'), HashHelper::md5('The quick brown fox'));
	}

	public function testMd5Binary(): void
	{
		$binary = HashHelper::md5('abc', true);

		self::assertSame(16, strlen($binary));
		self::assertSame('900150983cd24fb0d6963f7d28e17f72', bin2hex($binary));
	}

	// -------------------------------------------------------------------------
	// sha1 (string)
	// -------------------------------------------------------------------------

	public static function sha1Provider(): array
	{
		return [
			'empty string' => ['', 'da39a3ee5e6b4b0d3255bfef95601890afd80709'],
			'abc'          => ['abc', 'a9993e364706816aba3e25717850c26c9cd0d89d'],
		];
	}

	#[DataProvider('sha1Provider')]
	public function testSha1(string $input, string $expected): void
	{
		self::assertSame($expected, HashHelper::sha1($input));
	}

	public function testSha1MatchesNativeFunction(): void
	{
		self::assertSame(sha1('The quick brown fox'), HashHelper::sha1('The quick brown fox'));
	}

	public function testSha1Binary(): void
	{
		$binary = HashHelper::sha1('abc', true);

		self::assertSame(20, strlen($binary));
		self::assertSame('a9993e364706816aba3e25717850c26c9cd0d89d', bin2hex($binary));
	}

	// -------------------------------------------------------------------------
	// md5_file / sha1_file
	// -------------------------------------------------------------------------

	private function makeTempFile(string $contents): string
	{
		$this->tempFile = tempnam(sys_get_temp_dir(), 'awfhash');
		file_put_contents($this->tempFile, $contents);

		return $this->tempFile;
	}

	public function testMd5File(): void
	{
		$file = $this->makeTempFile('abc');

		self::assertSame('900150983cd24fb0d6963f7d28e17f72', HashHelper::md5_file($file));
		self::assertSame(md5_file($file), HashHelper::md5_file($file));
	}

	public function testMd5FileBinary(): void
	{
		$file   = $this->makeTempFile('abc');
		$binary = HashHelper::md5_file($file, true);

		self::assertSame(16, strlen($binary));
		self::assertSame('900150983cd24fb0d6963f7d28e17f72', bin2hex($binary));
	}

	public function testSha1File(): void
	{
		$file = $this->makeTempFile('abc');

		self::assertSame('a9993e364706816aba3e25717850c26c9cd0d89d', HashHelper::sha1_file($file));
		self::assertSame(sha1_file($file), HashHelper::sha1_file($file));
	}

	public function testSha1FileBinary(): void
	{
		$file   = $this->makeTempFile('abc');
		$binary = HashHelper::sha1_file($file, true);

		self::assertSame(20, strlen($binary));
		self::assertSame('a9993e364706816aba3e25717850c26c9cd0d89d', bin2hex($binary));
	}

	// -------------------------------------------------------------------------
	// Determinism & error conditions
	// -------------------------------------------------------------------------

	public function testHashingIsDeterministic(): void
	{
		self::assertSame(HashHelper::md5('repeatable'), HashHelper::md5('repeatable'));
		self::assertSame(HashHelper::sha1('repeatable'), HashHelper::sha1('repeatable'));
	}

	public function testMd5FileMissingFileReturnsFalse(): void
	{
		$missing = sys_get_temp_dir() . '/awf-this-file-does-not-exist-' . uniqid('', true);

		self::assertFalse(@HashHelper::md5_file($missing));
	}

	public function testSha1FileMissingFileReturnsFalse(): void
	{
		$missing = sys_get_temp_dir() . '/awf-this-file-does-not-exist-' . uniqid('', true);

		self::assertFalse(@HashHelper::sha1_file($missing));
	}
}
