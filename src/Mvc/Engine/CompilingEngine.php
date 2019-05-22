<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Mvc\Engine;

use Awf\Utils\Buffer;
use Awf\Mvc\Compiler\CompilerInterface;

/**
 * View engine for compiling PHP template files.
 */
abstract class CompilingEngine extends AbstractEngine implements EngineInterface
{
	/** @var  CompilerInterface  The compiler used by this engine */
	protected $compiler = null;

	/**
	 * Get the 3ναlυa+3d contents of the view template. (I use leetspeak here because of bad quality hosts with broken scanners)
	 *
	 * @param   string  $path         The path to the view template
	 * @param   array   $forceParams  Any additional information to pass to the view template engine
	 *
	 * @return  array  Content evaluation information
	 */
	public function get($path, array $forceParams = array())
	{
		/**
		 * If the PHP Tokenizer extension is disabled or not present we try to fall back to precompiled templates.
		 *
		 * As we found out the hard way, there are still some hosts which disable / don't load the Tokenizer citing
		 * "security concerns". What they don't understand is that the tokenizer is basically a read-only extension. It
		 * can only read PHP code and break it into tokens, it cannot execute it. In fact, you can't easily convert
		 * tokenizer results back into executable PHP. In fact, you'd need https://github.com/nikic/PHP-Parser which is
		 * by no means trivial. Then again, it STILL cannot execute the PHP without either using 3v4l (sorry for the
		 * leetspeak, we have to deal with broken hosts killing our file due to this comment!) or writing to a PHP file
		 * and executing it. In short, the hosts which disable tokenizer don't know how PHP works, don't know the first
		 * thing about security and must NOT be trusted! If you are on such a host, run away fast, don't look back!
		 */
		if (!function_exists('token_get_all'))
		{
			$precompiledPath = $this->getPrecompiledPath($path);

			if (($precompiledPath !== false) && @file_exists($precompiledPath))
			{
				return array(
					'type'    => 'path',
					'content' => $precompiledPath,
				);
			}

			/**
			 * No precompiled templates and tokenized missing, i.e. I can't compile anything. Instead of throwing a
			 * fatal error I will throw a catchable runtime error explaining the error condition and how to solve it.
			 * If your extension does not trap the exception it will bubble up to Joomla's error handler which will
			 * display this message.
			 */
			throw new \RuntimeException("Your hosting provider has disabled the <code>token_get_all()</code> PHP function or they have not installed the Tokenizer extension for PHP. This is a safe and <em>secure</em> function of modern PHP, required to convert template files into HTML code your browser can display. Please ask them to enable it. This error occurred trying to render the template file <code>$path</code>", 500);
		}

		// If it's cached return the path to the cached file's path
		if ($this->isCached($path))
		{
			return array(
				'type'    => 'path',
				'content' => $this->getCachePath($path),
			);
		}

		/**
		 * Compile and cache the file. We also add the file path in a comment at the top of the file so phpStorm can
		 * debug it.
		 *
		 * @see https://blog.jetbrains.com/phpstorm/2019/02/phpstorm-2019-1-eap-191-5849-26/
		 * @see https://laravel-news.com/laravel-5-8-blade-template-file-path
		 */
		$content        = "<?php /* $path */ ?>\n";
		$content        .= $this->compile($path, $forceParams);
		$cacheFolder    = $this->view->getContainer()->temporaryPath;
		$cachedFilePath = $this->putToCache($path, $content);

		// If we could cache it, return the cached file's path
		if ($cachedFilePath !== false)
		{
			return array(
				'type'    => 'path',
				'content' => $cachedFilePath,
			);
		}

		// We could not write to the cache. Hm, can I use a stream wrapper?
		$canUseStreams = Buffer::canRegisterWrapper();

		if ($canUseStreams)
		{
			$id           = $this->getIdentifier($path);
			$streamPath   = 'awf://' . $this->view->getContainer()->application_name . '/compiled_templates/' . $id . '.php';

			file_put_contents($streamPath, $content);

			return array(
				'type'    => 'path',
				'content' => $streamPath,
			);
		}

		// I couldn't use a stream wrapper. I have to give up.
		$errorMessage = "Could not write to your temporary directory “%s”. Please make it writeable to PHP by changing the permissions. Alternatively, ask your host to make sure that they have not disabled the stream_wrapper_register() function in PHP. Moreover, if your host is using the Suhosin patch for PHP ask them to whitelist the awf:// stream wrapper in their server's php.ini file. If you do not understand what this means please contact your host and paste this entire message to them.";
		throw new \RuntimeException(sprintf($errorMessage, $cacheFolder), 500);
	}

	/**
	 * A method to compile the raw view template into valid PHP
	 *
	 * @param   string  $path         The path to the view template
	 * @param   array   $forceParams  Any additional information to pass to the view template compiler
	 *
	 * @return  string  The template compiled to executable PHP
	 */
	protected function compile($path, array $forceParams = array())
	{
		return $this->compiler->compile($path, $forceParams);
	}

	/**
	 * Returns a unique identifier for the template file being compiled
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  string  The unique identifier of the file
	 */
	protected function getIdentifier($path)
	{
		if (function_exists('sha1'))
		{
			return sha1($path);
		}

		return md5($path);
	}

	/**
	 * Returns the cached compiled version of the template file
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  string  The full path to the cached compiled file
	 */
	protected function getCachePath($path)
	{
		$id = $this->getIdentifier($path);
		return $this->view->getContainer()->temporaryPath . '/compiled_templates/' . $id . '.php';
	}

	/**
	 * Do we have a cached, compiled version of the template file which is up-to-date with the uncompiled file (we check
	 * the last modification timestamp)?
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  bool  True if we have a cached, compiled, up-to-date version of the file
	 */
	protected function isCached($path)
	{
		if (!$this->compiler->isCacheable())
		{
			return false;
		}

		$cachePath = $this->getCachePath($path);

		if (!file_exists($cachePath))
		{
			return false;
		}

		$cacheTime = filemtime($cachePath);
		$fileTime = filemtime($path);

		return $fileTime <= $cacheTime;
	}

	/**
	 * Get the cached, compiled version of the template file
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  bool|string  The ceched, compiled contents of the template file or boolean false if the file doesn't exist
	 */
	protected function getCached($path)
	{
		$cachePath = $this->getCachePath($path);

		return file_get_contents($cachePath);
	}

	/**
	 * Put the compiled version of a template file into the cache
	 *
	 * @param   string  $path     The full path to the template file which needs to be compiled
	 * @param   string  $content  The compiled content to cache
	 *
	 * @return  bool|string  The path of the cached file, or boolean false if writing to the cache failed
	 */
	protected function putToCache($path, $content)
	{
		$cachePath   = $this->getCachePath($path);
		$cacheFolder = dirname($cachePath);

		$this->makeCacheFolder($cacheFolder);

		if (@file_put_contents($cachePath, $content))
		{
			return $cachePath;
		}

		if ($this->view->getContainer()->fileSystem->write($cachePath, $content))
		{
			return $cachePath;
		}

		return false;
	}

	/**
	 * Makes sure the cache folder actually exists
	 *
	 * @param   string  $cacheFolder  The absolute filesystem path to the cache folder
	 *
	 * @return  void
	 */
	private function makeCacheFolder($cacheFolder)
	{
		if (@is_dir($cacheFolder))
		{
			return;
		}

		if (@mkdir($cacheFolder, 0755, true))
		{
			return;
		}

		$this->view->getContainer()->fileSystem->mkdir($cacheFolder, 0644);
	}

	/**
	 * Returns the path where I can find a precompiled version of the uncompiled view template which lives in $path
	 *
	 * @param   string  $path  The path to the uncompiled view template
	 *
	 * @return  bool|string  False if the view template is outside the component's front- or backend.
	 */
	public function getPrecompiledPath($path)
	{
		// Normalize the path to the file
		$path = realpath($path);

		if ($path === false)
		{
			// The file doesn't exist
			return false;
		}

		// Is this path under the application folder?
		$componentPath = realpath($this->view->getContainer()->basePath);
		$frontPos      = strpos($path, $componentPath);

		if ($frontPos !== 0)
		{
			// This is not a view template shipped with the application, i.e. it can't be precompiled
			return false;
		}

		// Eliminate the component path from $path to get the relative path to the file
		$relativePath = ltrim(substr($path, strlen($componentPath)), '\\/');

		// Break down the relative path to its parts
		$relativePath = str_replace('\\', '/', $relativePath);
		$pathParts    = explode('/', $relativePath);

		// Remove the prefix
		$prefix = array_shift($pathParts);

		// If it's a legacy view, View, Views, or views prefix remove the 'tmpl' part
		if ($prefix != 'ViewTemplates')
		{
			unset($pathParts[1]);
		}

		// Get the last part and process the extension
		$viewFile            = array_pop($pathParts);
		$extensionWithoutDot = $this->compiler->getFileExtension();
		$pathParts[]         = substr($viewFile, 0, -strlen($extensionWithoutDot)) . 'php';

		$precompiledRelativePath = implode(DIRECTORY_SEPARATOR, $pathParts);

		return $componentPath . DIRECTORY_SEPARATOR . 'PrecompiledTemplates' . DIRECTORY_SEPARATOR . $precompiledRelativePath;
	}
}
