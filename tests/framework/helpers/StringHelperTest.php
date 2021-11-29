<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use yii\helpers\StringHelper;
use yiiunit\TestCase;

/**
 * StringHelperTest.
 *
 * @group helpers
 *
 * @internal
 * @coversNothing
 */
final class StringHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // destroy application, Helper must work without Yii::$app
        $this->destroyApplication();
    }

    public function testStrlen(): void
    {
        $this->assertSame(4, StringHelper::byteLength('this'));
        $this->assertSame(6, StringHelper::byteLength('это'));
    }

    public function testSubstr(): void
    {
        $this->assertSame('th', StringHelper::byteSubstr('this', 0, 2));
        $this->assertSame('э', StringHelper::byteSubstr('это', 0, 2));

        $this->assertSame('abcdef', StringHelper::byteSubstr('abcdef', 0));
        $this->assertSame('abcdef', StringHelper::byteSubstr('abcdef', 0, null));

        $this->assertSame('de', StringHelper::byteSubstr('abcdef', 3, 2));
        $this->assertSame('def', StringHelper::byteSubstr('abcdef', 3));
        $this->assertSame('def', StringHelper::byteSubstr('abcdef', 3, null));

        $this->assertSame('cd', StringHelper::byteSubstr('abcdef', -4, 2));
        $this->assertSame('cdef', StringHelper::byteSubstr('abcdef', -4));
        $this->assertSame('cdef', StringHelper::byteSubstr('abcdef', -4, null));

        $this->assertSame('', StringHelper::byteSubstr('abcdef', 4, 0));
        $this->assertSame('', StringHelper::byteSubstr('abcdef', -4, 0));

        $this->assertSame('это', StringHelper::byteSubstr('это', 0));
        $this->assertSame('это', StringHelper::byteSubstr('это', 0, null));

        $this->assertSame('т', StringHelper::byteSubstr('это', 2, 2));
        $this->assertSame('то', StringHelper::byteSubstr('это', 2));
        $this->assertSame('то', StringHelper::byteSubstr('это', 2, null));

        $this->assertSame('т', StringHelper::byteSubstr('это', -4, 2));
        $this->assertSame('то', StringHelper::byteSubstr('это', -4));
        $this->assertSame('то', StringHelper::byteSubstr('это', -4, null));

        $this->assertSame('', StringHelper::byteSubstr('это', 4, 0));
        $this->assertSame('', StringHelper::byteSubstr('это', -4, 0));
    }

    public function testBasename(): void
    {
        $this->assertSame('', StringHelper::basename(''));

        $this->assertSame('file', StringHelper::basename('file'));
        $this->assertSame('file.test', StringHelper::basename('file.test', '.test2'));
        $this->assertSame('file', StringHelper::basename('file.test', '.test'));

        $this->assertSame('file', StringHelper::basename('/file'));
        $this->assertSame('file.test', StringHelper::basename('/file.test', '.test2'));
        $this->assertSame('file', StringHelper::basename('/file.test', '.test'));

        $this->assertSame('file', StringHelper::basename('/path/to/file'));
        $this->assertSame('file.test', StringHelper::basename('/path/to/file.test', '.test2'));
        $this->assertSame('file', StringHelper::basename('/path/to/file.test', '.test'));

        $this->assertSame('file', StringHelper::basename('\file'));
        $this->assertSame('file.test', StringHelper::basename('\file.test', '.test2'));
        $this->assertSame('file', StringHelper::basename('\file.test', '.test'));

        $this->assertSame('file', StringHelper::basename('C:\file'));
        $this->assertSame('file.test', StringHelper::basename('C:\file.test', '.test2'));
        $this->assertSame('file', StringHelper::basename('C:\file.test', '.test'));

        $this->assertSame('file', StringHelper::basename('C:\path\to\file'));
        $this->assertSame('file.test', StringHelper::basename('C:\path\to\file.test', '.test2'));
        $this->assertSame('file', StringHelper::basename('C:\path\to\file.test', '.test'));

        // mixed paths
        $this->assertSame('file.test', StringHelper::basename('/path\to/file.test'));
        $this->assertSame('file.test', StringHelper::basename('/path/to\file.test'));
        $this->assertSame('file.test', StringHelper::basename('\path/to\file.test'));

        // \ and / in suffix
        $this->assertSame('file', StringHelper::basename('/path/to/filete/st', 'te/st'));
        $this->assertSame('st', StringHelper::basename('/path/to/filete/st', 'te\st'));
        $this->assertSame('file', StringHelper::basename('/path/to/filete\st', 'te\st'));
        $this->assertSame('st', StringHelper::basename('/path/to/filete\st', 'te/st'));

        // https://www.php.net/manual/en/function.basename.php#72254
        $this->assertSame('foo', StringHelper::basename('/bar/foo/'));
        $this->assertSame('foo', StringHelper::basename('\\bar\\foo\\'));
    }

    public function testTruncate(): void
    {
        $this->assertSame('привет, я multibyte...', StringHelper::truncate('привет, я multibyte строка!', 20));
        $this->assertSame('Не трогаем строку', StringHelper::truncate('Не трогаем строку', 20));
        $this->assertSame('исполь!!!', StringHelper::truncate('используем восклицательные знаки', 6, '!!!'));

        // With Html
        $this->assertSame('<span>This is a test</span>...', StringHelper::truncate('<span>This is a test sentance</span>', 14, '...', null, true));
        $this->assertSame('<span>This is a test</span>...', StringHelper::truncate('<span>This is a test </span>sentance', 14, '...', null, true));
        $this->assertSame('<span>This is a test </span><strong>for</strong>...', StringHelper::truncate('<span>This is a test </span><strong>for a sentance</strong>', 18, '...', null, true));
        $this->assertSame('<span>This is a test</span><strong> for</strong>...', StringHelper::truncate('<span>This is a test</span><strong> for a sentance</strong>', 18, '...', null, true));

        $this->assertSame('<span><img src="image.png" />This is a test</span>...', StringHelper::truncate('<span><img src="image.png" />This is a test sentance</span>', 14, '...', null, true));
        $this->assertSame('<span><img src="image.png" />This is a test</span>...', StringHelper::truncate('<span><img src="image.png" />This is a test </span>sentance', 14, '...', null, true));
        $this->assertSame('<span><img src="image.png" />This is a test </span><strong>for</strong>...', StringHelper::truncate('<span><img src="image.png" />This is a test </span><strong>for a sentance</strong>', 18, '...', null, true));

        $this->assertSame('<p>This is a test</p><ul><li>bullet1</li><li>b</li></ul>...', StringHelper::truncate('<p>This is a test</p><ul><li>bullet1</li><li>bullet2</li><li>bullet3</li><li>bullet4</li></ul>', 22, '...', null, true));

        $this->assertSame('<div><ul><li>bullet1</li><li><div>b</div></li></ul></div>...', StringHelper::truncate('<div><ul><li>bullet1</li><li><div>bullet2</div></li></ul><br></div>', 8, '...', null, true));
    }

    public function testTruncateWords(): void
    {
        $this->assertSame('это тестовая multibyte строка', StringHelper::truncateWords('это тестовая multibyte строка', 5));
        $this->assertSame('это тестовая multibyte...', StringHelper::truncateWords('это тестовая multibyte строка', 3));
        $this->assertSame('это тестовая multibyte!!!', StringHelper::truncateWords('это тестовая multibyte строка', 3, '!!!'));
        $this->assertSame('это строка с          неожиданными...', StringHelper::truncateWords('это строка с          неожиданными пробелами', 4));

        $this->assertSame('lorem ipsum', StringHelper::truncateWords('lorem ipsum', 3, '...', true));
        $this->assertSame(' lorem ipsum', StringHelper::truncateWords(' lorem ipsum', 3, '...', true));
        // With Html
        $this->assertSame('<span>This is a test</span>...', StringHelper::truncateWords('<span>This is a test sentance</span>', 4, '...', true));
        $this->assertSame('<span>This is a test </span><strong>for</strong>...', StringHelper::truncateWords('<span>This is a test </span><strong>for a sentance</strong>', 5, '...', true));
        $this->assertSame('<span>This is a test</span><strong> for</strong>...', StringHelper::truncateWords('<span>This is a test</span><strong> for a sentance</strong>', 5, '...', true));
        $this->assertSame('<p> раз два три четыре пять </p> <p> шесть</p>...', StringHelper::truncateWords('<p> раз два три четыре пять </p> <p> шесть семь восемь девять десять</p>', 6, '...', true));

        $this->assertSame('<span><img src="image.png" />This is a test</span>...', StringHelper::truncateWords('<span><img src="image.png" />This is a test sentance</span>', 4, '...', true));
        $this->assertSame('<span><img src="image.png" />This is a test </span><strong>for</strong>...', StringHelper::truncateWords('<span><img src="image.png" />This is a test </span><strong>for a sentance</strong>', 5, '...', true));
        $this->assertSame('<span><img src="image.png" />This is a test</span><strong> for</strong>...', StringHelper::truncateWords('<span><img src="image.png" />This is a test</span><strong> for a sentance</strong>', 5, '...', true));
    }

    /**
     * @dataProvider providerStartsWith
     *
     * @param bool        $result
     * @param string      $string
     * @param null|string $with
     */
    public function testStartsWith($result, $string, $with): void
    {
        // case sensitive version check
        $this->assertSame($result, StringHelper::startsWith($string, $with));
        // case insensitive version check
        $this->assertSame($result, StringHelper::startsWith($string, $with, false));
    }

    /**
     * Rules that should work the same for case-sensitive and case-insensitive `startsWith()`.
     */
    public function providerStartsWith()
    {
        return [
            // positive check
            [true, '', ''],
            [true, '', null],
            [true, 'string', ''],
            [true, ' string', ' '],
            [true, 'abc', 'abc'],
            [true, 'Bürger', 'Bürger'],
            [true, '我Я multibyte', '我Я'],
            [true, 'Qנטשופ צרכנות', 'Qנ'],
            [true, 'ไทย.idn.icann.org', 'ไ'],
            [true, '!?+', "\x21\x3F"],
            [true, "\x21?+", '!?'],
            // false-positive check
            [false, '', ' '],
            [false, ' ', '  '],
            [false, 'Abc', 'Abcde'],
            [false, 'abc', 'abe'],
            [false, 'abc', 'b'],
            [false, 'abc', 'c'],
        ];
    }

    public function testStartsWithCaseSensitive(): void
    {
        $this->assertFalse(StringHelper::startsWith('Abc', 'a'));
        $this->assertFalse(StringHelper::startsWith('üЯ multibyte', 'Üя multibyte'));
    }

    public function testStartsWithCaseInsensitive(): void
    {
        $this->assertTrue(StringHelper::startsWith('sTrInG', 'StRiNg', false));
        $this->assertTrue(StringHelper::startsWith('CaSe', 'cAs', false));
        $this->assertTrue(StringHelper::startsWith('HTTP://BÜrger.DE/', 'http://bürger.de', false));
        $this->assertTrue(StringHelper::startsWith('üЯйΨB', 'ÜяЙΨ', false));
    }

    /**
     * @dataProvider providerEndsWith
     *
     * @param bool        $result
     * @param string      $string
     * @param null|string $with
     */
    public function testEndsWith($result, $string, $with): void
    {
        // case sensitive version check
        $this->assertSame($result, StringHelper::endsWith($string, $with));
        // case insensitive version check
        $this->assertSame($result, StringHelper::endsWith($string, $with, false));
    }

    /**
     * Rules that should work the same for case-sensitive and case-insensitive `endsWith()`.
     */
    public function providerEndsWith()
    {
        return [
            // positive check
            [true, '', ''],
            [true, '', null],
            [true, 'string', ''],
            [true, 'string ', ' '],
            [true, 'string', 'g'],
            [true, 'abc', 'abc'],
            [true, 'Bürger', 'Bürger'],
            [true, 'Я multibyte строка我!', ' строка我!'],
            [true, '+!?', "\x21\x3F"],
            [true, "+\x21?", "!\x3F"],
            [true, 'נטשופ צרכנות', 'ת'],
            // false-positive check
            [false, '', ' '],
            [false, ' ', '  '],
            [false, 'aaa', 'aaaa'],
            [false, 'abc', 'abe'],
            [false, 'abc', 'a'],
            [false, 'abc', 'b'],
        ];
    }

    public function testEndsWithCaseSensitive(): void
    {
        $this->assertFalse(StringHelper::endsWith('string', 'G'));
        $this->assertFalse(StringHelper::endsWith('multibyte строка', 'А'));
    }

    public function testEndsWithCaseInsensitive(): void
    {
        $this->assertTrue(StringHelper::endsWith('sTrInG', 'StRiNg', false));
        $this->assertTrue(StringHelper::endsWith('string', 'nG', false));
        $this->assertTrue(StringHelper::endsWith('BüЯйΨ', 'ÜяЙΨ', false));
    }

    public function testExplode(): void
    {
        $this->assertSame(['It', 'is', 'a first', 'test'], StringHelper::explode('It, is, a first, test'));
        $this->assertSame(['It', 'is', 'a test with trimmed digits', '0', '1', '2'], StringHelper::explode('It, is, a test with trimmed digits, 0, 1, 2', ',', true, true));
        $this->assertSame(['It', 'is', 'a second', 'test'], StringHelper::explode('It+ is+ a second+ test', '+'));
        $this->assertSame(['Save', '', '', 'empty trimmed string'], StringHelper::explode('Save, ,, empty trimmed string', ','));
        $this->assertSame(['44', '512'], StringHelper::explode('0 0 440 512', ' ', '0', true));
        $this->assertSame(['Здесь', 'multibyte', 'строка'], StringHelper::explode('Здесь我 multibyte我 строка', '我'));
        $this->assertSame(['Disable', '  trim  ', 'here but ignore empty'], StringHelper::explode('Disable,  trim  ,,,here but ignore empty', ',', false, true));
        $this->assertSame(['It/', ' is?', ' a', ' test with rtrim'], StringHelper::explode('It/, is?, a , test with rtrim', ',', 'rtrim'));
        $this->assertSame(['It', ' is', ' a ', ' test with closure'], StringHelper::explode('It/, is?, a , test with closure', ',', static fn ($value) => trim($value, '/?')));
    }

    public function testWordCount(): void
    {
        $this->assertSame(3, StringHelper::countWords('china 中国 ㄍㄐㄋㄎㄌ'));
        $this->assertSame(4, StringHelper::countWords('и много тут слов?'));
        $this->assertSame(4, StringHelper::countWords("и\rмного\r\nтут\nслов?"));
        $this->assertSame(1, StringHelper::countWords('крем-брюле'));
        $this->assertSame(1, StringHelper::countWords(' слово '));
    }

    /**
     * @dataProvider base64UrlEncodedStringsProvider
     *
     * @param $input
     * @param $base64UrlEncoded
     */
    public function testBase64UrlEncode($input, $base64UrlEncoded): void
    {
        $encoded = StringHelper::base64UrlEncode($input);
        $this->assertSame($base64UrlEncoded, $encoded);
    }

    /**
     * @dataProvider base64UrlEncodedStringsProvider
     *
     * @param $output
     * @param $base64UrlEncoded
     */
    public function testBase64UrlDecode($output, $base64UrlEncoded): void
    {
        $decoded = StringHelper::base64UrlDecode($base64UrlEncoded);
        $this->assertSame($output, $decoded);
    }

    public function base64UrlEncodedStringsProvider()
    {
        return [
            ['This is an encoded string', 'VGhpcyBpcyBhbiBlbmNvZGVkIHN0cmluZw=='],
            ['subjects?_d=1', 'c3ViamVjdHM_X2Q9MQ=='],
            ['subjects>_d=1', 'c3ViamVjdHM-X2Q9MQ=='],
            ['Это закодированная строка', '0K3RgtC-INC30LDQutC-0LTQuNGA0L7QstCw0L3QvdCw0Y8g0YHRgtGA0L7QutCw'],
        ];
    }

    /**
     * Data provider for [[testMatchWildcard()]].
     *
     * @return array test data
     */
    public function dataProviderMatchWildcard()
    {
        return [
            // *
            ['*', 'any', true],
            ['*', '', true],
            ['begin*end', 'begin-middle-end', true],
            ['begin*end', 'beginend', true],
            ['begin*end', 'begin-d', false],
            ['*end', 'beginend', true],
            ['*end', 'begin', false],
            ['begin*', 'begin-end', true],
            ['begin*', 'end', false],
            ['begin*', 'before-begin', false],
            // ?
            ['begin?end', 'begin1end', true],
            ['begin?end', 'beginend', false],
            ['begin??end', 'begin12end', true],
            ['begin??end', 'begin1end', false],
            // []
            ['gr[ae]y', 'gray', true],
            ['gr[ae]y', 'grey', true],
            ['gr[ae]y', 'groy', false],
            ['a[2-8]', 'a1', false],
            ['a[2-8]', 'a3', true],
            ['[][!]', ']', true],
            ['[-1]', '-', true],
            // [!]
            ['gr[!ae]y', 'gray', false],
            ['gr[!ae]y', 'grey', false],
            ['gr[!ae]y', 'groy', true],
            ['a[!2-8]', 'a1', true],
            ['a[!2-8]', 'a3', false],
            // -
            ['a-z', 'a-z', true],
            ['a-z', 'a-c', false],
            // slashes
            ['begin/*/end', 'begin/middle/end', true],
            ['begin/*/end', 'begin/two/steps/end', true],
            ['begin/*/end', 'begin/end', false],
            ['begin\\\\*\\\\end', 'begin\middle\end', true],
            ['begin\\\\*\\\\end', 'begin\two\steps\end', true],
            ['begin\\\\*\\\\end', 'begin\end', false],
            // dots
            ['begin.*.end', 'begin.middle.end', true],
            ['begin.*.end', 'begin.two.steps.end', true],
            ['begin.*.end', 'begin.end', false],
            // case
            ['begin*end', 'BEGIN-middle-END', false],
            ['begin*end', 'BEGIN-middle-END', true, ['caseSensitive' => false]],
            // file path
            ['begin/*/end', 'begin/middle/end', true, ['filePath' => true]],
            ['begin/*/end', 'begin/two/steps/end', false, ['filePath' => true]],
            ['begin\\\\*\\\\end', 'begin\middle\end', true, ['filePath' => true]],
            ['begin\\\\*\\\\end', 'begin\two\steps\end', false, ['filePath' => true]],
            ['*', 'any', true, ['filePath' => true]],
            ['*', 'any/path', false, ['filePath' => true]],
            ['[.-0]', 'any/path', false, ['filePath' => true]],
            ['*', '.dotenv', true, ['filePath' => true]],
            // escaping
            ['\*\?', '*?', true],
            ['\*\?', 'zz', false],
            ['begin\*\end', 'begin\middle\end', true, ['escape' => false]],
            ['begin\*\end', 'begin\two\steps\end', true, ['escape' => false]],
            ['begin\*\end', 'begin\end', false, ['escape' => false]],
            ['begin\*\end', 'begin\middle\end', true, ['filePath' => true, 'escape' => false]],
            ['begin\*\end', 'begin\two\steps\end', false, ['filePath' => true, 'escape' => false]],
        ];
    }

    /**
     * @dataProvider dataProviderMatchWildcard
     *
     * @param string $pattern
     * @param string $string
     * @param bool   $expectedResult
     * @param array  $options
     */
    public function testMatchWildcard($pattern, $string, $expectedResult, $options = []): void
    {
        $this->assertSame($expectedResult, StringHelper::matchWildcard($pattern, $string, $options));
    }

    public function dataProviderMb_ucfirst()
    {
        return [
            ['foo', 'Foo'],
            ['foo bar', 'Foo bar'],
            ['👍🏻 foo bar', '👍🏻 foo bar'],
            ['', ''],
            [null, ''],
            ['здесь我 multibyte我 строка', 'Здесь我 multibyte我 строка'],
        ];
    }

    /**
     * @param string $string
     * @param string $expectedResult
     * @dataProvider dataProviderMb_ucfirst
     */
    public function testMbUcfirst($string, $expectedResult): void
    {
        $this->assertSame($expectedResult, StringHelper::mb_ucfirst($string));
    }

    public function dataProviderMb_ucwords()
    {
        return [
            ['foo', 'Foo'],
            ['foo bar', 'Foo Bar'],
            ['!foo bar-baz', '!foo Bar-baz'],
            [' foo BAR', ' Foo BAR'],
            [' ! foo BAR', ' ! Foo BAR'],
            ["\tfoo\nbar  baz", "\tFoo\nBar  Baz"],
            ['👍🏻 foo bar', '👍🏻 Foo Bar'],
            ['', ''],
            ['0', '0'],
            [null, ''],
            ['здесь我 multibyte我 строка', 'Здесь我 Multibyte我 Строка'],
            ['p0 upload', 'P0 Upload'],
            ['p5 upload', 'P5 Upload'],
        ];
    }

    /**
     * @param string $string
     * @param string $expectedResult
     * @dataProvider dataProviderMb_ucwords
     */
    public function testMbUcwords($string, $expectedResult): void
    {
        $this->assertSame($expectedResult, StringHelper::mb_ucwords($string));
    }

    /**
     * @param string $string
     * @param string $expectedResult
     * @dataProvider dataProviderDirname
     */
    public function testDirname($string, $expectedResult): void
    {
        $this->assertSame($expectedResult, StringHelper::dirname($string));
    }

    public function dataProviderDirname()
    {
        return [
            ['\\foo\\bar\\test', '\foo\bar'],
            ['\\foo/bar\\test', '\foo/bar'],
            ['\\foo\\bar\\test\\', '\foo\bar'],
            ['foo/bar/test', 'foo/bar'],
            ['foo', ''],
            ['', ''],
        ];
    }
}
