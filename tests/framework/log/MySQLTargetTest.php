<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\log;

/**
 * @group db
 * @group mysql
 * @group log
 *
 * @internal
 * @coversNothing
 */
final class MySQLTargetTest extends DbTargetTest
{
    protected static $driverName = 'mysql';
}
