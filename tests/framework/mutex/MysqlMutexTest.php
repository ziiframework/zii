<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\mutex;

use Yii;
use yii\mutex\MysqlMutex;
use yiiunit\framework\db\DatabaseTestCase;

/**
 * Class MysqlMutexTest.
 *
 * @group mutex
 * @group db
 * @group mysql
 *
 * @internal
 * @coversNothing
 */
class MysqlMutexTest extends DatabaseTestCase
{
    use MutexTestTrait;

    protected $driverName = 'mysql';

    /**
     * @throws \yii\base\InvalidConfigException
     *
     * @return MysqlMutex
     */
    protected function createMutex()
    {
        return Yii::createObject([
            'class' => MysqlMutex::className(),
            'db' => $this->getConnection(),
        ]);
    }
}
