<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use Yii;
use yiiunit\TestCase;
use yii\base\Controller;
use yii\base\InlineAction;

/**
 * @group base
 */
class ControllerTest extends TestCase
{
    public static $actionRuns = [];

    public function testRunAction(): void
    {
        $this->mockApplication();

        static::$actionRuns = [];
        $controller = new TestController('test-controller', Yii::$app);
        $this->assertNull($controller->action);
        $result = $controller->runAction('test1');
        $this->assertEquals('test1', $result);
        $this->assertEquals([
            'test-controller/test1',
        ], static::$actionRuns);
        $this->assertNotNull($controller->action);
        $this->assertEquals('test1', $controller->action->id);
        $this->assertEquals('test-controller/test1', $controller->action->uniqueId);

        $result = $controller->runAction('test2');
        $this->assertEquals('test2', $result);
        $this->assertEquals([
            'test-controller/test1',
            'test-controller/test2',
        ], static::$actionRuns);
        $this->assertNotNull($controller->action);
        $this->assertEquals('test1', $controller->action->id);
        $this->assertEquals('test-controller/test1', $controller->action->uniqueId);
    }

    /**
     * @dataProvider createInlineActionProvider
     *
     * @param string $controllerClass
     * @param string $actionId
     * @param string|null $expectedActionMethod
     */
    public function testCreateInlineAction($controllerClass, $actionId, $expectedActionMethod): void
    {
        $this->mockApplication();

        /** @var Controller $controller */
        $controller = new $controllerClass('test-controller', Yii::$app);

        /** @var InlineAction $action */
        $action = $controller->createAction($actionId);
        $actionMethod = $action !== null ? $action->actionMethod : null;

        $this->assertEquals($expectedActionMethod, $actionMethod);
    }

    public function createInlineActionProvider()
    {
        return [
            ['\yiiunit\framework\base\TestController', 'non-existent-id', null],
            ['\yiiunit\framework\base\TestController', 'test3', 'actionTest3'],
            ['\yiiunit\framework\base\TestController', 'test-test', 'actionTestTest'],
            ['\yiiunit\framework\base\Test1Controller', 'test_test', 'actionTest_test'],
            ['\yiiunit\framework\base\Test1Controller', 'test_1', 'actionTest_1'],
            ['\yiiunit\framework\base\Test1Controller', 'test-test_test_2', 'actionTestTest_test_2'],
        ];
    }

    /**
     * @dataProvider actionIdMethodProvider
     */
    public function testActionIdMethod($input, $expected): void
    {
        $this->assertSame($expected, preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $input));
    }

    public function actionIdMethodProvider()
    {
        return [
            ['apple-id', 1],
            ['-apple', 0],
            ['apple.', 0],
            ['apple--id', 0],
            ['a', 1],
            ['9', 1],
            ['apple-999', 1],
            ['app^le-999', 0],
            ['!', 0],
            ['apple\33', 0],
            ['apple333]', 0],
            ['apple_222', 1],
        ];
    }
}

class TestController extends Controller
{
    public function actionTest1()
    {
        ControllerTest::$actionRuns[] = $this->action->uniqueId;

        return 'test1';
    }

    public function actionTest2()
    {
        ControllerTest::$actionRuns[] = $this->action->uniqueId;

        return 'test2';
    }

    public function actionTest3(): void
    {
    }

    public function actionTestTest(): void
    {
    }

    public function actionTest_test(): void
    {
    }
}

class Test1Controller extends Controller
{
    public function actionTest_1(): void
    {
    }

    public function actionTest_test(): void
    {
    }

    public function actionTestTest_test_2(): void
    {
    }
}
