<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
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

        print "<body>\n";

        Spaceless::begin();
        print "\t<div class='wrapper'>\n";

        Spaceless::begin();
        print "\t\t<div class='left-column'>\n";
        print "\t\t\t<p>This is a left bar!</p>\n";
        print "\t\t</div>\n\n";
        print "\t\t<div class='right-column'>\n";
        print "\t\t\t<p>This is a right bar!</p>\n";
        print "\t\t</div>\n";
        Spaceless::end();

        print "\t</div>\n";
        Spaceless::end();

        print "\t<p>Bye!</p>\n";
        print "</body>\n";

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
        $spaceless     = Spaceless::begin(
            [
                'on init' => static function () use (&$initTriggered): void
                {
                    $initTriggered = true;
                },
            ]
        );
        Spaceless::end();
        $this->assertTrue($initTriggered);
    }
}
