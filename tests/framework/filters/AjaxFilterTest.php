<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\filters;

use Yii;
use yii\base\Action;
use yii\web\Request;
use yiiunit\TestCase;
use yii\web\Controller;
use yii\filters\AjaxFilter;

/**
 * @group filters
 */
class AjaxFilterTest extends TestCase
{
    /**
     * @param bool $isAjax
     *
     * @return Request
     */
    protected function mockRequest($isAjax)
    {
        /** @var Request $request */
        $request = $this->getMockBuilder('\yii\web\Request')
            ->setMethods(['getIsAjax'])
            ->getMock();
        $request->method('getIsAjax')->willReturn($isAjax);

        return $request;
    }

    public function testFilter(): void
    {
        $this->mockWebApplication();
        $controller = new Controller('id', Yii::$app);
        $action = new Action('test', $controller);
        $filter = new AjaxFilter();

        $filter->request = $this->mockRequest(true);
        $this->assertTrue($filter->beforeAction($action));

        $filter->request = $this->mockRequest(false);
        $this->expectException('yii\web\BadRequestHttpException');
        $filter->beforeAction($action);
    }
}
