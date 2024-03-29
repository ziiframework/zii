<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\filters\stubs;

use yii\log\Logger;

class ExposedLogger extends Logger
{
    public function log($message, $level, $category = 'application'): void
    {
        $this->messages[] = $message;
    }
}
