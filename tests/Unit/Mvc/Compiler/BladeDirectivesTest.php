<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\Compiler;

use Awf\Utils\HashHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Supplemental directive coverage for Awf\Mvc\Compiler\Blade.
 *
 * This file adds edge-case, multi-arg, error-condition, and regex-fallback
 * coverage for directives that are only smoke-tested in BladeCompilerTest.
 * The TestBlade helper class is defined in BladeCompilerTest.php and is
 * reused here (same namespace, same file loaded by PHPUnit autoloading).
 */
class BladeDirectivesTest extends TestCase
{
	private TestBlade $blade;

	protected function setUp(): void
	{
		$this->blade = new TestBlade();
	}

	// -------------------------------------------------------------------------
	// Helper — compile with both tokenizer and regex-fallback paths
	// -------------------------------------------------------------------------

	private function assertBothPaths(string $expected, string $template): void
	{
		$this->blade->forceUsingTokenizer(true);
		self::assertSame($expected, $this->blade->compileString($template), 'tokenizer path');

		$this->blade->resetCompilerState();

		$this->blade->forceUsingTokenizer(false);
		self::assertSame($expected, $this->blade->compileString($template), 'regex-fallback path');
	}

	// =========================================================================
	// @each — edge cases
	// =========================================================================

	public function testEachWithFourArgs(): void
	{
		// Four-argument form: view, data, iterator var, empty view
		$this->assertBothPaths(
			"<?php echo \$this->renderEach('view.row', \$rows, 'row', 'view.empty'); ?>",
			"@each('view.row', \$rows, 'row', 'view.empty')"
		);
	}

	// =========================================================================
	// @push / @stack — edge cases
	// =========================================================================

	public function testPushAndEndpushAroundContent(): void
	{
		$template = "@push('head')\n<link rel=\"stylesheet\" href=\"a.css\">\n@endpush";
		$expected = "<?php \$this->startSection('head'); ?>\n<link rel=\"stylesheet\" href=\"a.css\">\n<?php \$this->appendSection(); ?>";
		$this->assertBothPaths($expected, $template);
	}

	public function testStackEmitsYieldContent(): void
	{
		// @stack is an alias that emits yieldContent — same as @yield but
		// semantically for push stacks
		$this->blade->forceUsingTokenizer(true);
		$stackResult = $this->blade->compileString("@stack('footer')");
		$this->blade->resetCompilerState();
		$yieldResult = $this->blade->compileString("@yield('footer')");

		self::assertSame($yieldResult, $stackResult, '@stack and @yield must compile identically');
	}

	// =========================================================================
	// @inlineCss / @inlineJs — edge cases
	// =========================================================================

	public function testInlineCssWithMultilineStyleBlock(): void
	{
		$css      = ".a{\n  color:red;\n}";
		$template = "@inlineCss('$css')";
		$compiled = $this->blade->compileString($template);

		self::assertStringContainsString('addStyleDeclaration', $compiled);
	}

	public function testInlineJsWithMultilineScriptBlock(): void
	{
		$js       = "function f(){\n  return 1;\n}";
		$template = "@inlineJs('$js')";
		$compiled = $this->blade->compileString($template);

		self::assertStringContainsString('addScriptDeclaration', $compiled);
	}

	public function testInlineCssRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString("@inlineCss('body{margin:0}')");

		self::assertStringContainsString(
			"\$this->container->application->getDocument()->addStyleDeclaration",
			$result
		);
	}

	public function testInlineJsRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString("@inlineJs('alert(1)')");

		self::assertStringContainsString(
			"\$this->container->application->getDocument()->addScriptDeclaration",
			$result
		);
	}

	// =========================================================================
	// @jhtml / @html — edge cases
	// =========================================================================

	public function testJhtmlWithMultipleArguments(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->getContainer()->html->get('grid.id', \$row, 'id', \$checked); ?>",
			"@jhtml('grid.id', \$row, 'id', \$checked)"
		);
	}

	public function testHtmlIsExactAliasForJhtmlOnRegexPath(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$jhtml = $this->blade->compileString("@jhtml('select.genericlist', \$options)");
		$this->blade->resetCompilerState();
		$this->blade->forceUsingTokenizer(false);
		$html = $this->blade->compileString("@html('select.genericlist', \$options)");

		self::assertSame($jhtml, $html, '@html and @jhtml must compile identically on regex path');
	}

	// =========================================================================
	// @route — edge cases
	// =========================================================================

	public function testRouteWithoutArrayArgument(): void
	{
		// @route with only a URL string, no explicit array
		$this->blade->forceUsingTokenizer(true);
		$result = $this->blade->compileString("@route('index.php?view=items')");

		self::assertStringContainsString("\$this->container->router->route", $result);
	}

	public function testRouteRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString("@route('index.php?view=foo', [])");

		self::assertSame(
			"<?php echo \$this->container->router->route('index.php?view=foo', []); ?>",
			$result
		);
	}

	// =========================================================================
	// @media — edge cases
	// =========================================================================

	public function testMediaWithNestedPath(): void
	{
		$this->assertBothPaths(
			"<?php echo \\Awf\\Utils\\Template::parsePath('images/icons/arrow.svg'); ?>",
			"@media('images/icons/arrow.svg')"
		);
	}

	public function testMediaRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString("@media('images/logo.png')");

		self::assertSame(
			"<?php echo \\Awf\\Utils\\Template::parsePath('images/logo.png'); ?>",
			$result
		);
	}

	// =========================================================================
	// @token — edge cases
	// =========================================================================

	public function testTokenRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString('@token');

		self::assertSame(
			'<?php echo $this->container->session->getCsrfToken()->getValue(); ?>',
			$result
		);
	}

	public function testTokenInsideLargerTemplate(): void
	{
		$template = '<input type="hidden" name="token" value="@token">';
		$compiled = $this->blade->compileString($template);

		self::assertStringContainsString('getCsrfToken()->getValue()', $compiled);
		self::assertStringNotContainsString('@token', $compiled);
	}

	// =========================================================================
	// @repeatable — edge cases
	// =========================================================================

	public function testRepeatableWithNoArguments(): void
	{
		$key    = HashHelper::md5("'myblock'");
		$result = $this->blade->compileString("@repeatable('myBlock')");

		// No argument list — the closure signature has no parameters
		self::assertSame("<?php @\$this->repeatableMap['$key'] = function() { ?>", $result);
	}

	public function testRepeatableKeyIsLowercased(): void
	{
		// "MyBlock" and "myblock" must produce the same key
		$keyLower = HashHelper::md5("'myblock'");
		$keyMixed = HashHelper::md5("'myblock'"); // same — lowercase

		$lowerResult = $this->blade->compileString("@repeatable('myblock')");
		$this->blade->resetCompilerState();
		$mixedResult = $this->blade->compileString("@repeatable('myBlock')");

		// Both must reference the same key because the compiler lowercases the name
		self::assertSame($lowerResult, $mixedResult);
	}

	public function testRepeatableWithThreeArguments(): void
	{
		$key    = HashHelper::md5("'card'");
		$result = $this->blade->compileString("@repeatable('card', \$title, \$body, \$footer)");

		self::assertSame(
			"<?php @\$this->repeatableMap['$key'] = function( \$title, \$body, \$footer) { ?>",
			$result
		);
	}

	public function testEndrepeatableOnRegexPath(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString('@endrepeatable');

		self::assertSame('<?php }; ?>', $result);
	}

	// =========================================================================
	// @yieldRepeatable — edge cases
	// =========================================================================

	public function testYieldRepeatableWithArguments(): void
	{
		$key    = HashHelper::md5("'card'");
		$result = $this->blade->compileString("@yieldRepeatable('card', \$t, \$b)");

		$expected = "<?php try { \$this->repeatableMap['$key']( \$t, \$b); } catch (\Throwable \$e)"
		            . " { throw new \RuntimeException(sprintf('Error calling repeatable \"%s\"', 'card'), 500, \$e); } ?>";

		self::assertSame($expected, $result);
	}

	public function testYieldRepeatableOnRegexPath(): void
	{
		$key = HashHelper::md5("'widget'");
		$this->blade->forceUsingTokenizer(false);
		$result   = $this->blade->compileString("@yieldRepeatable('widget')");
		$expected = "<?php try { \$this->repeatableMap['$key'](); } catch (\Throwable \$e)"
		            . " { throw new \RuntimeException(sprintf('Error calling repeatable \"%s\"', 'widget'), 500, \$e); } ?>";

		self::assertSame($expected, $result);
	}

	// =========================================================================
	// @repeatableOverride — edge cases
	// =========================================================================

	public function testRepeatableOverrideAlsoLowercasesKey(): void
	{
		$expectedKey = HashHelper::md5("'widget'");

		$overrideResult = $this->blade->compileString("@repeatableOverride('Widget')");

		self::assertStringContainsString("repeatableMap['$expectedKey']", $overrideResult);
	}

	public function testRepeatableOverrideOnRegexPath(): void
	{
		$this->blade->forceUsingTokenizer(false);
		$key    = HashHelper::md5("'panel'");
		$result = $this->blade->compileString("@repeatableOverride('panel')");

		self::assertStringContainsString("repeatableMap['$key']", $result);
	}

	public function testRepeatableOverrideKeepsBlockWhenCalledFirst(): void
	{
		// The override itself must NOT use SKIP_ prefix
		$key    = HashHelper::md5("'nav'");
		$result = $this->blade->compileString("@repeatableOverride('nav')");

		self::assertStringNotContainsString('SKIP_', $result);
		self::assertStringContainsString("repeatableMap['$key']", $result);
	}

	public function testRepeatableAfterOverrideGetsSuppressedOnRegexPath(): void
	{
		$key = HashHelper::md5("'nav'");

		// Register the override on the regex path
		$this->blade->forceUsingTokenizer(false);
		$this->blade->compileString("@repeatableOverride('nav')");
		$this->blade->resetFooterAndCounter();

		// Subsequent @repeatable should get SKIP_ prefix
		$second = $this->blade->compileString("@repeatable('nav')");
		self::assertStringContainsString("repeatableMap['SKIP_$key']", $second);
	}

	public function testRepeatableOverrideThenADifferentBlockIsNotSuppressed(): void
	{
		$keyNav  = HashHelper::md5("'nav'");
		$keyFoot = HashHelper::md5("'footer'");

		// Override 'nav'
		$this->blade->compileString("@repeatableOverride('nav')");
		$this->blade->resetFooterAndCounter();

		// A different block ('footer') is NOT suppressed
		$result = $this->blade->compileString("@repeatable('footer')");
		self::assertStringContainsString("repeatableMap['$keyFoot']", $result);
		self::assertStringNotContainsString("SKIP_$keyNav", $result);
	}

	// =========================================================================
	// Custom extend() directives — edge cases
	// =========================================================================

	public function testExtendReceivesBothValueAndCompilerArguments(): void
	{
		$receivedCompiler = null;

		$this->blade->extend(function (string $value, $compiler) use (&$receivedCompiler): string {
			$receivedCompiler = $compiler;

			return $value;
		});

		$this->blade->compileString('hello');

		// The second argument passed to the extension must be the Blade instance
		self::assertInstanceOf(TestBlade::class, $receivedCompiler);
	}

	public function testExtendOnRegexFallbackPath(): void
	{
		$this->blade->extend(fn(string $v): string => str_replace('@ping', '<?php echo "pong"; ?>', $v));

		$this->blade->forceUsingTokenizer(false);
		$result = $this->blade->compileString('@ping');

		self::assertSame('<?php echo "pong"; ?>', $result);
	}

	public function testExtendOnTokenizerPath(): void
	{
		$this->blade->extend(fn(string $v): string => str_replace('@ping', '<?php echo "pong"; ?>', $v));

		$this->blade->forceUsingTokenizer(true);
		$result = $this->blade->compileString('@ping');

		self::assertSame('<?php echo "pong"; ?>', $result);
	}

	public function testExtensionsAreClearedOnReset(): void
	{
		// Extensions survive resetCompilerState (state != extensions list),
		// confirming resetCompilerState only resets footer/counter/overrides.
		$this->blade->extend(fn(string $v): string => str_replace('X', 'Y', $v));
		$this->blade->resetCompilerState();

		// Extension must still fire after reset
		$result = $this->blade->compileString('X');
		self::assertSame('Y', $result);
	}

	// =========================================================================
	// Regex-fallback path — directives that only appear in regex tests
	// =========================================================================

	public static function regexFallbackDirectivesProvider(): array
	{
		return [
			'@lang'         => [
				"@lang('KEY')",
				"<?php echo \$this->getLanguage()->text('KEY'); ?>",
			],
			'@sprintf'      => [
				"@sprintf('KEY', \$n)",
				"<?php echo \$this->getLanguage()->sprintf('KEY', \$n); ?>",
			],
			'@plural'       => [
				"@plural('KEY', \$n)",
				"<?php echo \$this->getLanguage()->plural('KEY', \$n); ?>",
			],
			'@css'          => [
				"@css('app.css')",
				"<?php \\Awf\\Utils\\Template::addCss('app.css'); ?>",
			],
			'@js'           => [
				"@js('app.js')",
				"<?php \\Awf\\Utils\\Template::addJs('app.js'); ?>",
			],
			'@include'      => [
				"@include('partials/nav')",
				"<?php echo \$this->loadAnyTemplate('partials/nav'); ?>",
			],
			'@section'      => [
				"@section('sidebar')",
				"<?php \$this->startSection('sidebar'); ?>",
			],
			'@endsection'   => [
				'@endsection',
				'<?php $this->stopSection(); ?>',
			],
			'@yield'        => [
				"@yield('main')",
				"<?php echo \$this->yieldContent('main'); ?>",
			],
			'@each'         => [
				"@each('view.item', \$items, 'item')",
				"<?php echo \$this->renderEach('view.item', \$items, 'item'); ?>",
			],
			'@push'         => [
				"@push('scripts')",
				"<?php \$this->startSection('scripts'); ?>",
			],
			'@endpush'      => [
				'@endpush',
				'<?php $this->appendSection(); ?>',
			],
			'@stack'        => [
				"@stack('scripts')",
				"<?php echo \$this->yieldContent('scripts'); ?>",
			],
			'@jhtml'        => [
				"@jhtml('foo.bar', \$x)",
				"<?php echo \$this->getContainer()->html->get('foo.bar', \$x); ?>",
			],
			'@html'         => [
				"@html('foo.bar', \$x)",
				"<?php echo \$this->getContainer()->html->get('foo.bar', \$x); ?>",
			],
			'@inlineCss'    => [
				"@inlineCss('a{}')",
				"<?php \$this->container->application->getDocument()->addStyleDeclaration('a{}'); ?>",
			],
			'@inlineJs'     => [
				"@inlineJs('x=1')",
				"<?php \$this->container->application->getDocument()->addScriptDeclaration('x=1'); ?>",
			],
			'@route'        => [
				"@route('index.php?view=foo', [])",
				"<?php echo \$this->container->router->route('index.php?view=foo', []); ?>",
			],
			'@media'        => [
				"@media('img/x.png')",
				"<?php echo \\Awf\\Utils\\Template::parsePath('img/x.png'); ?>",
			],
			'@endrepeatable' => [
				'@endrepeatable',
				'<?php }; ?>',
			],
		];
	}

	#[DataProvider('regexFallbackDirectivesProvider')]
	public function testDirectiveOnRegexFallbackPath(string $template, string $expected): void
	{
		$this->blade->forceUsingTokenizer(false);
		self::assertSame($expected, $this->blade->compileString($template), 'regex-fallback path');
	}

	// =========================================================================
	// Mixed template integration with regex-fallback path
	// =========================================================================

	public function testMixedEchoAndDirectivesOnRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);

		$template = "@foreach(\$items as \$item)\n{{ \$item->name }}\n@endforeach";
		$result   = $this->blade->compileString($template);

		self::assertStringContainsString('foreach($items as $item):', $result);
		self::assertStringContainsString('$this->escape($item->name)', $result);
		self::assertStringContainsString('endforeach;', $result);
	}

	public function testPushStackRoundTripOnRegexFallback(): void
	{
		$this->blade->forceUsingTokenizer(false);

		$push  = $this->blade->compileString("@push('head')");
		$this->blade->resetCompilerState();
		$this->blade->forceUsingTokenizer(false);
		$end   = $this->blade->compileString('@endpush');
		$this->blade->resetCompilerState();
		$this->blade->forceUsingTokenizer(false);
		$stack = $this->blade->compileString("@stack('head')");

		self::assertSame("<?php \$this->startSection('head'); ?>", $push);
		self::assertSame('<?php $this->appendSection(); ?>', $end);
		self::assertSame("<?php echo \$this->yieldContent('head'); ?>", $stack);
	}

	// =========================================================================
	// Unknown directive — passed through unchanged
	// =========================================================================

	public function testUnknownDirectiveIsPassedThroughUnchanged(): void
	{
		// A directive with no matching compile* method must be emitted as-is
		$template = '@unknownDirectiveThatDoesNotExist';
		$result   = $this->blade->compileString($template);

		self::assertSame('@unknownDirectiveThatDoesNotExist', $result);
	}

	public function testUnknownDirectiveWithParensIsPassedThroughUnchanged(): void
	{
		$template = "@unknownDirectiveThatDoesNotExist('foo')";
		$result   = $this->blade->compileString($template);

		self::assertSame("@unknownDirectiveThatDoesNotExist('foo')", $result);
	}
}
