<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\Compiler;

/**
 * A compiler which reports the version of the format of its compiled output.
 *
 * The compiling view engines mix this version into the identifier of the cached, compiled template files. Changing
 * the version therefore makes the engine ignore every compiled file produced by an earlier version of the compiler,
 * and write new compiled files under different paths (which also sidesteps stale opcode cache entries).
 *
 * This is a separate interface, instead of a new method in CompilerInterface, so that existing third party compilers
 * implementing CompilerInterface keep working unchanged. Their compiled files keep using the unversioned identifier.
 *
 * @since  1.3.1
 */
interface VersionedCompilerInterface
{
	/**
	 * Returns the version of the format of the compiled output.
	 *
	 * It must change every time the compiled output changes in a way which makes previously compiled templates unsafe
	 * or incorrect to reuse.
	 *
	 * @return  string
	 * @since   1.3.1
	 */
	public function getOutputFormatVersion(): string;
}
