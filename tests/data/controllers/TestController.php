<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\controllers;

use yii\web\Controller;
use yii\web\ErrorAction;

class TestController extends Controller
{
    public $layout = '@yiiunit/data/views/layout.php';

    private $actionConfig = [];

    public function setActionConfig($config = []): void
    {
        $this->actionConfig = $config;
    }

    public function actions()
    {
        return [
            'error' => array_merge([
                'class' => ErrorAction::className(),
                'view' => '@yiiunit/data/views/error.php',
            ], $this->actionConfig),
        ];
    }
}
