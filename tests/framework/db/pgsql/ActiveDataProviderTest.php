<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

/**
 * @group db
 * @group pgsql
 * @group data
 *
 * @internal
 * @coversNothing
 */
final class ActiveDataProviderTest extends \yiiunit\framework\data\ActiveDataProviderTest
{
    public $driverName = 'pgsql';
}
