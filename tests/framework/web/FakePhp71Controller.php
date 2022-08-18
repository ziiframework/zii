<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use yii\web\Request;
use yii\web\Controller;
use yii\data\DataProviderInterface;
use yiiunit\framework\web\stubs\VendorImage;
use yiiunit\framework\web\stubs\ModelBindingStub;

/**
 * @author Sam Mousa<sam@mousa.nl>
 *
 * @since 2.0.36
 */
class FakePhp71Controller extends Controller
{
    public $enableCsrfValidation = false;

    public function actionInjection($before, Request $request, $between, VendorImage $vendorImage, Post $post = null, $after): void
    {
    }

    public function actionNullableInjection(?Request $request, ?Post $post): void
    {
    }

    public function actionModuleServiceInjection(DataProviderInterface $dataProvider): void
    {
    }

    public function actionModelBindingInjection(ModelBindingStub $model): void
    {
    }
}
