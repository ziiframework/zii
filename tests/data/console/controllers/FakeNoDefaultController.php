<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\console\controllers;

use yii\console\Controller;

/**
 * @author Dmitry V. Alekseev <mail@alexeevdv.ru>
 *
 * @since 2.0.16
 */
class FakeNoDefaultController extends Controller
{
    public $defaultAction = 'not-exist';

    public function actionIndex(): void
    {
    }
}
