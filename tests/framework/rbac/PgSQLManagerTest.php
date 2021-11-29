<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\rbac;

/**
 * PgSQLManagerTest.
 *
 * @group db
 * @group rbac
 * @group pgsql
 *
 * @internal
 * @coversNothing
 */
final class PgSQLManagerTest extends DbManagerTestCase
{
    protected static $driverName = 'pgsql';
}
