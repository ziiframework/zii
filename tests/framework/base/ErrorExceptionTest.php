<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use yiiunit\TestCase;
use yii\base\ErrorException;

/**
 * @group base
 */
class ErrorExceptionTest extends TestCase
{
    private function isXdebugStackAvailable()
    {
        if (!function_exists('xdebug_get_function_stack')) {
            return false;
        }
        $version = phpversion('xdebug');

        if ($version === false) {
            return false;
        }

        if (version_compare($version, '3.0.0', '<')) {
            return true;
        }

        return str_contains(ini_get('xdebug.mode'), 'develop');
    }

    public function testXdebugTrace(): void
    {
        if (!$this->isXdebugStackAvailable()) {
            $this->markTestSkipped('Xdebug is required.');
        }

        try {
            throw new ErrorException();
        } catch (ErrorException $e) {
            $this->assertEquals(__FUNCTION__, $e->getTrace()[0]['function']);
        }
    }
}
