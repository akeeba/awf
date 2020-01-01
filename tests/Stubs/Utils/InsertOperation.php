<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Utils;

/**
 * Disables foreign key checks temporarily.
 */
class InsertOperation extends \PHPUnit_Extensions_Database_Operation_Insert
{
    public function execute(\PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection, \PHPUnit_Extensions_Database_DataSet_IDataSet $dataSet)
    {
        $connection->getConnection()->query("SET foreign_key_checks = 0");
        parent::execute($connection, $dataSet);
        $connection->getConnection()->query("SET foreign_key_checks = 1");
    }
}
