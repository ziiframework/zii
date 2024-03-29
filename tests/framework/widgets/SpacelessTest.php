<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\widgets;

use yii\widgets\Spaceless;

/**
 * @group widgets
 */
class SpacelessTest extends \yiiunit\TestCase
{
    public function testWidget(): void
    {
        ob_start();
        ob_implicit_flush(false);

        echo "<body>\n";

        Spaceless::begin();
        echo "\t<div class='wrapper'>\n";

        Spaceless::begin();
        echo "\t\t<div class='left-column'>\n";
        echo "\t\t\t<p>This is a left bar!</p>\n";
        echo "\t\t</div>\n\n";
        echo "\t\t<div class='right-column'>\n";
        echo "\t\t\t<p>This is a right bar!</p>\n";
        echo "\t\t</div>\n";
        Spaceless::end();

        echo "\t</div>\n";
        Spaceless::end();

        echo "\t<p>Bye!</p>\n";
        echo "</body>\n";

        $expected = "<body>\n<div class='wrapper'><div class='left-column'><p>This is a left bar!</p>" .
            "</div><div class='right-column'><p>This is a right bar!</p></div></div>\t<p>Bye!</p>\n</body>\n";
        $this->assertEquals($expected, ob_get_clean());
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15536
     */
    public function testShouldTriggerInitEvent(): void
    {
        $initTriggered = false;
        $spaceless = Spaceless::begin([
                'on init' => static function () use (&$initTriggered): void {
                    $initTriggered = true;
                },
            ]);
        Spaceless::end();
        $this->assertTrue($initTriggered);
    }
}
