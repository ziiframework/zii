<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use yii\base\Object;

class BCObject extends \yii\base\Object
{
    public static $initCalled = false;

    public function __construct($config = [])
    {
        Object::__construct($config);
    }

    public function init(): void
    {
        static::$initCalled = true;
    }
}
