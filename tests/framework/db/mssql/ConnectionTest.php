<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

/**
 * @group db
 * @group mssql
 */
class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    protected $driverName = 'sqlsrv';

    public function testQuoteValue(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals('[table]', $connection->quoteTableName('table'));
        $this->assertEquals('[table]', $connection->quoteTableName('[table]'));
        $this->assertEquals('[schema].[table]', $connection->quoteTableName('schema.table'));
        $this->assertEquals('[schema].[table]', $connection->quoteTableName('schema.[table]'));
        $this->assertEquals('[schema].[table]', $connection->quoteTableName('[schema].[table]'));
        $this->assertEquals('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals('[column]', $connection->quoteColumnName('column'));
        $this->assertEquals('[column]', $connection->quoteColumnName('[column]'));
        $this->assertEquals('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $connection->quoteColumnName('(column)'));

        $this->assertEquals('[column]', $connection->quoteSql('[[column]]'));
        $this->assertEquals('[column]', $connection->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $connection = $this->getConnection(false, false);
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('table.column'));
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('table.[column]'));
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('[table].column'));
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('[table].[column]'));

        $this->assertEquals('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}.[column]', $connection->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}.[column]', $connection->quoteColumnName('{{table}}.[column]'));
        $this->assertEquals('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}.[column]', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}.[column]', $connection->quoteColumnName('{{%table}}.[column]'));

        $this->assertEquals('[column.name]', $connection->quoteColumnName('[column.name]'));
        $this->assertEquals('[column.name.with.dots]', $connection->quoteColumnName('[column.name.with.dots]'));
        $this->assertEquals('[table].[column.name.with.dots]', $connection->quoteColumnName('[table].[column.name.with.dots]'));

        $this->assertEquals('[table].[column]', $connection->quoteSql('[[table.column]]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{table}}.[column]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{%table}}.[column]'));
    }
}
