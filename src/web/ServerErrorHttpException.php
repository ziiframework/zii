<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\web;

use Throwable;

/**
 * ServerErrorHttpException represents an "Internal Server Error" HTTP exception with status code 500.
 *
 * @see https://tools.ietf.org/html/rfc7231#section-6.6.1
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class ServerErrorHttpException extends HttpException
{
    /**
     * Constructor.
     *
     * @param string|null $message error message
     * @param int $code error code
     * @param Throwable|null $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = null, $code = 0, $previous = null)
    {
        parent::__construct(500, $message, $code, $previous);
    }
}
