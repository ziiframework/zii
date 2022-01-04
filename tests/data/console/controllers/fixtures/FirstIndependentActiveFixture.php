<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\console\controllers\fixtures;

use yii\test\ActiveFixture;

class FirstIndependentActiveFixture extends ActiveFixture
{
    public $modelClass = 'yiiunit\data\ar\Profile';

    public function load(): void
    {
        FixtureStorage::$activeFixtureSequence[] = self::className();
        parent::load();
    }
}
