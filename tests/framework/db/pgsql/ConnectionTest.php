<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use yii\db\Transaction;

/**
 * @group db
 * @group pgsql
 *
 * @internal
 * @coversNothing
 */
final class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    protected $driverName = 'pgsql';

    public function testConnection(): void
    {
        $this->assertIsObject($this->getConnection(true));
    }

    public function testQuoteValue(): void
    {
        $connection = $this->getConnection(false);
        $this->assertSame(123, $connection->quoteValue(123));
        $this->assertSame("'string'", $connection->quoteValue('string'));
        $this->assertSame("'It''s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertSame('"table"', $connection->quoteTableName('table'));
        $this->assertSame('"table"', $connection->quoteTableName('"table"'));
        $this->assertSame('"schema"."table"', $connection->quoteTableName('schema.table'));
        $this->assertSame('"schema"."table"', $connection->quoteTableName('schema."table"'));
        $this->assertSame('"schema"."table"', $connection->quoteTableName('"schema"."table"'));
        $this->assertSame('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertSame('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertSame('"column"', $connection->quoteColumnName('column'));
        $this->assertSame('"column"', $connection->quoteColumnName('"column"'));
        $this->assertSame('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertSame('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertSame('(column)', $connection->quoteColumnName('(column)'));

        $this->assertSame('"column"', $connection->quoteSql('[[column]]'));
        $this->assertSame('"column"', $connection->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $connection = $this->getConnection(false, false);
        $this->assertSame('"table"."column"', $connection->quoteColumnName('table.column'));
        $this->assertSame('"table"."column"', $connection->quoteColumnName('table."column"'));
        $this->assertSame('"table"."column"', $connection->quoteColumnName('"table".column'));
        $this->assertSame('"table"."column"', $connection->quoteColumnName('"table"."column"'));

        $this->assertSame('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertSame('{{table}}."column"', $connection->quoteColumnName('{{table}}.column'));
        $this->assertSame('{{table}}."column"', $connection->quoteColumnName('{{table}}."column"'));
        $this->assertSame('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertSame('{{%table}}."column"', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertSame('{{%table}}."column"', $connection->quoteColumnName('{{%table}}."column"'));

        $this->assertSame('"table"."column"', $connection->quoteSql('[[table.column]]'));
        $this->assertSame('"table"."column"', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertSame('"table"."column"', $connection->quoteSql('{{table}}."column"'));
        $this->assertSame('"table"."column"', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertSame('"table"."column"', $connection->quoteSql('{{%table}}."column"'));
    }

    public function testTransactionIsolation(): void
    {
        $connection = $this->getConnection(true);

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE . ' READ ONLY DEFERRABLE');
        $transaction->commit();

        $this->assertTrue(true); // No error occurred – assert passed.
    }
}
