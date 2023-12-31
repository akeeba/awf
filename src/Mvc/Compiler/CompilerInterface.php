<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\Compiler;

interface CompilerInterface
{
	/**
	 * Are the results of this compiler engine cacheable? If the engine makes use of the forcedParams it must return
	 * false.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isCacheable(): bool;

	/**
	 * Compile a view template into PHP and HTML
	 *
	 * @param   string  $path         The absolute filesystem path of the view template
	 * @param   array   $forceParams  Any parameters to force (only for engines returning raw HTML)
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	public function compile(string $path, array $forceParams = array()): string;

	/**
	 * Returns the file extension supported by this compiler
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getFileExtension(): string;
}
