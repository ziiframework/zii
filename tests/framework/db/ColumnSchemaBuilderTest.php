<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\db;

use function call_user_func_array;
use yii\db\ColumnSchemaBuilder;
use yii\db\Expression;
use yii\db\Schema;

abstract class ColumnSchemaBuilderTest extends DatabaseTestCase
{
    /**
     * @param string $type
     * @param int    $length
     *
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }

    /**
     * @return array
     */
    public function typesProvider()
    {
        return [
            ['integer NULL DEFAULT NULL', Schema::TYPE_INTEGER, null, [
                ['unsigned'], ['null'],
            ]],
            ['integer(10)', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['timestamp() WITH TIME ZONE NOT NULL', 'timestamp() WITH TIME ZONE', null, [
                ['notNull'],
            ]],
            ['timestamp() WITH TIME ZONE DEFAULT NOW()', 'timestamp() WITH TIME ZONE', null, [
                ['defaultValue', new Expression('NOW()')],
            ]],
            ['integer(10)', Schema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],
        ];
    }

    /**
     * @dataProvider typesProvider
     *
     * @param string   $expected
     * @param string   $type
     * @param null|int $length
     * @param mixed    $calls
     */
    public function testCustomTypes($expected, $type, $length, $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }

    /**
     * @param string   $expected
     * @param string   $type
     * @param null|int $length
     * @param array    $calls
     */
    public function checkBuildString($expected, $type, $length, $calls): void
    {
        $builder = $this->getColumnSchemaBuilder($type, $length);

        foreach ($calls as $call) {
            $method = array_shift($call);
            call_user_func_array([$builder, $method], $call);
        }

        $this->assertEquals($expected, $builder->__toString());
    }
}
