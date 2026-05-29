<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\Compiler;

use Awf\Mvc\Compiler\Blade;
use Awf\Utils\HashHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Minimal Blade subclass that bypasses the Container dependency.
 *
 * Compilation only generates PHP source strings; the container is accessed
 * at template-execution time inside the generated code, never during the
 * compile pass itself.
 */
class TestBlade extends Blade
{
	public function __construct()
	{
		$this->conditionallyEnableTokenizer();
	}

	public function forceUsingTokenizer(bool $value): void
	{
		$this->usingTokenizer = $value;
	}

	/** Full reset — also clears repeatableOverrides. */
	public function resetCompilerState(): void
	{
		$this->footer              = [];
		$this->forelseCounter      = 0;
		$this->repeatableOverrides = [];
	}

	/** Partial reset — preserves repeatableOverrides across compile calls. */
	public function resetFooterAndCounter(): void
	{
		$this->footer         = [];
		$this->forelseCounter = 0;
	}
}

class BladeCompilerTest extends TestCase
{
	private TestBlade $blade;

	protected function setUp(): void
	{
		$this->blade = new TestBlade();
	}

	// -------------------------------------------------------------------------
	// Helper
	// -------------------------------------------------------------------------

	/**
	 * Compile with both the tokenizer path and the regex fallback path and
	 * assert both produce the expected result.
	 */
	private function assertBothPaths(string $expected, string $template): void
	{
		$this->blade->forceUsingTokenizer(true);
		self::assertSame($expected, $this->blade->compileString($template), 'tokenizer path');

		$this->blade->resetCompilerState();

		$this->blade->forceUsingTokenizer(false);
		self::assertSame($expected, $this->blade->compileString($template), 'regex path');
	}

	// -------------------------------------------------------------------------
	// Compiler metadata
	// -------------------------------------------------------------------------

	public function testIsCacheable(): void
	{
		self::assertTrue($this->blade->isCacheable());
	}

	public function testGetFileExtension(): void
	{
		self::assertSame('blade.php', $this->blade->getFileExtension());
	}

	public function testIsUsingTokenizerTrueOnModernPhp(): void
	{
		// PHP 8.5 ships with the Tokenizer extension; the self-test inside
		// the constructor should enable it.
		self::assertTrue($this->blade->isUsingTokenizer());
	}

	public function testSetAndGetPath(): void
	{
		$this->blade->setPath('/srv/app/views/home.blade.php');
		self::assertSame('/srv/app/views/home.blade.php', $this->blade->getPath());
	}

	// -------------------------------------------------------------------------
	// compileEchoDefaults (public helper)
	// -------------------------------------------------------------------------

	public function testEchoDefaultsPassesPlainVariableThrough(): void
	{
		self::assertSame('$var', $this->blade->compileEchoDefaults('$var'));
	}

	public function testEchoDefaultsCompilesOrFallback(): void
	{
		self::assertSame(
			"isset(\$var) ? \$var : 'default'",
			$this->blade->compileEchoDefaults("\$var or 'default'")
		);
	}

	public function testEchoDefaultsRequiresLeadingDollarSign(): void
	{
		// Without a leading $, the "or" fallback is not triggered.
		self::assertSame("foo or 'bar'", $this->blade->compileEchoDefaults("foo or 'bar'"));
	}

	// -------------------------------------------------------------------------
	// Echo / content tags
	// -------------------------------------------------------------------------

	public function testEscapedEcho(): void
	{
		$this->assertBothPaths('<?php echo $this->escape($name); ?>', '{{ $name }}');
	}

	public function testEscapedEchoTrimsInternalWhitespace(): void
	{
		$this->assertBothPaths('<?php echo $this->escape($name); ?>', '{{  $name  }}');
	}

	public function testEscapedEchoWithOrDefault(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->escape(isset(\$name) ? \$name : 'Guest'); ?>",
			"{{ \$name or 'Guest' }}"
		);
	}

	public function testTripleBraceRawEcho(): void
	{
		$this->assertBothPaths('<?php echo $name; ?>', '{{{ $name }}}');
	}

	public function testDoubleBangRawEcho(): void
	{
		$this->assertBothPaths('<?php echo $name; ?>', '{!! $name !!}');
	}

	public function testBladeComment(): void
	{
		$this->assertBothPaths('<?php /* this is a comment */ ?>', '{{-- this is a comment --}}');
	}

	public function testBladeCommentWithMultipleLines(): void
	{
		$this->assertBothPaths(
			"<?php /* line one\nline two */ ?>",
			"{{-- line one\nline two --}}"
		);
	}

	public function testAtPrefixEscapesDoubleBangEcho(): void
	{
		// @{!! ... !!} should emit the literal {!! ... !!} (@ is stripped).
		$this->assertBothPaths('{!! $name !!}', '@{!! $name !!}');
	}

	public function testPlainTextPassesThrough(): void
	{
		$html = '<div class="container"><p>Hello world</p></div>';
		$this->assertBothPaths($html, $html);
	}

	public function testNullCompileStringReturnsEmptyString(): void
	{
		self::assertSame('', $this->blade->compileString(null));
	}

	// -------------------------------------------------------------------------
	// Control flow — data-provider driven
	// -------------------------------------------------------------------------

	public static function controlFlowProvider(): array
	{
		return [
			'@if'         => ['@if($foo)',                   '<?php if($foo): ?>'],
			'@elseif'     => ['@elseif($foo)',               '<?php elseif($foo): ?>'],
			'@else'       => ['@else',                       '<?php else: ?>'],
			'@endif'      => ['@endif',                      '<?php endif; ?>'],
			'@unless'     => ['@unless($foo)',               '<?php if ( ! ($foo)): ?>'],
			'@endunless'  => ['@endunless',                  '<?php endif; ?>'],
			'@for'        => ['@for($i=0;$i<5;$i++)',        '<?php for($i=0;$i<5;$i++): ?>'],
			'@endfor'     => ['@endfor',                     '<?php endfor; ?>'],
			'@foreach'    => ['@foreach($items as $item)',   '<?php foreach($items as $item): ?>'],
			'@endforeach' => ['@endforeach',                 '<?php endforeach; ?>'],
			'@while'      => ['@while($cond)',               '<?php while($cond): ?>'],
			'@endwhile'   => ['@endwhile',                   '<?php endwhile; ?>'],
			'@endforelse' => ['@endforelse',                 '<?php endif; ?>'],
		];
	}

	#[DataProvider('controlFlowProvider')]
	public function testControlFlow(string $template, string $expected): void
	{
		$this->assertBothPaths($expected, $template);
	}

	public function testForelse(): void
	{
		// Directives must be on their own line (or at start of string) because
		// the statement regex requires \B (non-word boundary) before @.
		$template = "@forelse(\$users as \$user)\nitem\n@empty\nno users\n@endforelse";
		$expected = '<?php $__empty_1 = true; foreach($users as $user): $__empty_1 = false; ?>'
		            . "\nitem\n"
		            . '<?php endforeach; if ($__empty_1): ?>'
		            . "\nno users\n"
		            . '<?php endif; ?>';
		$this->assertBothPaths($expected, $template);
	}

	public function testForelesCounterResetsAfterEachPair(): void
	{
		// @forelse increments the counter, @empty decrements it back to 0, so
		// sequential (non-nested) forelses both produce $__empty_1.
		$template = "@forelse(\$a as \$x)\n@empty\n@endforelse\n"
		            . "@forelse(\$b as \$y)\n@empty\n@endforelse";
		$expected = '<?php $__empty_1 = true; foreach($a as $x): $__empty_1 = false; ?>'
		            . "\n<?php endforeach; if (\$__empty_1): ?>\n<?php endif; ?>\n"
		            . '<?php $__empty_1 = true; foreach($b as $y): $__empty_1 = false; ?>'
		            . "\n<?php endforeach; if (\$__empty_1): ?>\n<?php endif; ?>";
		$this->assertBothPaths($expected, $template);
	}

	// -------------------------------------------------------------------------
	// Template inheritance
	// -------------------------------------------------------------------------

	public function testSection(): void
	{
		$this->assertBothPaths("<?php \$this->startSection('content'); ?>", "@section('content')");
	}

	public function testSectionWithInlineValue(): void
	{
		$this->assertBothPaths(
			"<?php \$this->startSection('title', 'My Page'); ?>",
			"@section('title', 'My Page')"
		);
	}

	public function testEndsection(): void
	{
		$this->assertBothPaths('<?php $this->stopSection(); ?>', '@endsection');
	}

	public function testStop(): void
	{
		$this->assertBothPaths('<?php $this->stopSection(); ?>', '@stop');
	}

	public function testOverwrite(): void
	{
		$this->assertBothPaths('<?php $this->stopSection(true); ?>', '@overwrite');
	}

	public function testShow(): void
	{
		$this->assertBothPaths('<?php echo $this->yieldSection(); ?>', '@show');
	}

	public function testAppend(): void
	{
		$this->assertBothPaths('<?php $this->appendSection(); ?>', '@append');
	}

	public function testYield(): void
	{
		$this->assertBothPaths("<?php echo \$this->yieldContent('content'); ?>", "@yield('content')");
	}

	public function testYieldWithDefault(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->yieldContent('title', 'My App'); ?>",
			"@yield('title', 'My App')"
		);
	}

	public function testExtendsPushesLoadCallToFooter(): void
	{
		$expected = "\n<?php echo \$this->loadAnyTemplate('layout'); ?>";
		$this->assertBothPaths($expected, "@extends('layout')");
	}

	public function testExtendsAppearsAfterSectionsInOutput(): void
	{
		$template = "@extends('layout')\n@section('title', 'Hello')\n@endsection";
		$expected = "<?php \$this->startSection('title', 'Hello'); ?>\n"
		            . "<?php \$this->stopSection(); ?>\n"
		            . "<?php echo \$this->loadAnyTemplate('layout'); ?>";
		$this->assertBothPaths($expected, $template);
	}

	public function testInclude(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->loadAnyTemplate('partials/header'); ?>",
			"@include('partials/header')"
		);
	}

	public function testEach(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->renderEach('view.item', \$items, 'item'); ?>",
			"@each('view.item', \$items, 'item')"
		);
	}

	public function testPush(): void
	{
		$this->assertBothPaths("<?php \$this->startSection('scripts'); ?>", "@push('scripts')");
	}

	public function testEndpush(): void
	{
		$this->assertBothPaths('<?php $this->appendSection(); ?>', '@endpush');
	}

	public function testStack(): void
	{
		$this->assertBothPaths("<?php echo \$this->yieldContent('scripts'); ?>", "@stack('scripts')");
	}

	// -------------------------------------------------------------------------
	// AWF-specific directives
	// -------------------------------------------------------------------------

	public function testLang(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->getLanguage()->text('COM_EXAMPLE_KEY'); ?>",
			"@lang('COM_EXAMPLE_KEY')"
		);
	}

	public function testSprintf(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->getLanguage()->sprintf('COM_EXAMPLE_N_ITEMS', \$count); ?>",
			"@sprintf('COM_EXAMPLE_N_ITEMS', \$count)"
		);
	}

	public function testPlural(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->getLanguage()->plural('COM_EXAMPLE_N_ITEMS', \$n); ?>",
			"@plural('COM_EXAMPLE_N_ITEMS', \$n)"
		);
	}

	public function testToken(): void
	{
		$this->assertBothPaths(
			'<?php echo $this->container->session->getCsrfToken()->getValue(); ?>',
			'@token'
		);
	}

	public function testRoute(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->container->router->route('index.php?view=foo', []); ?>",
			"@route('index.php?view=foo', [])"
		);
	}

	public function testMedia(): void
	{
		$this->assertBothPaths(
			"<?php echo \\Awf\\Utils\\Template::parsePath('images/logo.png'); ?>",
			"@media('images/logo.png')"
		);
	}

	public function testCss(): void
	{
		$this->assertBothPaths(
			"<?php \\Awf\\Utils\\Template::addCss('style.css'); ?>",
			"@css('style.css')"
		);
	}

	public function testJs(): void
	{
		$this->assertBothPaths(
			"<?php \\Awf\\Utils\\Template::addJs('app.js'); ?>",
			"@js('app.js')"
		);
	}

	public function testInlineCss(): void
	{
		$this->assertBothPaths(
			"<?php \$this->container->application->getDocument()->addStyleDeclaration('.foo{color:red}'); ?>",
			"@inlineCss('.foo{color:red}')"
		);
	}

	public function testInlineJs(): void
	{
		$this->assertBothPaths(
			"<?php \$this->container->application->getDocument()->addScriptDeclaration('var x=1;'); ?>",
			"@inlineJs('var x=1;')"
		);
	}

	public function testJhtml(): void
	{
		$this->assertBothPaths(
			"<?php echo \$this->getContainer()->html->get('select.genericlist', \$options); ?>",
			"@jhtml('select.genericlist', \$options)"
		);
	}

	public function testHtmlIsAliasForJhtml(): void
	{
		$this->blade->forceUsingTokenizer(true);
		$jhtml = $this->blade->compileString("@jhtml('select.genericlist', \$options)");
		$this->blade->resetCompilerState();
		$html = $this->blade->compileString("@html('select.genericlist', \$options)");

		self::assertSame($jhtml, $html);
	}

	// -------------------------------------------------------------------------
	// Repeatable blocks
	// -------------------------------------------------------------------------

	public function testRepeatableDefinesAClosure(): void
	{
		$key    = HashHelper::md5("'myblock'");
		$result = $this->blade->compileString("@repeatable('myBlock')");

		self::assertSame("<?php @\$this->repeatableMap['$key'] = function() { ?>", $result);
	}

	public function testRepeatableWithArguments(): void
	{
		$key    = HashHelper::md5("'myblock'");
		$result = $this->blade->compileString("@repeatable('myBlock', \$arg1, \$arg2)");

		self::assertSame(
			"<?php @\$this->repeatableMap['$key'] = function( \$arg1, \$arg2) { ?>",
			$result
		);
	}

	public function testEndrepeatableClosesTheClosure(): void
	{
		$this->assertBothPaths('<?php }; ?>', '@endrepeatable');
	}

	public function testYieldRepeatableCallsTheClosure(): void
	{
		$key      = HashHelper::md5("'myblock'");
		$result   = $this->blade->compileString("@yieldRepeatable('myBlock')");
		$expected = "<?php try { \$this->repeatableMap['$key'](); } catch (\Throwable \$e)"
		            . " { throw new \RuntimeException(sprintf('Error calling repeatable \"%s\"', 'myBlock'), 500, \$e); } ?>";

		self::assertSame($expected, $result);
	}

	public function testRepeatableOverridePreventsFutureNonOverrides(): void
	{
		$key = HashHelper::md5("'myblock'");

		// First call registers the override and records the key.
		$first = $this->blade->compileString("@repeatableOverride('myBlock')");
		self::assertStringContainsString("repeatableMap['$key']", $first);

		// Clear footer but keep $repeatableOverrides intact.
		$this->blade->resetFooterAndCounter();

		// A subsequent @repeatable for the same block is suppressed with SKIP_.
		$second = $this->blade->compileString("@repeatable('myBlock')");
		self::assertStringContainsString("repeatableMap['SKIP_$key']", $second);
	}

	// -------------------------------------------------------------------------
	// Custom extensions
	// -------------------------------------------------------------------------

	public function testExtendRegistersCustomCompiler(): void
	{
		$this->blade->extend(function (string $value): string {
			return str_replace('@hello', '<?php echo "Hello!"; ?>', $value);
		});

		$this->assertBothPaths('<?php echo "Hello!"; ?>', '@hello');
	}

	public function testMultipleExtensionsAreChained(): void
	{
		$this->blade->extend(fn(string $v) => str_replace('A', 'B', $v));
		$this->blade->extend(fn(string $v) => str_replace('B', 'C', $v));

		self::assertSame('C', $this->blade->compileString('A'));
	}

	// -------------------------------------------------------------------------
	// Matcher helpers
	// -------------------------------------------------------------------------

	public function testCreateMatcher(): void
	{
		self::assertSame('/(?<!\w)(\s*)@foo(\s*\(.*\))/', $this->blade->createMatcher('foo'));
	}

	public function testCreateOpenMatcher(): void
	{
		self::assertSame('/(?<!\w)(\s*)@foo(\s*\(.*)\)/', $this->blade->createOpenMatcher('foo'));
	}

	public function testCreatePlainMatcher(): void
	{
		self::assertSame('/(?<!\w)(\s*)@foo(\s*)/', $this->blade->createPlainMatcher('foo'));
	}

	// -------------------------------------------------------------------------
	// Integration: mixed directives and echo in a single template
	// -------------------------------------------------------------------------

	public function testMixedDirectivesAndEcho(): void
	{
		// compileEscapedEchos doubles the newline that follows a closing }} —
		// it consumes the original \n and then re-emits it twice as whitespace
		// preservation, producing a blank line after the echo statement.
		$template = "@if(\$show)\n{{ \$message }}\n@endif";
		$expected = "<?php if(\$show): ?>\n<?php echo \$this->escape(\$message); ?>\n\n<?php endif; ?>";
		$this->assertBothPaths($expected, $template);
	}

	public function testForeachWithEchoBody(): void
	{
		$template = "@foreach(\$items as \$item)\n{{ \$item }}\n@endforeach";
		$expected = "<?php foreach(\$items as \$item): ?>\n<?php echo \$this->escape(\$item); ?>\n\n<?php endforeach; ?>";
		$this->assertBothPaths($expected, $template);
	}
}
