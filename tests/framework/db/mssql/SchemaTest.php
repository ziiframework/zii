<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use function in_array;
use yii\db\DefaultValueConstraint;
use yii\db\mssql\Schema;
use yiiunit\framework\db\AnyValue;

/**
 * @group db
 * @group mssql
 *
 * @internal
 * @coversNothing
 */
class SchemaTest extends \yiiunit\framework\db\SchemaTest
{
    public $driverName = 'sqlsrv';

    protected $expectedSchemas = [
        'dbo',
    ];

    public function constraintsProvider()
    {
        $result = parent::constraintsProvider();
        $result['1: check'][2][0]->expression = '([C_check]<>\'\')';
        $result['1: default'][2] = [];
        $result['1: default'][2][] = new DefaultValueConstraint([
            'name' => AnyValue::getInstance(),
            'columnNames' => ['C_default'],
            'value' => '((0))',
        ]);

        $result['2: default'][2] = [];

        $result['3: foreign key'][2][0]->foreignSchemaName = 'dbo';
        $result['3: index'][2] = [];
        $result['3: default'][2] = [];

        $result['4: default'][2] = [];

        return $result;
    }

    public function testGetStringFieldsSize(): void
    {
        /** @var Connection $db */
        $db = $this->getConnection();

        /** @var Schema $schema */
        $schema = $db->schema;

        $columns = $schema->getTableSchema('type', false)->columns;

        foreach ($columns as $name => $column) {
            $type = $column->type;
            $size = $column->size;
            $dbType = $column->dbType;

            if (strpos($name, 'char_') === 0) {
                switch ($name) {
                    case 'char_col':
                        $expectedType = 'char';
                        $expectedSize = 100;
                        $expectedDbType = 'char(100)';

                        break;
                    case 'char_col2':
                        $expectedType = 'string';
                        $expectedSize = 100;
                        $expectedDbType = 'varchar(100)';

                        break;
                    case 'char_col3':
                        $expectedType = 'text';
                        $expectedSize = null;
                        $expectedDbType = 'text';

                        break;
                }

                $this->assertSame($expectedType, $type);
                $this->assertSame($expectedSize, $size);
                $this->assertSame($expectedDbType, $dbType);
            }
        }
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
            ['test', '[test]'],
            ['test.test', '[test].[test]'],
            ['test.test.test', '[test].[test].[test]'],
            ['[test]', '[test]'],
            ['[test].[test]', '[test].[test]'],
            ['test.[test.test]', '[test].[test.test]'],
            ['test.test.[test.test]', '[test].[test].[test.test]'],
            ['[test].[test.test]', '[test].[test.test]'],
        ];
    }

    /**
     * @dataProvider getTableSchemaDataProvider
     *
     * @param $name
     * @param $expectedName
     *
     * @throws \yii\base\NotSupportedException
     */
    public function testGetTableSchema($name, $expectedName): void
    {
        $schema = $this->getConnection()->getSchema();
        $tableSchema = $schema->getTableSchema($name);
        $this->assertSame($expectedName, $tableSchema->name);
    }

    public function getTableSchemaDataProvider()
    {
        return [
            ['[dbo].[profile]', 'profile'],
            ['dbo.profile', 'profile'],
            ['profile', 'profile'],
            ['dbo.[table.with.special.characters]', 'table.with.special.characters'],
        ];
    }

    public function getExpectedColumns()
    {
        $columns = parent::getExpectedColumns();
        unset($columns['enum_col'], $columns['ts_default'], $columns['bit_col'], $columns['json_col']);

        $columns['int_col']['dbType'] = 'int';
        $columns['int_col2']['dbType'] = 'int';
        $columns['tinyint_col']['dbType'] = 'tinyint';
        $columns['smallint_col']['dbType'] = 'smallint';
        $columns['float_col']['dbType'] = 'decimal';
        $columns['float_col']['phpType'] = 'string';
        $columns['float_col']['type'] = 'decimal';
        $columns['float_col']['scale'] = null;
        $columns['float_col2']['dbType'] = 'float';
        $columns['float_col2']['phpType'] = 'double';
        $columns['float_col2']['type'] = 'float';
        $columns['float_col2']['scale'] = null;
        $columns['blob_col']['dbType'] = 'varbinary';
        $columns['numeric_col']['dbType'] = 'decimal';
        $columns['numeric_col']['scale'] = null;
        $columns['time']['dbType'] = 'datetime';
        $columns['time']['type'] = 'datetime';
        $columns['bool_col']['dbType'] = 'tinyint';
        $columns['bool_col2']['dbType'] = 'tinyint';

        array_walk($columns, static function (&$item): void {
            $item['enumValues'] = [];
        });

        array_walk($columns, static function (&$item, $name): void {
            if (!in_array($name, ['char_col', 'char_col2', 'char_col3'], true)) {
                $item['size'] = null;
            }
        });

        array_walk($columns, static function (&$item, $name): void {
            if (!in_array($name, ['char_col', 'char_col2', 'char_col3'], true)) {
                $item['precision'] = null;
            }
        });

        return $columns;
    }

    public function testGetPrimaryKey(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testPKTable') !== null) {
            $db->createCommand()->dropTable('testPKTable')->execute();
        }

        $db->createCommand()->createTable('testPKTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();

        $insertResult = $db->getSchema()->insert('testPKTable', ['bar' => 1]);
        $selectResult = $db->createCommand('select [id] from [testPKTable] where [bar]=1')->queryOne();

        $this->assertSame($selectResult['id'], $insertResult['id']);
    }
}
