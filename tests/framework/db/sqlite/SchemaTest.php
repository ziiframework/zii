<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use yii\db\Constraint;
use yiiunit\framework\db\AnyValue;

/**
 * @group db
 * @group sqlite
 *
 * @internal
 * @coversNothing
 */
final class SchemaTest extends \yiiunit\framework\db\SchemaTest
{
    protected $driverName = 'sqlite';

    public function testGetSchemaNames(): void
    {
        $this->markTestSkipped('Schemas are not supported in SQLite.');
    }

    public function getExpectedColumns()
    {
        $columns = parent::getExpectedColumns();
        unset($columns['enum_col'], $columns['bit_col'], $columns['json_col']);

        $columns['int_col']['dbType'] = 'integer';
        $columns['int_col']['size'] = null;
        $columns['int_col']['precision'] = null;
        $columns['int_col2']['dbType'] = 'integer';
        $columns['int_col2']['size'] = null;
        $columns['int_col2']['precision'] = null;
        $columns['bool_col']['type'] = 'boolean';
        $columns['bool_col']['phpType'] = 'boolean';
        $columns['bool_col2']['type'] = 'boolean';
        $columns['bool_col2']['phpType'] = 'boolean';
        $columns['bool_col2']['defaultValue'] = true;

        return $columns;
    }

    public function testCompositeFk(): void
    {
        $schema = $this->getConnection()->schema;

        $table = $schema->getTableSchema('composite_fk');

        $this->assertCount(1, $table->foreignKeys);
        $this->assertTrue(isset($table->foreignKeys[0]));
        $this->assertSame('order_item', $table->foreignKeys[0][0]);
        $this->assertSame('order_id', $table->foreignKeys[0]['order_id']);
        $this->assertSame('item_id', $table->foreignKeys[0]['item_id']);
    }

    public function constraintsProvider()
    {
        $result = parent::constraintsProvider();
        $result['1: primary key'][2]->name = null;
        $result['1: check'][2][0]->columnNames = null;
        $result['1: check'][2][0]->expression = '"C_check" <> \'\'';
        $result['1: unique'][2][0]->name = AnyValue::getInstance();
        $result['1: index'][2][1]->name = AnyValue::getInstance();

        $result['2: primary key'][2]->name = null;
        $result['2: unique'][2][0]->name = AnyValue::getInstance();
        $result['2: index'][2][2]->name = AnyValue::getInstance();

        $result['3: foreign key'][2][0]->name = null;
        $result['3: index'][2] = [];

        $result['4: primary key'][2]->name = null;
        $result['4: unique'][2][0]->name = AnyValue::getInstance();

        $result['5: primary key'] = ['T_upsert', 'primaryKey', new Constraint([
            'name' => AnyValue::getInstance(),
            'columnNames' => ['id'],
        ])];

        return $result;
    }

    /**
     * @dataProvider quoteTableNameDataProvider
     *
     * @param $name
     * @param $expectedName
     *
     * @throws \yii\base\NotSupportedException
     */
    public function testQuoteTableName($name, $expectedName): void
    {
        $schema = $this->getConnection()->getSchema();
        $quotedName = $schema->quoteTableName($name);
        $this->assertSame($expectedName, $quotedName);
    }

    public function quoteTableNameDataProvider()
    {
        return [
            ['test', '`test`'],
            ['test.test', '`test`.`test`'],
            ['test.test.test', '`test`.`test`.`test`'],
            ['`test`', '`test`'],
            ['`test`.`test`', '`test`.`test`'],
            ['test.`test`.test', '`test`.`test`.`test`'],
        ];
    }
}
