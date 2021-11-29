<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\rbac;

/**
 * MySQLManagerTest.
 *
 * @group db
 * @group rbac
 * @group mysql
 *
 * @internal
 * @coversNothing
 */
final class MySQLManagerTest extends DbManagerTestCase
{
    protected static $driverName = 'mysql';
}
