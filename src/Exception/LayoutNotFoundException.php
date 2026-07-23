<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Exception;

/**
 * Class LayoutNotFoundException
 *
 * Thrown when a view template (layout) file cannot be resolved to an existing path. This is the signal that
 * View::loadTemplate() uses to fall back to the next candidate layout; it must therefore be distinguishable from
 * genuine render-time exceptions, which should propagate instead of being swallowed by the fallback loop.
 *
 * @package Awf\Exception
 *
 * @codeCoverageIgnore
 */
class LayoutNotFoundException extends \Exception implements Generic
{
}
