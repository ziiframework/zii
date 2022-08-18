<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use yiiunit\TestCase;
use yii\db\pgsql\ArrayParser;

class ArrayParserTest extends TestCase
{
    /**
     * @var ArrayParser
     */
    protected $arrayParser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->arrayParser = new ArrayParser();
    }

    public function convertProvider()
    {
        return [
            ['{}', []],
            ['{,}', [null, null]],
            ['{,,}', [null, null, null]],
            ['{1,2,}', ['1', '2', null]],
            ['{{},,1}', [[], null, '1']],
            ['{"{\"key\":\"value\"}",NULL,"NULL","{}"}', ['{"key":"value"}', null, 'NULL', '{}']],
            ['{boo,",",,test}', ['boo', ',', null, 'test']],
            ['{"string1","str\\\\in\\"g2","str,ing3"}', ['string1', 'str\\in"g2', 'str,ing3']],
            ['{{1,2,3},{4,5,6},{7,8,9}}', [['1', '2', '3'], ['4', '5', '6'], ['7', '8', '9']]],
            ['{utf8€,👍}', ['utf8€', '👍']],
            ['{"","","{}",{}}', ['', '', '{}', []]],
        ];
    }

    /**
     * @dataProvider convertProvider
     */
    public function testConvert($string, $expected): void
    {
        $this->assertSame($expected, $this->arrayParser->parse($string));
    }
}
