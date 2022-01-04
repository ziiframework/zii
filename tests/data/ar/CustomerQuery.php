<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\ar;

use yii\db\ActiveQuery;

/**
 * CustomerQuery.
 */
class CustomerQuery extends ActiveQuery
{
    public static $joinWithProfile = false;

    public function init(): void
    {
        if (static::$joinWithProfile) {
            $this->innerJoinWith('profile');
        }
        parent::init();
    }

    public function active()
    {
        $this->andWhere('[[status]]=1');

        return $this;
    }
}
