<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use yii\helpers\UnsetArrayValue;
use yiiunit\TestCase;

/**
 * @group helpers
 *
 * @internal
 * @coversNothing
 */
final class UnsetArrayValueTest extends TestCase
{
    public function testSetState(): void
    {
        $object = new UnsetArrayValue();
        $result = $object::__set_state([]);
        $this->assertInstanceOf('yii\helpers\UnsetArrayValue', $result);
    }
}
