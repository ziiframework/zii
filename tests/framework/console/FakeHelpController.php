<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console;

use yii\console\controllers\HelpController;

class FakeHelpController extends HelpController
{
    private static $_actionIndexLastCallParams;

    public function actionIndex($command = null): void
    {
        self::$_actionIndexLastCallParams = func_get_args();
    }

    public static function getActionIndexLastCallParams()
    {
        $params = self::$_actionIndexLastCallParams;
        self::$_actionIndexLastCallParams = null;

        return $params;
    }
}
