<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use yii\helpers\Markdown;
use yiiunit\TestCase;

/**
 * Description of MarkdownTest.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @group helpers
 *
 * @internal
 * @coversNothing
 */
class MarkdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // destroy application, Helper must work without Yii::$app
        $this->destroyApplication();
    }

    public function testOriginalFlavor(): void
    {
        $text = <<<'TEXT'
html
new line 1

new line 2
TEXT;

        Markdown::$defaultFlavor = 'original';
        $this->assertSame(Markdown::process($text), Markdown::process($text, 'original'));

        Markdown::$defaultFlavor = 'gfm-comment';
        $this->assertNotSame(Markdown::process($text), Markdown::process($text, 'original'));
        $this->assertSame(Markdown::process($text), Markdown::process($text, 'gfm-comment'));
    }

    public function testProcessInvalidParamException(): void
    {
        $this->expectException('\yii\base\InvalidParamException');
        $this->expectExceptionMessage("Markdown flavor 'undefined' is not defined.");
        Markdown::process('foo', 'undefined');
    }

    public function testProcessParagraph(): void
    {
        $actual = Markdown::processParagraph('foo');
        $expected = 'foo';
        $this->assertSame($expected, $actual);
    }
}
