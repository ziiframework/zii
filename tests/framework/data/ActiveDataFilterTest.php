<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\data;

use yiiunit\TestCase;
use yii\base\DynamicModel;
use yii\data\ActiveDataFilter;

class ActiveDataFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();
    }

    // Tests :

    public function dataProviderBuild()
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    'name' => 'some',
                    'number' => '2',
                ],
                [
                    'AND',
                    ['name' => 'some'],
                    ['number' => '2'],
                ],
            ],
            [
                [
                    'and' => [
                        ['name' => 'some'],
                        ['number' => '2'],
                    ],
                ],
                [
                    'AND',
                    ['name' => 'some'],
                    ['number' => '2'],
                ],
            ],
            [
                [
                    'name' => '  to be trimmed  ',
                ],
                [
                    'name' => 'to be trimmed',
                ],
            ],
            [
                [
                    'number' => [
                        'in' => [1, 5, 8],
                    ],
                ],
                ['IN', 'number', [1, 5, 8]],
            ],
            [
                [
                    'not' => [
                        'number' => 10,
                    ],
                ],
                ['NOT', ['number' => 10]],
            ],
            [
                [
                    'or' => [
                        [
                            'and' => [
                                ['name' => 'some'],
                                ['number' => '2'],
                            ],
                        ],
                        [
                            'or' => [
                                [
                                    'price' => 100,
                                ],
                                [
                                    'price' => [
                                        'gt' => 0,
                                        'lt' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'OR',
                    [
                        'AND',
                        ['name' => 'some'],
                        ['number' => '2'],
                    ],
                    [
                        'OR',
                        [
                            'price' => 100,
                        ],
                        [
                            'AND',
                            ['>', 'price', 0],
                            ['<', 'price', 10],
                        ],
                    ],
                ],
                [
                    [
                        'price' => [
                            'gt' => 0,
                            'lt' => 10,
                        ],
                    ],
                    [
                        'AND',
                        ['>', 'price', 0],
                        ['<', 'price', 10],
                    ],
                ],
            ],
            [
                [
                    'name' => 'NULL',
                    'number' => 'NULL',
                    'price' => 'NULL',
                    'tags' => ['NULL'],
                ],
                [
                    'AND',
                    ['name' => ''],
                    ['number' => null],
                    ['price' => null],
                    ['tags' => [null]],
                ],
            ],
            [
                [
                    'number' => [
                        'neq' => 'NULL',
                    ],
                ],
                ['!=', 'number', null],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderBuild
     *
     * @param array $filter
     * @param array $expectedResult
     */
    public function testBuild($filter, $expectedResult): void
    {
        $builder = new ActiveDataFilter();
        $searchModel = (new DynamicModel(['name' => null, 'number' => null, 'price' => null, 'tags' => null]))
            ->addRule('name', 'trim')
            ->addRule('name', 'string')
            ->addRule('number', 'integer', ['min' => 0, 'max' => 100])
            ->addRule('price', 'number')
            ->addRule('tags', 'each', ['rule' => ['string']]);

        $builder->setSearchModel($searchModel);

        $builder->filter = $filter;
        $this->assertEquals($expectedResult, $builder->build());
    }

    /**
     * @depends testBuild
     */
    public function testBuildCallback(): void
    {
        $builder = new ActiveDataFilter();
        $searchModel = (new DynamicModel(['name' => null]))
            ->addRule('name', 'trim')
            ->addRule('name', 'string');

        $builder->setSearchModel($searchModel);

        $builder->conditionBuilders['OR'] = static fn ($operator, $condition) => ['CALLBACK-OR', $condition];
        $builder->conditionBuilders['LIKE'] = static fn ($operator, $condition, $attribute) => ['CALLBACK-LIKE', $operator, $condition, $attribute];

        $builder->filter = [
            'or' => [
                ['name' => 'some'],
                ['name' => 'another'],
            ],
        ];
        $expectedResult = [
            'CALLBACK-OR',
            [
                ['name' => 'some'],
                ['name' => 'another'],
            ],
        ];
        $this->assertEquals($expectedResult, $builder->build());

        $builder->filter = [
            'name' => [
                'like' => 'foo',
            ],
        ];
        $expectedResult = ['CALLBACK-LIKE', 'LIKE', 'foo', 'name'];
        $this->assertEquals($expectedResult, $builder->build());
    }
}
