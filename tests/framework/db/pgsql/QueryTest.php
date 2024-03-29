<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use yii\db\Query;

/**
 * @group db
 * @group pgsql
 */
class QueryTest extends \yiiunit\framework\db\QueryTest
{
    public $driverName = 'pgsql';

    public function testBooleanValues(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $command->batchInsert('bool_values', ['bool_col'], [
                [true],
                [false],
            ])->execute();

        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = TRUE')->count('*', $db));
        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = FALSE')->count('*', $db));
        $this->assertEquals(2, (new Query())->from('bool_values')->where('bool_col IN (TRUE, FALSE)')->count('*', $db));

        $this->assertEquals(1, (new Query())->from('bool_values')->where(['bool_col' => true])->count('*', $db));
        $this->assertEquals(1, (new Query())->from('bool_values')->where(['bool_col' => false])->count('*', $db));
        $this->assertEquals(2, (new Query())->from('bool_values')->where(['bool_col' => [true, false]])->count('*', $db));

        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => true])->count('*', $db));
        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => false])->count('*', $db));
    }
}
