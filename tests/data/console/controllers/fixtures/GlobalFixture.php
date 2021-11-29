<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\data\console\controllers\fixtures;

use yii\test\Fixture;

class GlobalFixture extends Fixture
{
    public function load(): void
    {
        FixtureStorage::$globalFixturesData[] = 'some data set for global fixture';
    }

    public function unload(): void
    {
        FixtureStorage::$globalFixturesData = [];
    }
}
