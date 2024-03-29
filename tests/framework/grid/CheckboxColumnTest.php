<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\grid;

use Yii;
use yii\helpers\Html;
use yiiunit\TestCase;
use yii\grid\GridView;
use yii\helpers\FileHelper;
use yii\grid\CheckboxColumn;
use yii\data\ArrayDataProvider;
use yiiunit\framework\i18n\IntlTestHelper;

/**
 * @group grid
 */
class CheckboxColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        IntlTestHelper::resetIntlStatus();
        $this->mockApplication();
        Yii::setAlias('@webroot', '@yiiunit/runtime');
        Yii::setAlias('@web', 'http://localhost/');
        FileHelper::createDirectory(Yii::getAlias('@webroot/assets'));
        Yii::$app->assetManager->bundles['yii\web\JqueryAsset'] = false;
    }

    public function testInputName(): void
    {
        $column = new CheckboxColumn(['name' => 'selection', 'grid' => $this->getGrid()]);
        $this->assertStringContainsString('name="selection_all"', $column->renderHeaderCell());

        $column = new CheckboxColumn(['name' => 'selections[]', 'grid' => $this->getGrid()]);
        $this->assertStringContainsString('name="selections_all"', $column->renderHeaderCell());

        $column = new CheckboxColumn(['name' => 'MyForm[grid1]', 'grid' => $this->getGrid()]);
        $this->assertStringContainsString('name="MyForm[grid1_all]"', $column->renderHeaderCell());

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][]', 'grid' => $this->getGrid()]);
        $this->assertStringContainsString('name="MyForm[grid1_all]"', $column->renderHeaderCell());

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][key]', 'grid' => $this->getGrid()]);
        $this->assertStringContainsString('name="MyForm[grid1][key_all]"', $column->renderHeaderCell());

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][key][]', 'grid' => $this->getGrid()]);
        $this->assertStringContainsString('name="MyForm[grid1][key_all]"', $column->renderHeaderCell());
    }

    public function testInputValue(): void
    {
        $column = new CheckboxColumn(['grid' => $this->getGrid()]);
        $this->assertStringContainsString('value="1"', $column->renderDataCell([], 1, 0));
        $this->assertStringContainsString('value="42"', $column->renderDataCell([], 42, 0));
        $this->assertStringContainsString('value="[1,42]"', $column->renderDataCell([], [1, 42], 0));

        $column = new CheckboxColumn(['checkboxOptions' => ['value' => 42], 'grid' => $this->getGrid()]);
        $this->assertStringNotContainsString('value="1"', $column->renderDataCell([], 1, 0));
        $this->assertStringContainsString('value="42"', $column->renderDataCell([], 1, 0));

        $column = new CheckboxColumn([
            'checkboxOptions' => static fn ($model, $key, $index, $column) => [],
            'grid' => $this->getGrid(),
        ]);
        $this->assertStringContainsString('value="1"', $column->renderDataCell([], 1, 0));
        $this->assertStringContainsString('value="42"', $column->renderDataCell([], 42, 0));
        $this->assertStringContainsString('value="[1,42]"', $column->renderDataCell([], [1, 42], 0));

        $column = new CheckboxColumn([
            'checkboxOptions' => static fn ($model, $key, $index, $column) => ['value' => 42],
            'grid' => $this->getGrid(),
        ]);
        $this->assertStringNotContainsString('value="1"', $column->renderDataCell([], 1, 0));
        $this->assertStringContainsString('value="42"', $column->renderDataCell([], 1, 0));
    }

    public function testContent(): void
    {
        $column = new CheckboxColumn([
            'content' => static fn ($model, $key, $index, $column) => null,
            'grid' => $this->getGrid(),
        ]);
        $this->assertStringContainsString('<td></td>', $column->renderDataCell([], 1, 0));

        $column = new CheckboxColumn([
            'content' => static fn ($model, $key, $index, $column) => Html::checkBox('checkBoxInput', false),
            'grid' => $this->getGrid(),
        ]);
        $this->assertStringContainsString(Html::checkBox('checkBoxInput', false), $column->renderDataCell([], 1, 0));
    }

    /**
     * @return GridView a mock gridview
     */
    protected function getGrid()
    {
        return new GridView([
            'dataProvider' => new ArrayDataProvider(['allModels' => [], 'totalCount' => 0]),
        ]);
    }
}
