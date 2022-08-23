<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\rbac;

use yii\rbac\DbManager;
use yii\caching\FileCache;

/**
 * PgSQLManagerTest.
 *
 * @group db
 * @group rbac
 * @group pgsql
 */
class PgSQLManagerCacheTest extends DbManagerTestCase
{
    protected static $driverName = 'pgsql';

    /**
     * @return \yii\rbac\ManagerInterface
     */
    protected function createManager()
    {
        return new DbManager([
            'db' => $this->getConnection(),
            'cache' => new FileCache(['cachePath' => '@yiiunit/runtime/cache']),
            'defaultRoles' => ['myDefaultRole'],
        ]);
    }
}
