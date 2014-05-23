<?php
/**
 * @package     Quickstart.Standalone
 * @copyright   2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

// The base path of your application installation
define('APATH_BASE',          __DIR__);
// Same as above
define('APATH_ROOT',          APATH_BASE);

// Where the index.php file is located
define('APATH_SITE',          APATH_BASE);
// Path to your application template directory. Must be under the web root.
define('APATH_THEMES',        APATH_BASE . '/templates');
// Path to your application language directory. Can be off the site's root.
define('APATH_TRANSLATION',   APATH_BASE . '/languages');