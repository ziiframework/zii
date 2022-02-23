<?php

namespace yii\web;

class LoginRequiredException extends ForbiddenHttpException
{
    public function getName()
    {
        return 'LoginRequired';
    }
}
