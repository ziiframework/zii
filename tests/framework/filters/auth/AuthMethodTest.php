<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\filters\auth;

use ReflectionClass;
use stdClass;
use Yii;
use yii\base\Action;
use yii\filters\auth\AuthMethod;
use yii\web\Controller;
use yiiunit\framework\filters\stubs\UserIdentity;
use yiiunit\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AuthMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebApplication([
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
            ],
        ]);
    }

    // Tests :

    public function testBeforeAction(): void
    {
        $action = $this->createAction();

        $filter = $this->createFilter(static fn () => new stdClass());
        $this->assertTrue($filter->beforeAction($action));

        $filter = $this->createFilter(static fn () => null);
        $this->expectException('yii\web\UnauthorizedHttpException');
        $this->assertTrue($filter->beforeAction($action));
    }

    public function testIsOptional(): void
    {
        $reflection = new ReflectionClass(AuthMethod::className());
        $method = $reflection->getMethod('isOptional');
        $method->setAccessible(true);

        $filter = $this->createFilter(static fn () => new stdClass());

        $filter->optional = ['some'];
        $this->assertFalse($method->invokeArgs($filter, [$this->createAction(['id' => 'index'])]));
        $this->assertTrue($method->invokeArgs($filter, [$this->createAction(['id' => 'some'])]));

        $filter->optional = ['test/*'];
        $this->assertFalse($method->invokeArgs($filter, [$this->createAction(['id' => 'index'])]));
        $this->assertTrue($method->invokeArgs($filter, [$this->createAction(['id' => 'test/index'])]));
    }

    /**
     * Creates mock for [[AuthMethod]] filter.
     *
     * @param callable $authenticateCallback callback, which result should [[authenticate()]] method return
     *
     * @return AuthMethod filter instance
     */
    protected function createFilter($authenticateCallback)
    {
        $filter = $this->getMockBuilder(AuthMethod::className())
            ->setMethods(['authenticate'])
            ->getMock()
        ;
        $filter->method('authenticate')->willReturnCallback($authenticateCallback);

        return $filter;
    }

    /**
     * Creates test action.
     *
     * @param array $config action configuration
     *
     * @return Action action instance
     */
    protected function createAction(array $config = [])
    {
        $controller = new Controller('test', Yii::$app);

        return new Action('index', $controller, $config);
    }
}
