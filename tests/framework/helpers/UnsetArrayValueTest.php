<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use yiiunit\TestCase;
use yii\helpers\UnsetArrayValue;

/**
 * @group helpers
 */
class UnsetArrayValueTest extends TestCase
{
    public function testSetState(): void
    {
        $object = new UnsetArrayValue();
        $result = $object::__set_state([]);
        $this->assertInstanceOf('yii\helpers\UnsetArrayValue', $result);
    }
}
