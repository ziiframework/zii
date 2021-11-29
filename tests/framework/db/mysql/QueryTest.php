<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use yii\db\Expression;
use yii\db\Query;

/**
 * @group db
 * @group mysql
 */
class QueryTest extends \yiiunit\framework\db\QueryTest
{
    protected $driverName = 'mysql';

    /**
     * Tests MySQL specific syntax for index hints.
     */
    public function testQueryIndexHint(): void
    {
        $db = $this->getConnection();

        $query = (new Query())->from([new Expression('{{%customer}} USE INDEX (primary)')]);
        $row = $query->one($db);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('email', $row);
    }

    public function testLimitOffsetWithExpression(): void
    {
        $query = (new Query())->from('customer')->select('id')->orderBy('id');
        // In MySQL limit and offset arguments must both be nonnegative integer constant
        $query
            ->limit(new Expression('2'))
            ->offset(new Expression('1'));

        $columnValues = $query->column($this->getConnection());

        $this->assertCount(2, $columnValues);

        // make sure int => string for strict equals
        foreach ($columnValues as $i => $columnValue) {
            if (is_int($columnValue)) {
                $columnValues[$i] = (string) $columnValue;
            }
        }

        $this->assertNotContains('1', $columnValues);
        $this->assertContains('2', $columnValues);
        $this->assertContains('3', $columnValues);
    }
}
