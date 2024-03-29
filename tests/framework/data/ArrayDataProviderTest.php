<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\data;

use yiiunit\TestCase;
use yii\data\ArrayDataProvider;

/**
 * @group data
 */
class ArrayDataProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    public function testGetModels(): void
    {
        $simpleArray = [
            ['name' => 'zero'],
            ['name' => 'one'],
        ];
        $dataProvider = new ArrayDataProvider(['allModels' => $simpleArray]);
        $this->assertEquals($simpleArray, $dataProvider->getModels());
    }

    public function testGetSortedData(): void
    {
        $simpleArray = [['sortField' => 1], ['sortField' => 0]];
        $dataProvider = new ArrayDataProvider([
                'allModels' => $simpleArray,
                'sort' => [
                    'attributes' => [
                        'sort' => [
                            'asc' => ['sortField' => SORT_ASC],
                            'desc' => ['sortField' => SORT_DESC],
                            'label' => 'Sorting',
                            'default' => 'asc',
                        ],
                    ],
                    'defaultOrder' => [
                        'sort' => SORT_ASC,
                    ],
                ],
            ]);
        $sortedArray = [['sortField' => 0], ['sortField' => 1]];
        $this->assertEquals($sortedArray, $dataProvider->getModels());
    }

    public function testGetSortedDataByInnerArrayField(): void
    {
        $simpleArray = [
            ['innerArray' => ['sortField' => 1]],
            ['innerArray' => ['sortField' => 0]],
        ];
        $dataProvider = new ArrayDataProvider([
                'allModels' => $simpleArray,
                'sort' => [
                    'attributes' => [
                        'sort' => [
                            'asc' => ['innerArray.sortField' => SORT_ASC],
                            'desc' => ['innerArray.sortField' => SORT_DESC],
                            'label' => 'Sorting',
                            'default' => 'asc',
                        ],
                    ],
                    'defaultOrder' => [
                        'sort' => SORT_ASC,
                    ],
                ],
            ]);
        $sortedArray = [
            ['innerArray' => ['sortField' => 0]],
            ['innerArray' => ['sortField' => 1]],
        ];
        $this->assertEquals($sortedArray, $dataProvider->getModels());
    }

    public function testCaseSensitiveSort(): void
    {
        // source data
        $unsortedProjects = [
            ['title' => 'Zabbix', 'license' => 'GPL'],
            ['title' => 'munin', 'license' => 'GPL'],
            ['title' => 'Arch Linux', 'license' => 'GPL'],
            ['title' => 'Nagios', 'license' => 'GPL'],
            ['title' => 'zend framework', 'license' => 'BSD'],
            ['title' => 'Zope', 'license' => 'ZPL'],
            ['title' => 'active-record', 'license' => false],
            ['title' => 'ActiveState', 'license' => false],
            ['title' => 'mach', 'license' => false],
            ['title' => 'MySQL', 'license' => 'GPL'],
            ['title' => 'mssql', 'license' => 'EULA'],
            ['title' => 'Master-Master', 'license' => false],
            ['title' => 'Zend Engine', 'license' => false],
            ['title' => 'Mageia Linux', 'license' => 'GNU GPL'],
            ['title' => 'nginx', 'license' => 'BSD'],
            ['title' => 'Mozilla Firefox', 'license' => 'MPL'],
        ];

        // expected data
        $sortedProjects = [
            // upper cased titles
            ['title' => 'ActiveState', 'license' => false],
            ['title' => 'Arch Linux', 'license' => 'GPL'],
            ['title' => 'Mageia Linux', 'license' => 'GNU GPL'],
            ['title' => 'Master-Master', 'license' => false],
            ['title' => 'Mozilla Firefox', 'license' => 'MPL'],
            ['title' => 'MySQL', 'license' => 'GPL'],
            ['title' => 'Nagios', 'license' => 'GPL'],
            ['title' => 'Zabbix', 'license' => 'GPL'],
            ['title' => 'Zend Engine', 'license' => false],
            ['title' => 'Zope', 'license' => 'ZPL'],
            // lower cased titles
            ['title' => 'active-record', 'license' => false],
            ['title' => 'mach', 'license' => false],
            ['title' => 'mssql', 'license' => 'EULA'],
            ['title' => 'munin', 'license' => 'GPL'],
            ['title' => 'nginx', 'license' => 'BSD'],
            ['title' => 'zend framework', 'license' => 'BSD'],
        ];

        $dataProvider = new ArrayDataProvider([
                'allModels' => $unsortedProjects,
                'sort' => [
                    'attributes' => [
                        'sort' => [
                            'asc' => ['title' => SORT_ASC],
                            'desc' => ['title' => SORT_DESC],
                            'label' => 'Title',
                            'default' => 'desc',
                        ],
                    ],
                    'defaultOrder' => [
                        'sort' => SORT_ASC,
                    ],
                ],
                'pagination' => [
                    'pageSize' => 100500,
                ],
            ]);

        $this->assertEquals($sortedProjects, $dataProvider->getModels());
    }

    public function testGetKeys(): void
    {
        $pagination = ['pageSize' => 2];

        $simpleArray = [
            ['name' => 'zero'],
            ['name' => 'one'],
            ['name' => 'tow'],
        ];
        $dataProvider = new ArrayDataProvider(['allModels' => $simpleArray, 'pagination' => $pagination]);
        $this->assertEquals([0, 1], $dataProvider->getKeys());

        $namedArray = [
            'key1' => ['name' => 'zero'],
            'key2' => ['name' => 'one'],
            'key3' => ['name' => 'two'],
        ];
        $dataProvider = new ArrayDataProvider(['allModels' => $namedArray, 'pagination' => $pagination]);
        $this->assertEquals(['key1', 'key2'], $dataProvider->getKeys());

        $mixedArray = [
            'key1' => ['name' => 'zero'],
            9 => ['name' => 'one'],
            'key3' => ['name' => 'two'],
        ];
        $dataProvider = new ArrayDataProvider(['allModels' => $mixedArray, 'pagination' => $pagination]);
        $this->assertEquals(['key1', 9], $dataProvider->getKeys());
    }

    public function testSortFlags(): void
    {
        $simpleArray = [['sortField' => 1], ['sortField' => 2], ['sortField' => 11]];
        $dataProvider = new ArrayDataProvider([
                'allModels' => $simpleArray,
                'sort' => [
                    'sortFlags' => SORT_STRING,
                    'attributes' => [
                        'sort' => [
                            'asc' => ['sortField' => SORT_ASC],
                            'desc' => ['sortField' => SORT_DESC],
                            'label' => 'Sorting',
                            'default' => 'asc',
                        ],
                    ],
                    'defaultOrder' => [
                        'sort' => SORT_ASC,
                    ],
                ],
            ]);
        $sortedArray = [['sortField' => 1], ['sortField' => 11], ['sortField' => 2]];
        $this->assertEquals($sortedArray, $dataProvider->getModels());
    }
}
