<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use yii\base\InvalidCallException;
use yii\db\Query;

class UnqueryableQueryMock extends Query
{
    /**
     * {@inheritdoc}
     */
    public function one($db = null): void
    {
        throw new InvalidCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function all($db = null): void
    {
        throw new InvalidCallException();
    }
}
