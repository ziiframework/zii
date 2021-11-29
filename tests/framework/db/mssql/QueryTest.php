<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use yii\db\Query;

/**
 * @group db
 * @group mssql
 *
 * @internal
 * @coversNothing
 */
class QueryTest extends \yiiunit\framework\db\QueryTest
{
    protected $driverName = 'sqlsrv';

    public function testUnion(): void
    {
        $connection = $this->getConnection();

        // MSSQL supports limit only in sub queries with UNION
        $query = (new Query())
            ->select(['id', 'name'])
            ->from((new Query())
                    ->select(['id', 'name'])
                    ->from('item')
                    ->limit(2))
            ->union((new Query())
                    ->select(['id', 'name'])
                    ->from((new Query())
                            ->select(['id', 'name'])
                            ->from(['category'])
                            ->limit(2)))
        ;

        $result = $query->all($connection);
        $this->assertNotEmpty($result);
        $this->assertCount(4, $result);
    }
}
