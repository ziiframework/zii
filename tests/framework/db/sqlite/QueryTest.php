<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use yii\db\Query;

/**
 * @group db
 * @group sqlite
 */
class QueryTest extends \yiiunit\framework\db\QueryTest
{
    protected $driverName = 'sqlite';

    public function testUnion(): void
    {
        $connection = $this->getConnection();
        $query = new Query();
        $query->select(['id', 'name'])
            ->from('item')
            ->union((new Query())
                    ->select(['id', 'name'])
                    ->from(['category']));
        $result = $query->all($connection);
        $this->assertNotEmpty($result);
        $this->assertCount(7, $result);
    }
}
