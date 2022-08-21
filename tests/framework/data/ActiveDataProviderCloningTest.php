<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\data;

use yii\db\Query;
use yiiunit\TestCase;
use yii\data\ActiveDataProvider;

class ActiveDataProviderCloningTest extends TestCase
{
    public function testClone(): void
    {
        $queryFirst = new Query();

        $dataProviderFirst = new ActiveDataProvider([
            'query' => $queryFirst,
        ]);

        $dataProviderSecond = clone $dataProviderFirst;

        $querySecond = $dataProviderSecond->query;

        $this->assertNotSame($querySecond, $queryFirst);
    }
}
