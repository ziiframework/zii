<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web\stubs;

use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

class ModelBindingStub extends ActiveRecord
{
    /**
     * @return self;
     * @throw NotFoundHttpException
     */
    public static function build()
    {
        throw new NotFoundHttpException('Not Found Item.');
    }
}
