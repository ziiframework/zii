<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use Yii;
use yiiunit\TestCase;
use yii\web\ErrorAction;
use yii\base\UserException;
use InvalidArgumentException;
use yii\base\InvalidConfigException;
use yiiunit\data\controllers\TestController;

/**
 * @group web
 */
class ErrorActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockWebApplication();
    }

    /**
     * Creates a controller instance.
     *
     * @param array $actionConfig
     *
     * @return TestController
     */
    public function getController($actionConfig = [])
    {
        return new TestController('test', Yii::$app, ['layout' => false, 'actionConfig' => $actionConfig]);
    }

    public function testYiiException(): void
    {
        Yii::$app->getErrorHandler()->exception = new InvalidConfigException('This message will not be shown to the user');

        $this->assertEqualsWithoutLE('Name: Invalid Configuration
Code: 500
Message: An internal server error occurred.
Exception: yii\base\InvalidConfigException', $this->getController()->runAction('error'));
    }

    public function testUserException(): void
    {
        Yii::$app->getErrorHandler()->exception = new UserException('User can see this error message');

        $this->assertEqualsWithoutLE('Name: Exception
Code: 500
Message: User can see this error message
Exception: yii\base\UserException', $this->getController()->runAction('error'));
    }

    public function testAjaxRequest(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertEquals('Not Found (#404): Page not found.', $this->getController()->runAction('error'));
    }

    public function testGenericException(): void
    {
        Yii::$app->getErrorHandler()->exception = new InvalidArgumentException('This message will not be shown to the user');

        $this->assertEqualsWithoutLE('Name: Error
Code: 500
Message: An internal server error occurred.
Exception: InvalidArgumentException', $this->getController()->runAction('error'));
    }

    public function testGenericExceptionCustomNameAndMessage(): void
    {
        Yii::$app->getErrorHandler()->exception = new InvalidArgumentException('This message will not be shown to the user');

        $controller = $this->getController([
            'defaultName' => 'Oops...',
            'defaultMessage' => 'The system is drunk',
        ]);

        $this->assertEqualsWithoutLE('Name: Oops...
Code: 500
Message: The system is drunk
Exception: InvalidArgumentException', $controller->runAction('error'));
    }

    public function testNoExceptionInHandler(): void
    {
        $this->assertEqualsWithoutLE('Name: Not Found (#404)
Code: 404
Message: Page not found.
Exception: yii\web\NotFoundHttpException', $this->getController()->runAction('error'));
    }

    public function testDefaultView(): void
    {
        /** @var ErrorAction $action */
        $action = $this->getController()->createAction('error');

        // Unset view name. Class should try to load view that matches action name by default
        $action->view = null;
        $ds = preg_quote(DIRECTORY_SEPARATOR, '\\');
        $this->expectException('yii\base\ViewNotFoundException');
        $this->expectExceptionMessageMatches('#The view file does not exist: .*?views' . $ds . 'test' . $ds . 'error.php#');
        $this->invokeMethod($action, 'renderHtmlResponse');
    }

    public function testLayout(): void
    {
        $this->expectException('yii\base\ViewNotFoundException');

        $this->getController([
            'layout' => 'non-existing',
        ])->runAction('error');

        $ds = preg_quote(DIRECTORY_SEPARATOR, '\\');
        $this->expectExceptionMessageMatches('#The view file does not exist: .*?views' . $ds . 'layouts' . $ds . 'non-existing.php#');
    }
}
