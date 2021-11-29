<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\mutex\mocks;

use function microtime;
use yii\mutex\Mutex;
use yii\mutex\RetryAcquireTrait;

/**
 * Class DumbMutex.
 *
 * @author Robert Korulczyk <robert@korulczyk.pl>
 */
class DumbMutex extends Mutex
{
    use RetryAcquireTrait;

    public $attemptsTime = [];
    public static $locked = false;

    /**
     * {@inheritdoc}
     */
    protected function acquireLock($name, $timeout = 0)
    {
        return $this->retryAcquire($timeout, function () {
            $this->attemptsTime[] = microtime(true);

            if (!static::$locked) {
                static::$locked = true;

                return true;
            }

            return false;
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function releaseLock($name)
    {
        if (static::$locked) {
            static::$locked = false;

            return true;
        }

        return false;
    }
}
