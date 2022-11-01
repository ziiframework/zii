<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use yiiunit\TestCase;
use yiiunit\data\base\Singer;
use yiiunit\data\base\Speaker;

class StaticInstanceTraitTest extends TestCase
{
    public function testInstance(): void
    {
        $speakerModel = Speaker::instance();
        $this->assertInstanceOf(Speaker::class, $speakerModel);

        $singerModel = Singer::instance();
        $this->assertInstanceOf(Singer::class, $singerModel);

        $this->assertSame($speakerModel, Speaker::instance());
        $this->assertNotSame($speakerModel, Speaker::instance(true));
    }
}
