<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console;

use yii\console\Controller;
use yii\console\Request;
use yii\data\DataProviderInterface;
use yiiunit\framework\console\stubs\DummyService;

class FakePhp71Controller extends Controller
{
    public function actionInjection($before, Request $request, $between, DummyService $dummyService, Post $post = null, $after): void
    {
    }

    public function actionNullableInjection(?Request $request, ?Post $post): void
    {
    }

    public function actionModuleServiceInjection(DataProviderInterface $dataProvider): void
    {
    }
}
