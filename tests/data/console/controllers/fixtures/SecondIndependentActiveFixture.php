<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\data\console\controllers\fixtures;

use yii\test\ActiveFixture;

class SecondIndependentActiveFixture extends ActiveFixture
{
    public $modelClass = 'yiiunit\data\ar\Animal';

    public function load(): void
    {
        FixtureStorage::$activeFixtureSequence[] = self::className();
        parent::load();
    }
}
