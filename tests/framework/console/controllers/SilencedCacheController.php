<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\console\controllers;

use yii\console\controllers\CacheController;

/**
 * CacheController that discards output.
 */
class SilencedCacheController extends CacheController
{
    /**
     * @inheritDoc
     */
    public function stdout($string): void
    {
        // do nothing
    }
}
