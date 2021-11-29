<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console;

use function func_get_args;
use function get_class;
use const PHP_VERSION_ID;
use RuntimeException;
use Yii;
use yii\base\InlineAction;
use yii\base\Module;
use yii\console\Application;
use yii\console\Exception;
use yii\console\Request;
use yii\helpers\Console;
use yiiunit\framework\console\stubs\DummyService;
use yiiunit\TestCase;

/**
 * @group console
 *
 * @internal
 * @coversNothing
 */
class ControllerTest extends TestCase
{
    /** @var FakeController */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        Yii::$app->controllerMap = [
            'fake' => 'yiiunit\framework\console\FakeController',
            'fake_witout_output' => 'yiiunit\framework\console\FakeHelpControllerWithoutOutput',
            'help' => 'yiiunit\framework\console\FakeHelpController',
        ];
    }

    public function testBindArrayToActionParams(): void
    {
        $controller = new FakeController('fake', Yii::$app);

        $params = ['test' => []];
        $this->assertSame([], $controller->runAction('aksi4', $params));
        $this->assertSame([], $controller->runAction('aksi4', $params));
    }

    public function testBindActionParams(): void
    {
        $controller = new FakeController('fake', Yii::$app);

        $params = ['from params'];
        [$fromParam, $other] = $controller->run('aksi1', $params);
        $this->assertSame('from params', $fromParam);
        $this->assertSame('default', $other);

        $params = ['from params', 'notdefault'];
        [$fromParam, $other] = $controller->run('aksi1', $params);
        $this->assertSame('from params', $fromParam);
        $this->assertSame('notdefault', $other);

        $params = ['d426,mdmunir', 'single'];
        $result = $controller->runAction('aksi2', $params);
        $this->assertSame([['d426', 'mdmunir'], 'single'], $result);

        $params = ['', 'single'];
        $result = $controller->runAction('aksi2', $params);
        $this->assertSame([[], 'single'], $result);

        $params = ['_aliases' => ['t' => 'test']];
        $result = $controller->runAction('aksi4', $params);
        $this->assertSame('test', $result);

        $params = ['_aliases' => ['a' => 'testAlias']];
        $result = $controller->runAction('aksi5', $params);
        $this->assertSame('testAlias', $result);

        $params = ['_aliases' => ['ta' => 'from params,notdefault']];
        [$fromParam, $other] = $controller->runAction('aksi6', $params);
        $this->assertSame('from params', $fromParam);
        $this->assertSame('notdefault', $other);

        $params = ['test-array' => 'from params,notdefault'];
        [$fromParam, $other] = $controller->runAction('aksi6', $params);
        $this->assertSame('from params', $fromParam);
        $this->assertSame('notdefault', $other);

        $params = ['from params', 'notdefault'];
        [$fromParam, $other] = $controller->run('trimargs', $params);
        $this->assertSame('from params', $fromParam);
        $this->assertSame('notdefault', $other);

        $params = ['avaliable'];
        $message = Yii::t('yii', 'Missing required arguments: {params}', ['params' => implode(', ', ['missing'])]);
        $this->expectException('yii\console\Exception');
        $this->expectExceptionMessage($message);
        $result = $controller->runAction('aksi3', $params);
    }

    public function testNullableInjectedActionParams(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }

        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new Application([
            'id' => 'app',
            'basePath' => __DIR__,
        ]));
        $this->mockApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionNullableInjection');
        $params = [];
        $args = $this->controller->bindActionParams($injectionAction, $params);
        $this->assertSame(Yii::$app->request, $args[0]);
        $this->assertNull($args[1]);
    }

    public function testInjectionContainerException(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new Application([
            'id' => 'app',
            'basePath' => __DIR__,
        ]));
        $this->mockApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionInjection');
        $params = ['between' => 'test', 'after' => 'another', 'before' => 'test'];
        Yii::$container->set(DummyService::className(), static function (): void { throw new RuntimeException('uh oh'); });

        $this->expectException(get_class(new RuntimeException()));
        $this->expectExceptionMessage('uh oh');
        $this->controller->bindActionParams($injectionAction, $params);
    }

    public function testUnknownInjection(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new Application([
            'id' => 'app',
            'basePath' => __DIR__,
        ]));
        $this->mockApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionInjection');
        $params = ['between' => 'test', 'after' => 'another', 'before' => 'test'];
        Yii::$container->clear(DummyService::className());
        $this->expectException(get_class(new Exception()));
        $this->expectExceptionMessage('Could not load required service: dummyService');
        $this->controller->bindActionParams($injectionAction, $params);
    }

    public function testInjectedActionParams(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new Application([
            'id' => 'app',
            'basePath' => __DIR__,
        ]));
        $this->mockApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionInjection');
        $params = ['between' => 'test', 'after' => 'another', 'before' => 'test'];
        Yii::$container->set(DummyService::className(), DummyService::className());
        $args = $this->controller->bindActionParams($injectionAction, $params);
        $this->assertSame($params['before'], $args[0]);
        $this->assertSame(Yii::$app->request, $args[1]);
        $this->assertSame('Component: yii\console\Request $request', Yii::$app->requestedParams['request']);
        $this->assertSame($params['between'], $args[2]);
        $this->assertInstanceOf(DummyService::className(), $args[3]);
        $this->assertSame('Container DI: yiiunit\framework\console\stubs\DummyService $dummyService', Yii::$app->requestedParams['dummyService']);
        $this->assertNull($args[4]);
        $this->assertSame('Unavailable service: post', Yii::$app->requestedParams['post']);
        $this->assertSame($params['after'], $args[5]);
    }

    public function testInjectedActionParamsFromModule(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        $module = new \yii\base\Module('fake', new Application([
            'id' => 'app',
            'basePath' => __DIR__,
        ]));
        $module->set('yii\data\DataProviderInterface', [
            'class' => \yii\data\ArrayDataProvider::className(),
        ]);
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', $module);
        $this->mockWebApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionModuleServiceInjection');
        $args = $this->controller->bindActionParams($injectionAction, []);
        $this->assertInstanceOf(\yii\data\ArrayDataProvider::className(), $args[0]);
        $this->assertSame('Module yii\base\Module DI: yii\data\DataProviderInterface $dataProvider', Yii::$app->requestedParams['dataProvider']);
    }

    public function assertResponseStatus($status, $response): void
    {
        $this->assertInstanceOf('yii\console\Response', $response);
        $this->assertSame($status, $response->exitStatus);
    }

    public function runRequest($route, $args = 0)
    {
        $request = new Request();
        $request->setParams(func_get_args());

        return Yii::$app->handleRequest($request);
    }

    public function testResponse(): void
    {
        $status = 123;

        $response = $this->runRequest('fake/status');
        $this->assertResponseStatus(0, $response);

        $response = $this->runRequest('fake/status', (string) $status);
        $this->assertResponseStatus($status, $response);

        $response = $this->runRequest('fake/response');
        $this->assertResponseStatus(0, $response);

        $response = $this->runRequest('fake/response', (string) $status);
        $this->assertResponseStatus($status, $response);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/12028
     */
    public function testHelpOptionNotSet(): void
    {
        $controller = new FakeController('posts', Yii::$app);
        $controller->runAction('index');

        $this->assertTrue(FakeController::getWasActionIndexCalled());
        $this->assertNull(FakeHelpController::getActionIndexLastCallParams());
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/12028
     */
    public function testHelpOption(): void
    {
        $controller = new FakeController('posts', Yii::$app);
        $controller->help = true;
        $controller->runAction('index');

        $this->assertFalse(FakeController::getWasActionIndexCalled());
        $this->assertSame(FakeHelpController::getActionIndexLastCallParams(), ['posts/index']);

        $helpController = new FakeHelpControllerWithoutOutput('help', Yii::$app);
        $helpController->actionIndex('fake/aksi1');
        $this->assertStringContainsString('--test-array, -ta', $helpController->outputString);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13071
     */
    public function testHelpOptionWithModule(): void
    {
        $controller = new FakeController('posts', new Module('news'));
        $controller->help = true;
        $controller->runAction('index');

        $this->assertFalse(FakeController::getWasActionIndexCalled());
        $this->assertSame(FakeHelpController::getActionIndexLastCallParams(), ['news/posts/index']);
    }

    /**
     * Tests if action help does not include (class) type hinted arguments.
     *
     * @see #10372
     */
    public function testHelpSkipsTypeHintedArguments(): void
    {
        $controller = new FakeController('fake', Yii::$app);
        $help = $controller->getActionArgsHelp($controller->createAction('with-complex-type-hint'));

        $this->assertArrayNotHasKey('typedArgument', $help);
        $this->assertArrayHasKey('simpleArgument', $help);
    }

    public function testGetActionHelpSummaryOnNull(): void
    {
        $controller = new FakeController('fake', Yii::$app);

        $controller->color = false;
        $helpSummary = $controller->getActionHelpSummary(null);
        $this->assertSame('Action not found.', $helpSummary);

        $controller->color = true;
        $helpSummary = $controller->getActionHelpSummary(null);
        $this->assertSame($controller->ansiFormat('Action not found.', Console::FG_RED), $helpSummary);
    }
}
