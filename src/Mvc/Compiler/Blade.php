<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

/** @noinspection PhpUnused */

namespace Awf\Mvc\Compiler;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Text\Text;
use Awf\Utils\HashHelper;

require_once __DIR__ . '/../../Utils/helpers.php';

/**
 * Blade templates compiler into regular PHP code
 *
 * @since        1.0.0
 */
class Blade implements CompilerInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Are the results of this engine cacheable?
	 *
	 * @var bool
	 */
	protected $isCacheable = true;

	/**
	 * The extension of the template files supported by this compiler
	 *
	 * @var    string
	 */
	protected $fileExtension = 'blade.php';

	/**
	 * All the registered compiler extensions.
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * The file currently being compiled.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * All the available compiler functions. Each one is called against every HTML block in the template.
	 *
	 * @var array
	 */
	protected $compilers = [
		'Extensions',
		'Statements',
		'Content',
	];

	private const TAG_COMMENT = 0;

	private const TAG_RAW = 1;

	private const TAG_ESCAPED = 2;

	/**
	 * Tuples of content tags.
	 *
	 * Content tags are processed in the order presented in this array. Shorter tags which may be a subset of a longer
	 * tag (e.g. `{{` versus `{{{`) MUST appear AFTER the longer tag.
	 *
	 * @var array[]
	 */
	protected $tags = [
		['{{--', '--}}', self::TAG_COMMENT],
		['{{{', '}}}', self::TAG_RAW],
		['{!!', '!!}', self::TAG_RAW],
		['{{', '}}', self::TAG_ESCAPED],
	];

	/**
	 * Array of footer lines to be added to template.
	 *
	 * @var array
	 */
	protected $footer = [];

	/**
	 * Counter to keep track of nested forelse statements.
	 *
	 * @var int
	 */
	protected $forelseCounter = 0;

	/**
	 * Should I use the PHP Tokenizer extension to compile Blade templates? Default is true and is preferable. We expect
	 * this to be false only on bad quality hosts. It can be overridden with Reflection for testing purposes.
	 *
	 * @var bool
	 */
	protected $usingTokenizer = false;

	/**
	 * Maps the name of `repeatable` blocks to the method names generated for them, therefore allowing overrides.
	 *
	 * @var   array
	 * @since 1.2.0
	 */
	protected $repeatableMap = [];

	/**
	 * List of `repeatableOverride` sections which prevent future `repeatable` statements from overriding them.
	 *
	 * @var   array
	 * @since 1.2.0
	 */
	protected $repeatableOverrides = [];

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The application container.
	 *
	 * @since   1.0.0
	 */
	public function __construct(Container $container)
	{
		$this->setContainer($container);
		$this->conditionallyEnableTokenizer();
	}

	/**
	 * Report if the PHP Tokenizer extension is being used
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isUsingTokenizer(): bool
	{
		return $this->usingTokenizer;
	}

	/**
	 * Are the results of this compiler engine cacheable? If the engine makes use of the forcedParams it must return
	 * false.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isCacheable(): bool
	{
		return $this->isCacheable;
	}

	/**
	 * Compile a view template into PHP and HTML
	 *
	 * @param   string  $path         The absolute filesystem path of the view template
	 * @param   array   $forceParams  Any parameters to force (only for engines returning raw HTML)
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function compile(string $path, array $forceParams = []): string
	{
		$this->footer = [];

		$fileData = @file_get_contents($path);

		if ($path)
		{
			$this->setPath($path);
		}

		return $this->compileString($fileData);
	}


	/**
	 * Get the path currently being compiled.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Set the path currently being compiled.
	 *
	 * @param   string  $path
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function setPath(string $path)
	{
		$this->path = $path;
	}

	/**
	 * Compile the given Blade template contents.
	 *
	 * @param   string|null  $value  The string to compile
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function compileString(?string $value): string
	{
		$value  = $value ?? '';
		$result = '';

		if ($this->usingTokenizer)
		{
			/**
			 * Here we will loop through all the tokens returned by the Zend lexer and parse each one into the
			 * corresponding, valid PHP.
			 */
			foreach (token_get_all($value) as $token)
			{
				$result .= is_array($token) ? $this->parseToken($token) : $token;
			}
		}
		else
		{
			foreach ($this->compilers as $type)
			{
				$value = $this->{"compile{$type}"}($value);
			}

			$result .= $value;
		}

		/**
		 * If there are any footer lines that need to get added to a template we will add them here at the end of the
		 * template. This gets used mainly for the template inheritance via the extends keyword that should be appended.
		 */
		if (count($this->footer) > 0)
		{
			$result = ltrim($result, PHP_EOL) . PHP_EOL . implode(PHP_EOL, array_reverse($this->footer));
		}

		return $result;
	}

	/**
	 * Compile the default values for the echo statement.
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function compileEchoDefaults($value)
	{
		return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
	}

	/**
	 * Register a custom Blade compiler.
	 *
	 * @param   callable  $compiler
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function extend(callable $compiler)
	{
		$this->extensions[] = $compiler;
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param   string  $function
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function createMatcher(string $function): string
	{
		return '/(?<!\w)(\s*)@' . $function . '(\s*\(.*\))/';
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param   string  $function
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function createOpenMatcher(string $function): string
	{
		return '/(?<!\w)(\s*)@' . $function . '(\s*\(.*)\)/';
	}

	/**
	 * Create a plain Blade matcher.
	 *
	 * @param   string  $function
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function createPlainMatcher(string $function): string
	{
		return '/(?<!\w)(\s*)@' . $function . '(\s*)/';
	}

	/**
	 * Returns the file extension supported by this compiler
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getFileExtension(): string
	{
		return $this->fileExtension;
	}

	/**
	 * Parse the tokens from the template.
	 *
	 * @param   array  $token
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function parseToken(array $token): string
	{
		[$id, $content] = $token;

		if ($id == T_INLINE_HTML)
		{
			foreach ($this->compilers as $type)
			{
				$content = $this->{"compile{$type}"}($content);
			}
		}

		return $content;
	}

	/**
	 * Execute the user defined extensions.
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileExtensions(string $value): string
	{
		foreach ($this->extensions as $compiler)
		{
			$value = call_user_func($compiler, $value, $this);
		}

		return $value;
	}

	protected function compileContent(string $value): string
	{
		foreach ($this->tags as [$openTag, $closeTag, $type])
		{
			switch ($type)
			{
				case self::TAG_COMMENT:
					$value = $this->compileComments($value, $openTag, $closeTag);
					break;

				case self::TAG_RAW:
					$value = $this->compileRegularEchos($value, $openTag, $closeTag);
					break;

				case self::TAG_ESCAPED:
					$value = $this->compileEscapedEchos($value, $openTag, $closeTag);
					break;
			}
		}

		return $value;
	}

	/**
	 * Compile Blade comments into valid PHP.
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileComments(string $value, string $openTag, string $closeTag): string
	{
		return preg_replace(
			sprintf('/%s((.|\s)*?)%s/', $openTag, $closeTag),
			'<?php /*$1*/ ?>',
			$value
		);
	}

	/**
	 * Compile the "regular" echo statements.
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileRegularEchos(string $value, string $openTag, string $closeTag): string
	{
		return preg_replace_callback(
			sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $openTag, $closeTag),
			function(array $matches)
			{
				return $matches[1]
					? substr($matches[0], 1)
					: '<?php echo ' . $this->compileEchoDefaults($matches[2]) . '; ?>' .
					  // Whitespaces
					  (empty($matches[3]) ? '' : $matches[3] . $matches[3]);
			},
			$value
		);
	}

	/**
	 * Compile the escaped echo statements.
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEscapedEchos(string $value, string $openTag, string $closeTag): string
	{
		return preg_replace_callback(
			sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', $openTag, $closeTag),
			function(array $matches)
			{
				return '<?php echo $this->escape(' . $this->compileEchoDefaults($matches[1]) . '); ?>'
				       // Whitespace
				       . (empty($matches[2]) ? '' : $matches[2] . $matches[2]);
			},
			$value
		);
	}

	/**
	 * Compile Blade Statements that start with "@"
	 *
	 * @param   string  $value
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileStatements(string $value): string
	{
		return preg_replace_callback(
			'/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', [$this, 'compileStatementsCallback'], $value
		);
	}

	/**
	 * Callback for compileStatements, since $this is not allowed in Closures under PHP 5.3.
	 *
	 * @param   array  $match
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileStatementsCallback(array $match): string
	{
		if (method_exists($this, $method = 'compile' . ucfirst($match[1])))
		{
			$match[0] = $this->$method(akeeba_array_get($match, 3));
		}

		return isset($match[3]) ? $match[0] : $match[0] . $match[2];
	}

	/**
	 * Compile the `each` statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEach(string $expression): string
	{
		return "<?php echo \$this->renderEach{$expression}; ?>";
	}

	/**
	 * Compile the yield statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileYield(string $expression): string
	{
		return "<?php echo \$this->yieldContent{$expression}; ?>";
	}

	/**
	 * Compile the show statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileShow(?string $expression): string
	{
		return "<?php echo \$this->yieldSection(); ?>";
	}

	/**
	 * Compile the section statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileSection(string $expression): string
	{
		return "<?php \$this->startSection{$expression}; ?>";
	}

	/**
	 * Compile the append statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileAppend(?string $expression): string
	{
		return "<?php \$this->appendSection(); ?>";
	}

	/**
	 * Compile the end-section statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndsection(?string $expression): string
	{
		return "<?php \$this->stopSection(); ?>";
	}

	/**
	 * Compile the stop statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileStop(?string $expression): string
	{
		return "<?php \$this->stopSection(); ?>";
	}

	/**
	 * Compile the overwrite statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileOverwrite(?string $expression): string
	{
		return "<?php \$this->stopSection(true); ?>";
	}

	/**
	 * Compile the unless statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileUnless(?string $expression): string
	{
		return "<?php if ( ! $expression): ?>";
	}

	/**
	 * Compile the end unless statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndunless(?string $expression): string
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the repeatable statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileRepeatable(string $expression, bool $isOverride = false): string
	{
		$expression    = trim($expression, '()');
		$parts         = explode(',', $expression, 2);
		$argumentsList = $parts[1] ?? '';

		$key = HashHelper::md5(strtolower(trim($parts[0])));

		if ($isOverride)
		{
			$this->repeatableOverrides[] = $key;
		}

		if (!$isOverride && in_array($key, $this->repeatableOverrides))
		{
			$key = "SKIP_" . $key;
		}

		return "<?php @\$this->repeatableMap['$key'] = function($argumentsList) { ?>";
	}

	/**
	 * Compile the repeatableOverride statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.2.0
	 */
	protected function compileRepeatableOverride(string $expression): string
	{
		return $this->compileRepeatable($expression, true);
	}

	/**
	 * Compile the end endRepeatable statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndRepeatable(?string $expression): string
	{
		return "<?php }; ?>";
	}

	/**
	 * Compile the end yieldRepeatable statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileYieldRepeatable(string $expression): string
	{
		$expression    = trim($expression, '()');
		$parts         = explode(',', $expression, 2);
		$argumentsList = $parts[1] ?? '';
		$key           = HashHelper::md5(strtolower(trim($parts[0])));

		return "<?php try { \$this->repeatableMap['$key']($argumentsList); } catch (\Throwable \$e) { throw new \RuntimeException(sprintf('Error calling repeatable \"%s\"', {$parts[0]}), 500, \$e); } ?>";
	}

	/**
	 * Compile the lang statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileLang(string $expression): string
	{
		return "<?php echo \$this->getLanguage()->text$expression; ?>";
	}

	/**
	 * Compile the sprintf statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileSprintf(string $expression): string
	{
		return "<?php echo \$this->getLanguage()->sprintf$expression; ?>";
	}

	/**
	 * Compile the plural statements into valid PHP.
	 *
	 * e.g. `@plural('COM_FOOBAR_N_ITEMS_SAVED', $countItemsSaved)`
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 * @see     Text::plural()
	 *
	 */
	protected function compilePlural(string $expression): string
	{
		return "<?php echo \$this->getLanguage()->plural$expression; ?>";
	}

	/**
	 * Compile the token statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileToken(?string $expression): string
	{
		return "<?php echo \$this->container->session->getCsrfToken()->getValue(); ?>";
	}

	/**
	 * Compile the else statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileElse(?string $expression): string
	{
		return "<?php else: ?>";
	}

	/**
	 * Compile the for statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileFor(string $expression): string
	{
		return "<?php for{$expression}: ?>";
	}

	/**
	 * Compile the foreach statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileForeach(string $expression): string
	{
		return "<?php foreach{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileForelse(string $expression): string
	{
		$empty = '$__empty_' . ++$this->forelseCounter;

		return "<?php {$empty} = true; foreach{$expression}: {$empty} = false; ?>";
	}

	/**
	 * Compile the if statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileIf(string $expression): string
	{
		return "<?php if{$expression}: ?>";
	}

	/**
	 * Compile the else-if statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileElseif(string $expression): string
	{
		return "<?php elseif{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEmpty(?string $expression): string
	{
		$empty = '$__empty_' . $this->forelseCounter--;

		return "<?php endforeach; if ({$empty}): ?>";
	}

	/**
	 * Compile the while statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileWhile(string $expression): string
	{
		return "<?php while{$expression}: ?>";
	}

	/**
	 * Compile the end-while statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndwhile(?string $expression): string
	{
		return "<?php endwhile; ?>";
	}

	/**
	 * Compile the end-for statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndfor(?string $expression): string
	{
		return "<?php endfor; ?>";
	}

	/**
	 * Compile the end-for-each statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndforeach(?string $expression): string
	{
		return "<?php endforeach; ?>";
	}

	/**
	 * Compile the end-if statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndif(?string $expression): string
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the end-for-else statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndforelse(?string $expression): string
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the extends statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileExtends(string $expression): string
	{
		if (akeeba_starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		$data = "<?php echo \$this->loadAnyTemplate($expression); ?>";

		$this->footer[] = $data;

		return '';
	}

	/**
	 * Compile the include statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileInclude(string $expression): string
	{
		if (akeeba_starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		return "<?php echo \$this->loadAnyTemplate($expression); ?>";
	}

	/**
	 * Compile the stack statements into the content
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileStack(string $expression): string
	{
		return "<?php echo \$this->yieldContent{$expression}; ?>";
	}

	/**
	 * Compile the push statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compilePush(string $expression): string
	{
		return "<?php \$this->startSection{$expression}; ?>";
	}

	/**
	 * Compile the endpush statements into valid PHP.
	 *
	 * @param   string|null  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileEndpush(?string $expression): string
	{
		return "<?php \$this->appendSection(); ?>";
	}

	/**
	 * Compile the route statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileRoute(string $expression): string
	{
		return "<?php echo \$this->container->router->route{$expression}; ?>";
	}

	/**
	 * Compile the css statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileCss(string $expression): string
	{
		return "<?php \\Awf\\Utils\\Template::addCss{$expression}; ?>";
	}

	/**
	 * Compile the inlineCss statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileInlineCss(string $expression): string
	{
		return "<?php \$this->container->application->getDocument()->addStyleDeclaration{$expression}; ?>";
	}

	/**
	 * Compile the inlineJs statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileInlineJs(string $expression): string
	{
		return "<?php \$this->container->application->getDocument()->addScriptDeclaration{$expression}; ?>";
	}

	/**
	 * Compile the js statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileJs(string $expression): string
	{
		return "<?php \\Awf\\Utils\\Template::addJs{$expression}; ?>";
	}

	/**
	 * Compile the jhtml statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileJhtml(string $expression): string
	{
		return '<' . '?php echo $this->getContainer()->html->get' . $expression . '; ?' . '>';
	}

	/**
	 * Compile the html statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileHtml(string $expression): string
	{
		return $this->compileJhtml($expression);
	}

	/**
	 * Compile the media statements into valid PHP.
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function compileMedia(string $expression): string
	{
		return "<?php echo \\Awf\\Utils\\Template::parsePath{$expression}; ?>";
	}

	/**
	 * Enable the PHP Tokenizer if it is enabled and found to be working correctly
	 *
	 * @return  void
	 * @since   1.1.0
	 * @see     https://www.akeeba.com/support/akeeba-backup-wordpress/39513-user-interface-shows-code-instead-of-control-element-texts.html
	 */
	private function conditionallyEnableTokenizer(): void
	{
		$this->usingTokenizer = function_exists('token_get_all') && defined('T_INLINE_HTML');

		if (!$this->usingTokenizer)
		{
			return;
		}

		$uncompiledSource = "@lang('TEST')";
		$actual           = trim($this->compileString($uncompiledSource));
		$expected         = trim($this->compileLang("('TEST')"));

		$this->usingTokenizer = $actual === $expected && $actual !== $uncompiledSource;
	}


}
