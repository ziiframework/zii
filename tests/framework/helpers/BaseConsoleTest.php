<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use yiiunit\TestCase;
use yii\helpers\BaseConsole;

/**
 * Unit test for [[yii\helpers\BaseConsole]].
 *
 * @see BaseConsole
 *
 * @group helpers
 */
class BaseConsoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    public function testRenderColoredString(): void
    {
        $data = '%yfoo';
        $actual = BaseConsole::renderColoredString($data);
        $expected = "\033[33mfoo";
        $this->assertEquals($expected, $actual);

        $actual = BaseConsole::renderColoredString($data, false);
        $expected = 'foo';
        $this->assertEquals($expected, $actual);
    }

    public function testAnsiColorizedSubstrWithoutColors(): void
    {
        $str = 'FooBar';

        $actual = BaseConsole::ansiColorizedSubstr($str, 0, 3);
        $expected = BaseConsole::renderColoredString('Foo');
        $this->assertEquals($expected, $actual);

        $actual = BaseConsole::ansiColorizedSubstr($str, 3, 3);
        $expected = BaseConsole::renderColoredString('Bar');
        $this->assertEquals($expected, $actual);

        $actual = BaseConsole::ansiColorizedSubstr($str, 1, 4);
        $expected = BaseConsole::renderColoredString('ooBa');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider ansiColorizedSubstr_withColors_data
     */
    public function testAnsiColorizedSubstrWithColors($str, $start, $length, $expected): void
    {
        $ansiStr = BaseConsole::renderColoredString($str);

        $ansiActual = BaseConsole::ansiColorizedSubstr($ansiStr, $start, $length);
        $ansiExpected = BaseConsole::renderColoredString($expected);
        $this->assertEquals($ansiExpected, $ansiActual);
    }

    public function ansiColorizedSubstr_withColors_data()
    {
        return [
            ['%rFoo%gBar%n', 0, 3, '%rFoo%n'],
            ['%rFoo%gBar%n', 3, 3, '%gBar%n'],
            ['%rFoo%gBar%n', 1, 4, '%roo%gBa%n'],
            ['Foo%yBar%nYes', 1, 7, 'oo%yBar%nYe'],
            ['Foo%yBar%nYes', 5, 3, '%yr%nYe'],
        ];
    }

    public function testAnsiStrlen(): void
    {
        $this->assertSame(3, BaseConsole::ansiStrlen('Foo'));
        $this->assertSame(3, BaseConsole::ansiStrlen(BaseConsole::renderColoredString('Bar%y')));
    }
}
