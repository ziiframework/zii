<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\db;

use yii\base\InvalidCallException;
use yii\db\Query;

class UnqueryableQueryMock extends Query
{
    /**
     * @inheritDoc
     */
    public function one($db = null): void
    {
        throw new InvalidCallException();
    }

    /**
     * @inheritDoc
     */
    public function all($db = null): void
    {
        throw new InvalidCallException();
    }
}
