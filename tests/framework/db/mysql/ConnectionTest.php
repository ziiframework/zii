<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use yii\db\Connection;

/**
 * @group db
 * @group mysql
 *
 * @internal
 * @coversNothing
 */
final class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    protected $driverName = 'mysql';

    /**
     * @doesNotPerformAssertions
     */
    public function testTransactionAutocommit(): void
    {
        /** @var Connection $connection */
        $connection = $this->getConnection(true);
        $connection->transaction(static function (Connection $db): void {
            // create table will cause the transaction to be implicitly committed
            // (see https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html)
            $name = 'test_implicit_transaction_table';
            $db->createCommand()->createTable($name, ['id' => 'pk'])->execute();
            $db->createCommand()->dropTable($name)->execute();
        });
        // If we made it this far without an error, then everything's working
    }
}
