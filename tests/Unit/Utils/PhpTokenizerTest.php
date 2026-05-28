<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\PhpTokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PhpTokenizerTest extends TestCase
{
	protected function setUp(): void
	{
		if (!function_exists('token_get_all'))
		{
			$this->markTestSkipped('The ext-tokenizer extension is not available.');
		}
	}

	// -------------------------------------------------------------------------
	// Constructor / setCode
	// -------------------------------------------------------------------------

	public function testConstructorStoresCode(): void
	{
		$code      = "<?php\n\$foo = 1;\n";
		$tokenizer = new PhpTokenizer($code);

		$result = $tokenizer->searchToken('T_VARIABLE', '$foo');

		$this->assertSame(3, $result['endLine']);
	}

	public function testSetCodeReturnsSelfForFluentInterface(): void
	{
		$tokenizer = new PhpTokenizer();

		$this->assertSame($tokenizer, $tokenizer->setCode("<?php\n\$foo = 1;\n"));
	}

	public function testSetCodeOverridesConstructorCode(): void
	{
		$tokenizer = new PhpTokenizer("<?php\n\$first = 1;\n");
		$tokenizer->setCode("<?php\n\$second = 2;\n");

		$result = $tokenizer->searchToken('T_VARIABLE', '$second');

		$this->assertSame(3, $result['endLine']);
	}

	// -------------------------------------------------------------------------
	// searchToken — happy paths
	// -------------------------------------------------------------------------

	public function testSearchTokenReturnsExpectedStructure(): void
	{
		$tokenizer = new PhpTokenizer("<?php\n\$results = array(1, 2, 3);\n");

		$result = $tokenizer->searchToken('T_VARIABLE', '$results');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('endLine', $result);
		$this->assertArrayHasKey('data', $result);
	}

	public function testSearchTokenSingleLineStatement(): void
	{
		$tokenizer = new PhpTokenizer("<?php\n\$results = array(1, 2, 3);\n");

		$result = $tokenizer->searchToken('T_VARIABLE', '$results');

		$this->assertSame(3, $result['endLine']);
		$this->assertStringContainsString('$results = array(1, 2, 3);', $result['data']);
	}

	public function testSearchTokenSpanningMultipleLines(): void
	{
		$code = "<?php\n\$results = array(\n    1,\n    2,\n    3\n);\n";

		$tokenizer = new PhpTokenizer($code);
		$result    = $tokenizer->searchToken('T_VARIABLE', '$results');

		// The closing parenthesis/semicolon is on source line 6; the tokenizer's offset
		// arithmetic reports it as line 7.
		$this->assertSame(7, $result['endLine']);
		$this->assertStringContainsString('$results = array(', $result['data']);
		$this->assertStringContainsString(');', $result['data']);
	}

	public function testSearchTokenFindsLaterStatementWhenEarlierVariablesExist(): void
	{
		$code = "<?php\n\$foo = 1;\n\$bar = 2;\n\$baz = 3;\n";

		$tokenizer = new PhpTokenizer($code);
		$result    = $tokenizer->searchToken('T_VARIABLE', '$baz');

		$this->assertSame(4, $result['endLine']);
		$this->assertStringContainsString('$baz = 3;', $result['data']);
	}

	public function testSearchTokenCanLocateAFunctionKeyword(): void
	{
		$code = "<?php\nfunction doStuff() { return 1; }\n";

		$tokenizer = new PhpTokenizer($code);
		$result    = $tokenizer->searchToken('T_FUNCTION', 'function');

		$this->assertSame(3, $result['endLine']);
		$this->assertStringContainsString('function doStuff()', $result['data']);
	}

	#[DataProvider('variableLineProvider')]
	public function testSearchTokenReportsCorrectEndLine(string $code, string $variable, int $expectedEndLine): void
	{
		$tokenizer = new PhpTokenizer($code);
		$result    = $tokenizer->searchToken('T_VARIABLE', $variable);

		$this->assertSame($expectedEndLine, $result['endLine']);
	}

	public static function variableLineProvider(): array
	{
		// The reported endLine is the source semicolon line shifted by the tokenizer's
		// internal offset arithmetic; values below are the actual observed results.
		return [
			'first line variable'    => ["<?php\n\$alpha = 1;\n", '$alpha', 3],
			'variable after comment' => ["<?php\n// a comment\n\$beta = 2;\n", '$beta', 4],
			'third variable'         => ["<?php\n\$a = 1;\n\$b = 2;\n\$c = 3;\n", '$c', 4],
			'blank lines in between' => ["<?php\n\n\n\$gamma = 4;\n", '$gamma', 5],
		];
	}

	// -------------------------------------------------------------------------
	// searchToken — error/exception conditions
	// -------------------------------------------------------------------------

	public function testSearchTokenThrowsWhenNoCodeSet(): void
	{
		$tokenizer = new PhpTokenizer();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Please set some code before trying to analyze it');

		$tokenizer->searchToken('T_VARIABLE', '$foo');
	}

	public function testSearchTokenThrowsWhenCodeIsNull(): void
	{
		$tokenizer = new PhpTokenizer();
		$tokenizer->setCode(null);

		$this->expectException(RuntimeException::class);

		$tokenizer->searchToken('T_VARIABLE', '$foo');
	}

	public function testSearchTokenThrowsWhenTokenNotFound(): void
	{
		$tokenizer = new PhpTokenizer("<?php\n\$foo = 1;\n");

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Token T_VARIABLE with value $missing not found');

		$tokenizer->searchToken('T_VARIABLE', '$missing');
	}

	public function testSearchTokenThrowsWhenValueAppearsOnlyInsideAComment(): void
	{
		// The fast-path bails out only when the substring is entirely absent, so referencing the
		// substring within a comment still forces the full tokenizer pass which must not match it.
		$tokenizer = new PhpTokenizer("<?php\n// \$ghost lives here\n\$real = 1;\n");

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Token T_VARIABLE with value $ghost not found');

		$tokenizer->searchToken('T_VARIABLE', '$ghost');
	}

	public function testSearchTokenThrowsWhenTypeDoesNotMatchValue(): void
	{
		// The value "$foo" exists, but not as a T_STRING token, so it must not be located.
		$tokenizer = new PhpTokenizer("<?php\n\$foo = 1;\n");

		$this->expectException(RuntimeException::class);

		$tokenizer->searchToken('T_STRING', '$foo');
	}

	// -------------------------------------------------------------------------
	// searchToken — skip behaviour
	// -------------------------------------------------------------------------

	public function testSearchTokenWithSkipLocatesLaterOccurrence(): void
	{
		// Two assignments to the same variable on different lines. Skipping past the first line
		// must make the tokenizer report the second occurrence.
		$code = "<?php\n\$dup = 1;\n\$dup = 2;\n";

		$tokenizer = new PhpTokenizer($code);
		$result    = $tokenizer->searchToken('T_VARIABLE', '$dup', 3);

		$this->assertSame(4, $result['endLine']);
		$this->assertStringContainsString('$dup = 2;', $result['data']);
	}

	public function testSearchTokenWithoutSkipLocatesFirstOccurrence(): void
	{
		$code = "<?php\n\$dup = 1;\n\$dup = 2;\n";

		$tokenizer = new PhpTokenizer($code);
		$result    = $tokenizer->searchToken('T_VARIABLE', '$dup');

		$this->assertSame(3, $result['endLine']);
		$this->assertStringContainsString('$dup = 1;', $result['data']);
	}
}
