<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\filters\auth;

use ReflectionClass;
use Yii;
use yii\base\Action;
use yii\filters\auth\AuthMethod;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\HttpHeaderAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;
use yiiunit\framework\filters\stubs\UserIdentity;

/**
 * @group filters
 *
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 *
 * @since 2.0.7
 *
 * @internal
 * @coversNothing
 */
final class AuthTest extends \yiiunit\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['SCRIPT_FILENAME'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
            ],
            'controllerMap' => [
                'test-auth' => TestAuthController::className(),
            ],
        ];

        $this->mockWebApplication($appConfig);
    }

    public function tokenProvider()
    {
        return [
            ['token1', 'user1'],
            ['token2', 'user2'],
            ['token3', 'user3'],
            ['unknown', null],
            [null, null],
        ];
    }

    public function authOnly($token, $login, $filter): void
    {
        /** @var TestAuthController $controller */
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['only' => ['filtered']]);

        try {
            $this->assertSame($login, $controller->run('filtered'));
        } catch (UnauthorizedHttpException $e) {
        }
    }

    public function authOptional($token, $login, $filter): void
    {
        /** @var TestAuthController $controller */
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['optional' => ['filtered']]);

        try {
            $this->assertSame($login, $controller->run('filtered'));
        } catch (UnauthorizedHttpException $e) {
        }
    }

    public function authExcept($token, $login, $filter): void
    {
        /** @var TestAuthController $controller */
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['except' => ['other']]);

        try {
            $this->assertSame($login, $controller->run('filtered'));
        } catch (UnauthorizedHttpException $e) {
        }
    }

    public function ensureFilterApplies($token, $login, $filter): void
    {
        $this->authOnly($token, $login, $filter);
        $this->authOptional($token, $login, $filter);
        $this->authExcept($token, $login, $filter);
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testQueryParamAuth($token, $login): void
    {
        $_GET['access-token'] = $token;
        $filter = ['class' => QueryParamAuth::className()];
        $this->ensureFilterApplies($token, $login, $filter);
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpHeaderAuth($token, $login): void
    {
        Yii::$app->request->headers->set('X-Api-Key', $token);
        $filter = ['class' => HttpHeaderAuth::className()];
        $this->ensureFilterApplies($token, $login, $filter);
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpBearerAuth($token, $login): void
    {
        Yii::$app->request->headers->set('Authorization', "Bearer {$token}");
        $filter = ['class' => HttpBearerAuth::className()];
        $this->ensureFilterApplies($token, $login, $filter);
    }

    public function authMethodProvider()
    {
        return [
            ['yii\filters\auth\CompositeAuth'],
            ['yii\filters\auth\HttpBearerAuth'],
            ['yii\filters\auth\QueryParamAuth'],
            ['yii\filters\auth\HttpHeaderAuth'],
        ];
    }

    /**
     * @dataProvider authMethodProvider
     *
     * @param string $authClass
     */
    public function testActive($authClass): void
    {
        /** @var AuthMethod $filter */
        $filter = new $authClass();
        $reflection = new ReflectionClass($filter);
        $method = $reflection->getMethod('isActive');
        $method->setAccessible(true);

        $controller = new \yii\web\Controller('test', Yii::$app);

        // active by default
        $this->assertTrue($method->invokeArgs($filter, [new Action('index', $controller)]));
        $this->assertTrue($method->invokeArgs($filter, [new Action('view', $controller)]));

        $filter->only = ['index'];
        $filter->except = [];
        $filter->optional = [];
        $this->assertTrue($method->invokeArgs($filter, [new Action('index', $controller)]));
        $this->assertFalse($method->invokeArgs($filter, [new Action('view', $controller)]));

        $filter->only = ['index'];
        $filter->except = [];
        $filter->optional = ['view'];
        $this->assertTrue($method->invokeArgs($filter, [new Action('index', $controller)]));
        $this->assertFalse($method->invokeArgs($filter, [new Action('view', $controller)]));

        $filter->only = ['index', 'view'];
        $filter->except = ['view'];
        $filter->optional = [];
        $this->assertTrue($method->invokeArgs($filter, [new Action('index', $controller)]));
        $this->assertFalse($method->invokeArgs($filter, [new Action('view', $controller)]));

        $filter->only = ['index', 'view'];
        $filter->except = ['view'];
        $filter->optional = ['view'];
        $this->assertTrue($method->invokeArgs($filter, [new Action('index', $controller)]));
        $this->assertFalse($method->invokeArgs($filter, [new Action('view', $controller)]));

        $filter->only = [];
        $filter->except = ['view'];
        $filter->optional = ['view'];
        $this->assertTrue($method->invokeArgs($filter, [new Action('index', $controller)]));
        $this->assertFalse($method->invokeArgs($filter, [new Action('view', $controller)]));
    }

    public function testHeaders(): void
    {
        Yii::$app->request->headers->set('Authorization', 'Bearer wrong_token');
        $filter = ['class' => HttpBearerAuth::className()];
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['only' => ['filtered']]);

        try {
            $controller->run('filtered');
            $this->fail('Should throw UnauthorizedHttpException');
        } catch (UnauthorizedHttpException $e) {
            $this->assertArrayHasKey('WWW-Authenticate', Yii::$app->getResponse()->getHeaders());
        }
    }
}

/**
 * Class TestAuthController.
 *
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 *
 * @since 2.0.7
 */
class TestAuthController extends Controller
{
    public $authenticatorConfig = [];

    public function behaviors()
    {
        return ['authenticator' => $this->authenticatorConfig];
    }

    public function actionFiltered()
    {
        return Yii::$app->user->id;
    }
}
