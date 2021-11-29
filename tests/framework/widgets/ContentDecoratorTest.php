<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\widgets;

use yii\widgets\ContentDecorator;

/**
 * @group widgets
 *
 * @internal
 * @coversNothing
 */
final class ContentDecoratorTest extends \yiiunit\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebApplication();
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15536
     */
    public function testShouldTriggerInitEvent(): void
    {
        $initTriggered = false;

        $contentDecorator = new ContentDecorator([
            'viewFile' => '@app/views/layouts/base.php',
            'on init' => static function () use (&$initTriggered): void {
                $initTriggered = true;
            },
        ]);

        ob_get_clean();

        $this->assertTrue($initTriggered);
    }
}
