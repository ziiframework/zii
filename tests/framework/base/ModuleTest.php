<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use Yii;
use yii\base\BaseObject;
use yii\base\Controller;
use yii\base\Module;
use yiiunit\TestCase;

/**
 * @group base
 *
 * @internal
 * @coversNothing
 */
final class ModuleTest extends TestCase
{
    public static $actionRuns = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    public function testTrueParentModule(): void
    {
        $parent = new Module('parent');
        $child = new Module('child');
        $child2 = new Module('child2');

        $parent->setModule('child', $child);
        $parent->setModules(['child2' => $child2]);

        $this->assertSame('parent', $child->module->id);
        $this->assertSame('parent', $child2->module->id);
    }

    public function testControllerPath(): void
    {
        $module = new TestModule('test');
        $this->assertSame('yiiunit\framework\base\controllers', $module->controllerNamespace);
        $this->assertSame(__DIR__ . DIRECTORY_SEPARATOR . 'controllers', str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $module->controllerPath));
    }

    public function testSetupVersion(): void
    {
        $module = new TestModule('test');

        $version = '1.0.1';
        $module->setVersion($version);
        $this->assertSame($version, $module->getVersion());

        $module->setVersion(static function ($module) {
            /* @var $module TestModule */
            return 'version.' . $module->getUniqueId();
        });
        $this->assertSame('version.test', $module->getVersion());
    }

    /**
     * @depends testSetupVersion
     */
    public function testDefaultVersion(): void
    {
        $module = new TestModule('test');

        $version = $module->getVersion();
        $this->assertSame('1.0', $version);
    }

    public function testRunControllerAction(): void
    {
        $module = new TestModule('test');
        $this->assertNull(Yii::$app->controller);
        static::$actionRuns = [];

        $module->runAction('test-controller1/test1');
        $this->assertSame([
            'test/test-controller1/test1',
        ], static::$actionRuns);
        $this->assertNotNull(Yii::$app->controller);
        $this->assertSame('test-controller1', Yii::$app->controller->id);
        $this->assertSame('test/test-controller1', Yii::$app->controller->uniqueId);
        $this->assertNotNull(Yii::$app->controller->action);
        $this->assertSame('test/test-controller1/test1', Yii::$app->controller->action->uniqueId);

        $module->runAction('test-controller2/test2');
        $this->assertSame([
            'test/test-controller1/test1',
            'test/test-controller2/test2',
        ], static::$actionRuns);
        $this->assertNotNull(Yii::$app->controller);
        $this->assertSame('test-controller1', Yii::$app->controller->id);
        $this->assertSame('test/test-controller1', Yii::$app->controller->uniqueId);
        $this->assertNotNull(Yii::$app->controller->action);
        $this->assertSame('test/test-controller1/test1', Yii::$app->controller->action->uniqueId);
    }

    public function testServiceLocatorTraversal(): void
    {
        $parent = new Module('parent');
        $child = new Module('child', $parent);
        $grandchild = new Module('grandchild', $child);

        $parentObject = new BaseObject();
        $childObject = new BaseObject();

        $parent->set('test', $parentObject);
        $this->assertTrue($grandchild->has('test'));
        $this->assertTrue($child->has('test'));
        $this->assertTrue($parent->has('test'));
        $this->assertSame($parentObject, $grandchild->get('test'));
        $this->assertSame($parentObject, $child->get('test'));
        $this->assertSame($parentObject, $parent->get('test'));

        $child->set('test', $childObject);
        $this->assertSame($childObject, $grandchild->get('test'));
        $this->assertSame($childObject, $child->get('test'));
        $this->assertSame($parentObject, $parent->get('test'));
        $this->assertTrue($grandchild->has('test'));
        $this->assertTrue($child->has('test'));
        $this->assertTrue($parent->has('test'));

        $parent->clear('test');
        $this->assertSame($childObject, $grandchild->get('test'));
        $this->assertSame($childObject, $child->get('test'));
        $this->assertTrue($grandchild->has('test'));
        $this->assertTrue($child->has('test'));
        $this->assertFalse($parent->has('test'));
    }

    public function testCreateControllerByID(): void
    {
        $module = new TestModule('test');
        $module->controllerNamespace = 'yiiunit\framework\base';

        $route = 'module-test';
        $this->assertInstanceOf(ModuleTestController::className(), $module->createControllerByID($route));

        $route = 'module-test-';
        $this->assertNotInstanceOf(ModuleTestController::className(), $module->createControllerByID($route));

        $route = '-module-test';
        $this->assertNotInstanceOf(ModuleTestController::className(), $module->createControllerByID($route));

        $route = 'very-complex-name-test';
        $this->assertInstanceOf(VeryComplexNameTestController::className(), $module->createControllerByID($route));

        $route = 'very-complex-name-test--';
        $this->assertNotInstanceOf(VeryComplexNameTestController::className(), $module->createControllerByID($route));

        $route = '--very-complex-name-test';
        $this->assertNotInstanceOf(VeryComplexNameTestController::className(), $module->createControllerByID($route));

        $route = 'very---complex---name---test';
        $this->assertNotInstanceOf(VeryComplexNameTestController::className(), $module->createControllerByID($route));
    }

    public function testCreateController(): void
    {
        // app module has a submodule "base" which has two controllers: "default" and "other"
        $module = new Module('app');
        $module->setModule('base', new Module('base'));
        $defaultController = ['class' => 'yii\web\Controller'];
        $otherController = ['class' => 'yii\web\Controller'];
        $module->getModule('base')->controllerMap = [
            'default' => $defaultController,
            'other' => $otherController,
        ];

        [$controller, $action] = $module->createController('base');
        $this->assertSame('', $action);
        $this->assertSame('app/base/default', $controller->uniqueId);

        [$controller, $action] = $module->createController('base/default');
        $this->assertSame('', $action);
        $this->assertSame('app/base/default', $controller->uniqueId);

        [$controller, $action] = $module->createController('base/other');
        $this->assertSame('', $action);
        $this->assertSame('app/base/other', $controller->uniqueId);

        [$controller, $action] = $module->createController('base/default/index');
        $this->assertSame('index', $action);
        $this->assertSame('app/base/default', $controller->uniqueId);

        [$controller, $action] = $module->createController('base/other/index');
        $this->assertSame('index', $action);
        $this->assertSame('app/base/other', $controller->uniqueId);

        [$controller, $action] = $module->createController('base/other/someaction');
        $this->assertSame('someaction', $action);
        $this->assertSame('app/base/other', $controller->uniqueId);

        $controller = $module->createController('bases/default/index');
        $this->assertFalse($controller);

        $controller = $module->createController('nocontroller');
        $this->assertFalse($controller);
    }
}

class TestModule extends \yii\base\Module
{
    public $controllerMap = [
        'test-controller1' => 'yiiunit\framework\base\ModuleTestController',
        'test-controller2' => 'yiiunit\framework\base\ModuleTestController',
    ];
}

class ModuleTestController extends Controller
{
    public function actionTest1(): void
    {
        ModuleTest::$actionRuns[] = $this->action->uniqueId;
    }

    public function actionTest2(): void
    {
        ModuleTest::$actionRuns[] = $this->action->uniqueId;
    }
}

class VeryComplexNameTestController extends Controller
{
    public function actionIndex(): void
    {
        ModuleTest::$actionRuns[] = $this->action->uniqueId;
    }
}
