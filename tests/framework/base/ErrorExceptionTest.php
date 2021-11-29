<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use function function_exists;
use yii\base\ErrorException;
use yiiunit\TestCase;

/**
 * @group base
 *
 * @internal
 * @coversNothing
 */
final class ErrorExceptionTest extends TestCase
{
    public function testXdebugTrace(): void
    {
        if (!$this->isXdebugStackAvailable()) {
            $this->markTestSkipped('Xdebug is required.');
        }

        try {
            throw new ErrorException();
        } catch (ErrorException $e) {
            $this->assertSame(__FUNCTION__, $e->getTrace()[0]['function']);
        }
    }

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

        return false !== strpos(ini_get('xdebug.mode'), 'develop');
    }
}
