<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\mutex;

use Yii;
use yii\mutex\PgsqlMutex;
use yiiunit\framework\db\DatabaseTestCase;

/**
 * Class PgsqlMutexTest.
 *
 * @group mutex
 * @group db
 * @group pgsql
 */
class PgsqlMutexTest extends DatabaseTestCase
{
    use MutexTestTrait;

    protected $driverName = 'pgsql';

    /**
     * @return PgsqlMutex
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function createMutex()
    {
        return Yii::createObject([
            'class' => PgsqlMutex::className(),
            'db' => $this->getConnection(),
        ]);
    }
}
