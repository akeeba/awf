<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database\Fixture;

use Awf\Database\Driver\Pdomysql;
use Awf\Database\Query\Sqlite as BindableQuery;

/**
 * The PDO MySQL driver, handing out a query object which supports bind().
 *
 * Pdo::execute() binds the parameters of a QueryPreparable query. The MySQL query class is not one, and the SQLite
 * query class is the only one which is, so this borrows it: it is a plain Pdo query with bind() and no SQLite syntax.
 */
final class BindingPdomysql extends Pdomysql
{
	public function getQuery($new = false)
	{
		return $new ? new BindableQuery($this) : parent::getQuery($new);
	}
}
