<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console\controllers;

/**
 * StdOutBufferControllerTrait is a trait, which can be applied to [[yii\console\Controller]],
 * allowing to store all output into internal buffer instead of direct sending it to 'stdout'.
 */
trait StdOutBufferControllerTrait
{
    /**
     * @var string output buffer.
     */
    private $stdOutBuffer = '';

    public function stdout($string): void
    {
        $this->stdOutBuffer .= $string;
    }

    public function flushStdOutBuffer()
    {
        $result = $this->stdOutBuffer;
        $this->stdOutBuffer = '';

        return $result;
    }
}
