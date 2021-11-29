<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

/**
 * @group db
 * @group sqlite
 *
 * @internal
 * @coversNothing
 */
final class ActiveRecordTest extends \yiiunit\framework\db\ActiveRecordTest
{
    protected $driverName = 'sqlite';
}
