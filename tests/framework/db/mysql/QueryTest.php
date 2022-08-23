<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use yii\db\Query;
use yii\db\Expression;

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

        $result = $query->column($this->getConnection());

        $this->assertCount(2, $result);

        $this->assertNotContains(1, $result);
        $this->assertStringContainsString(2, $result);
        $this->assertStringContainsString(3, $result);
    }
}
