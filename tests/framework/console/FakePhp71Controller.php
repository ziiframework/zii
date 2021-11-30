<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console;

use yii\console\Controller;
use yii\console\Request;
use yii\data\DataProviderInterface;
use yiiunit\framework\console\stubs\DummyService;

class FakePhp71Controller extends Controller
{
    public function actionInjection($before, Request $request, $between, DummyService $dummyService, Post $post = null, $after)
    {
    }

    public function actionNullableInjection(?Request $request, ?Post $post)
    {
    }

    public function actionModuleServiceInjection(DataProviderInterface $dataProvider)
    {
    }
}
