<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

/**
 * @group db
 * @group mssql
 *
 * @internal
 * @coversNothing
 */
final class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    protected $driverName = 'sqlsrv';

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
        $this->assertSame('[table]', $connection->quoteTableName('table'));
        $this->assertSame('[table]', $connection->quoteTableName('[table]'));
        $this->assertSame('[schema].[table]', $connection->quoteTableName('schema.table'));
        $this->assertSame('[schema].[table]', $connection->quoteTableName('schema.[table]'));
        $this->assertSame('[schema].[table]', $connection->quoteTableName('[schema].[table]'));
        $this->assertSame('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertSame('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertSame('[column]', $connection->quoteColumnName('column'));
        $this->assertSame('[column]', $connection->quoteColumnName('[column]'));
        $this->assertSame('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertSame('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertSame('(column)', $connection->quoteColumnName('(column)'));

        $this->assertSame('[column]', $connection->quoteSql('[[column]]'));
        $this->assertSame('[column]', $connection->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $connection = $this->getConnection(false, false);
        $this->assertSame('[table].[column]', $connection->quoteColumnName('table.column'));
        $this->assertSame('[table].[column]', $connection->quoteColumnName('table.[column]'));
        $this->assertSame('[table].[column]', $connection->quoteColumnName('[table].column'));
        $this->assertSame('[table].[column]', $connection->quoteColumnName('[table].[column]'));

        $this->assertSame('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertSame('{{table}}.[column]', $connection->quoteColumnName('{{table}}.column'));
        $this->assertSame('{{table}}.[column]', $connection->quoteColumnName('{{table}}.[column]'));
        $this->assertSame('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertSame('{{%table}}.[column]', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertSame('{{%table}}.[column]', $connection->quoteColumnName('{{%table}}.[column]'));

        $this->assertSame('[column.name]', $connection->quoteColumnName('[column.name]'));
        $this->assertSame('[column.name.with.dots]', $connection->quoteColumnName('[column.name.with.dots]'));
        $this->assertSame('[table].[column.name.with.dots]', $connection->quoteColumnName('[table].[column.name.with.dots]'));

        $this->assertSame('[table].[column]', $connection->quoteSql('[[table.column]]'));
        $this->assertSame('[table].[column]', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertSame('[table].[column]', $connection->quoteSql('{{table}}.[column]'));
        $this->assertSame('[table].[column]', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertSame('[table].[column]', $connection->quoteSql('{{%table}}.[column]'));
    }
}
