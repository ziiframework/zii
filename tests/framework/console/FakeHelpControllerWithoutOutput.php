<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console;

use yii\console\controllers\HelpController;

class FakeHelpControllerWithoutOutput extends HelpController
{
    public $outputString = '';

    public function stdout($string)
    {
        return $this->outputString .= $string;
    }
}
