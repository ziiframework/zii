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
 * MySQLManagerCacheTest.
 *
 * @group rbac
 * @group db
 * @group mysql
 */
class MySQLManagerCacheTest extends MySQLManagerTest
{
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
