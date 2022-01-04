<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\modules\magic\controllers;

class ETagController extends \yii\console\Controller
{
    public function actionListETags()
    {
        return '';
    }

    public function actionDelete()
    {
        return 'deleted';
    }
}
