<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use yiiunit\data\base\Singer;
use yiiunit\data\base\Speaker;
use yiiunit\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class StaticInstanceTraitTest extends TestCase
{
    public function testInstance(): void
    {
        $speakerModel = Speaker::instance();
        $this->assertTrue($speakerModel instanceof Speaker);

        $singerModel = Singer::instance();
        $this->assertTrue($singerModel instanceof Singer);

        $this->assertSame($speakerModel, Speaker::instance());
        $this->assertNotSame($speakerModel, Speaker::instance(true));
    }
}
