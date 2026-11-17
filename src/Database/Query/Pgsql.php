<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Query;

use Awf\Database\QueryLimitable;
use Awf\Database\QueryPreparable;

/**
 * Query class of the PDO PostgreSQL driver.
 *
 * Unlike the native Postgresql driver's query class, this one supports bound parameters: Driver\Pgsql::execute()
 * prepares the statement and binds them.
 */
class Pgsql extends Postgresql implements QueryLimitable, QueryPreparable
{
	use Bindable;
}