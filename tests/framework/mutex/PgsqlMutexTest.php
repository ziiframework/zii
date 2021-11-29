<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
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
 *
 * @internal
 * @coversNothing
 */
class PgsqlMutexTest extends DatabaseTestCase
{
    use MutexTestTrait;

    protected $driverName = 'pgsql';

    /**
     * @throws \yii\base\InvalidConfigException
     *
     * @return PgsqlMutex
     */
    protected function createMutex()
    {
        return Yii::createObject([
            'class' => PgsqlMutex::className(),
            'db' => $this->getConnection(),
        ]);
    }
}
