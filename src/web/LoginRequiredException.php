<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\web;

class LoginRequiredException extends ForbiddenHttpException
{
    public function getName()
    {
        return 'LoginRequired';
    }
}
