<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

/**
 * Mock for the time() function for web classes.
 *
 * @return int
 */
function time()
{
    return \yiiunit\framework\web\UserTest::$time ?: time();
}

namespace yiiunit\framework\web;

use Yii;
use yii\base\BaseObject;
use yii\rbac\CheckAccessInterface;
use yii\rbac\PhpManager;
use yii\web\Cookie;
use yii\web\CookieCollection;
use yii\web\ForbiddenHttpException;
use yiiunit\TestCase;

/**
 * @group web
 *
 * @internal
 * @coversNothing
 */
final class UserTest extends TestCase
{
    /**
     * @var int virtual time to be returned by mocked time() function.
     *          Null means normal time() behavior.
     */
    public static $time;

    protected function tearDown(): void
    {
        Yii::$app->session->removeAll();
        static::$time = null;
        parent::tearDown();
    }

    public function testLoginExpires(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                    'authTimeout' => 10,
                ],
                'authManager' => [
                    'class' => PhpManager::className(),
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                    'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                    'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);

        $am = Yii::$app->authManager;
        $am->removeAll();
        $am->add($role = $am->createPermission('rUser'));
        $am->add($perm = $am->createPermission('doSomething'));
        $am->addChild($role, $perm);
        $am->assign($role, 'user1');

        Yii::$app->session->removeAll();
        static::$time = time();
        Yii::$app->user->login(UserIdentity::findIdentity('user1'));

//        print_r(Yii::$app->session);
//        print_r($_SESSION);

        $this->mockWebApplication($appConfig);
        $this->assertFalse(Yii::$app->user->isGuest);
        $this->assertTrue(Yii::$app->user->can('doSomething'));

        static::$time += 5;
        $this->mockWebApplication($appConfig);
        $this->assertFalse(Yii::$app->user->isGuest);
        $this->assertTrue(Yii::$app->user->can('doSomething'));

        static::$time += 11;
        $this->mockWebApplication($appConfig);
        $this->assertTrue(Yii::$app->user->isGuest);
        $this->assertFalse(Yii::$app->user->can('doSomething'));
    }

    /**
     * Make sure autologin works more than once.
     *
     * @see https://github.com/yiisoft/yii2/issues/11825
     */
    public function testIssue11825(): void
    {
        global $cookiesMock;
        $cookiesMock = new CookieCollection();

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                    'authTimeout' => 10,
                    'enableAutoLogin' => true,
                    'autoRenewCookie' => false,
                ],
                'response' => [
                    'class' => MockResponse::className(),
                ],
                'request' => [
                    'class' => MockRequest::className(),
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);

        Yii::$app->session->removeAll();
        static::$time = time();
        Yii::$app->user->login(UserIdentity::findIdentity('user1'), 20);

        // User is logged in
        $this->mockWebApplication($appConfig);
        $this->assertFalse(Yii::$app->user->isGuest);

        // IdentityCookie is valid
        Yii::$app->session->removeAll();
        static::$time += 5;
        $this->mockWebApplication($appConfig);
        $this->assertFalse(Yii::$app->user->isGuest);

        // IdentityCookie is still valid
        Yii::$app->session->removeAll();
        static::$time += 10;
        $this->mockWebApplication($appConfig);
        $this->assertFalse(Yii::$app->user->isGuest);

        // IdentityCookie is no longer valid (we remove it manually, but browser will do it automatically)
        $this->invokeMethod(Yii::$app->user, 'removeIdentityCookie');
        Yii::$app->session->removeAll();
        static::$time += 25;
        $this->mockWebApplication($appConfig);
        $this->assertTrue(Yii::$app->user->isGuest);
    }

    public function testCookieCleanup(): void
    {
        global $cookiesMock;

        $cookiesMock = new CookieCollection();

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                    'enableAutoLogin' => true,
                ],
                'response' => [
                    'class' => MockResponse::className(),
                ],
                'request' => [
                    'class' => MockRequest::className(),
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);
        Yii::$app->session->removeAll();

        $cookie = new Cookie(Yii::$app->user->identityCookie);
        $cookie->value = 'junk';
        $cookiesMock->add($cookie);
        Yii::$app->user->getIdentity();
        $this->assertSame(\strlen($cookiesMock->getValue(Yii::$app->user->identityCookie['name'])), 0);

        Yii::$app->user->login(UserIdentity::findIdentity('user1'), 3600);
        $this->assertFalse(Yii::$app->user->isGuest);
        $this->assertSame(Yii::$app->user->id, 'user1');
        $this->assertNotSame(\strlen($cookiesMock->getValue(Yii::$app->user->identityCookie['name'])), 0);

        Yii::$app->user->login(UserIdentity::findIdentity('user2'), 0);
        $this->assertFalse(Yii::$app->user->isGuest);
        $this->assertSame(Yii::$app->user->id, 'user2');
        $this->assertSame(\strlen($cookiesMock->getValue(Yii::$app->user->identityCookie['name'])), 0);
    }

    public function testLoginRequired(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
                'authManager' => [
                    'class' => PhpManager::className(),
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                    'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                    'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);

        $user = Yii::$app->user;

        $this->reset();
        Yii::$app->request->setUrl('normal');
        $user->loginRequired();
        $this->assertSame('normal', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('ajax');
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $user->loginRequired();
        $this->assertSame(Yii::$app->getHomeUrl(), $user->getReturnUrl());
        // AJAX requests don't update returnUrl but they do cause redirection.
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $user->loginRequired(false);
        $this->assertSame('ajax', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('json-only');
        $_SERVER['HTTP_ACCEPT'] = 'Accept:  text/json, q=0.1';
        $user->loginRequired(true, false);
        $this->assertSame('json-only', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('json-only');
        $_SERVER['HTTP_ACCEPT'] = 'text/json,q=0.1';
        $user->loginRequired(true, false);
        $this->assertSame('json-only', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('accept-all');
        $_SERVER['HTTP_ACCEPT'] = '*/*;q=0.1';
        $user->loginRequired();
        $this->assertSame('accept-all', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('json-and-accept-all');
        $_SERVER['HTTP_ACCEPT'] = 'text/json, */*; q=0.1';

        try {
            $user->loginRequired();
        } catch (ForbiddenHttpException $e) {
        }
        $this->assertFalse(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('accept-html-json');
        $_SERVER['HTTP_ACCEPT'] = 'text/json; q=1, text/html; q=0.1';
        $user->loginRequired();
        $this->assertSame('accept-html-json', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        Yii::$app->request->setUrl('accept-html-json');
        $_SERVER['HTTP_ACCEPT'] = 'text/json;q=1,application/xhtml+xml;q=0.1';
        $user->loginRequired();
        $this->assertSame('accept-html-json', $user->getReturnUrl());
        $this->assertTrue(Yii::$app->response->getIsRedirection());

        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setUrl('dont-set-return-url-on-post-request');
        Yii::$app->getSession()->set($user->returnUrlParam, null);
        $user->loginRequired();
        $this->assertNull(Yii::$app->getSession()->get($user->returnUrlParam));

        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Yii::$app->request->setUrl('set-return-url-on-get-request');
        Yii::$app->getSession()->set($user->returnUrlParam, null);
        $user->loginRequired();
        $this->assertSame('set-return-url-on-get-request', Yii::$app->getSession()->get($user->returnUrlParam));

        // Confirm that returnUrl is not set.
        $this->reset();
        Yii::$app->request->setUrl('json-only');
        $_SERVER['HTTP_ACCEPT'] = 'text/json;q=0.1';

        try {
            $user->loginRequired();
        } catch (ForbiddenHttpException $e) {
        }
        $this->assertNotSame('json-only', $user->getReturnUrl());

        $this->reset();
        $_SERVER['HTTP_ACCEPT'] = 'text/json;q=0.1';
        $this->expectException('yii\\web\\ForbiddenHttpException');
        $user->loginRequired();
    }

    public function testLoginRequiredException1(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
                'authManager' => [
                    'class' => PhpManager::className(),
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                    'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                    'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);
        $this->reset();
        $_SERVER['HTTP_ACCEPT'] = 'text/json,q=0.1';
        $this->expectException('yii\\web\\ForbiddenHttpException');
        Yii::$app->user->loginRequired();
    }

    public function testAccessChecker(): void
    {
        $this->mockWebApplication([
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                    'accessChecker' => AccessChecker::className(),
                ],
            ],
        ]);
        $this->assertInstanceOf(AccessChecker::className(), Yii::$app->user->accessChecker);

        $this->mockWebApplication([
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                    'accessChecker' => [
                        'class' => AccessChecker::className(),
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(AccessChecker::className(), Yii::$app->user->accessChecker);

        $this->mockWebApplication([
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                    'accessChecker' => 'accessChecker',
                ],
                'accessChecker' => [
                    'class' => AccessChecker::className(),
                ],
            ],
        ]);
        $this->assertInstanceOf(AccessChecker::className(), Yii::$app->user->accessChecker);
    }

    public function testGetIdentityException(): void
    {
        $session = $this->createMock('yii\web\Session');
        $session->method('getHasSessionId')->willReturn(true);
        $session->method('get')->with($this->equalTo('__id'))->willReturn('1');

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => ExceptionIdentity::className(),
                ],
                'session' => $session,
            ],
        ];
        $this->mockWebApplication($appConfig);

        $exceptionThrown = false;

        try {
            Yii::$app->getUser()->getIdentity();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);

        // Do it again to make sure the exception is thrown the second time
        $this->expectException('Exception');
        Yii::$app->getUser()->getIdentity();
    }

    public function testSetIdentity(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
                'authManager' => [
                    'class' => PhpManager::className(),
                    'itemFile' => '@runtime/user_test_rbac_items.php',
                    'assignmentFile' => '@runtime/user_test_rbac_assignments.php',
                    'ruleFile' => '@runtime/user_test_rbac_rules.php',
                ],
            ],
        ];
        $this->mockWebApplication($appConfig);

        $am = Yii::$app->authManager;
        $am->removeAll();
        $am->add($role = $am->createPermission('rUser'));
        $am->add($perm = $am->createPermission('doSomething'));
        $am->addChild($role, $perm);
        $am->assign($role, 'user1');

        $this->assertNull(Yii::$app->user->identity);
        $this->assertFalse(Yii::$app->user->can('doSomething'));

        Yii::$app->user->setIdentity(UserIdentity::findIdentity('user1'));
        $this->assertInstanceOf(UserIdentity::className(), Yii::$app->user->identity);
        $this->assertTrue(Yii::$app->user->can('doSomething'));

        Yii::$app->user->setIdentity(null);
        $this->assertNull(Yii::$app->user->identity);
        $this->assertFalse(Yii::$app->user->can('doSomething'));

        $this->expectException('\yii\base\InvalidValueException');
        Yii::$app->user->setIdentity(new \stdClass());
    }

    public function testSessionAuthWithNonExistingId(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);

        Yii::$app->session->set('__id', '1');

        $this->assertNull(Yii::$app->user->getIdentity());
    }

    public function testSessionAuthWithMissingKey(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);

        Yii::$app->session->set('__id', 'user1');

        $this->assertNotNull(Yii::$app->user->getIdentity());
    }

    public function testSessionAuthWithInvalidKey(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);

        Yii::$app->session->set('__id', 'user1');
        Yii::$app->session->set('__authKey', 'invalid');

        $this->assertNull(Yii::$app->user->getIdentity());
    }

    public function testSessionAuthWithValidKey(): void
    {
        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className(),
                ],
            ],
        ];

        $this->mockWebApplication($appConfig);

        Yii::$app->session->set('__id', 'user1');
        Yii::$app->session->set('__authKey', 'ABCD1234');

        $this->assertNotNull(Yii::$app->user->getIdentity());
    }

    /**
     * Resets request, response and $_SERVER.
     */
    protected function reset(): void
    {
        static $server;

        if (!isset($server)) {
            $server = $_SERVER;
        }

        $_SERVER = $server;
        Yii::$app->set('response', ['class' => 'yii\web\Response']);
        Yii::$app->set('request', [
            'class' => 'yii\web\Request',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
            'url' => '',
        ]);
        Yii::$app->user->setReturnUrl(null);
    }
}

static $cookiesMock;

class MockRequest extends \yii\web\Request
{
    public function getCookies()
    {
        global $cookiesMock;

        return $cookiesMock;
    }
}

class MockResponse extends \yii\web\Response
{
    public function getCookies()
    {
        global $cookiesMock;

        return $cookiesMock;
    }
}

class AccessChecker extends BaseObject implements CheckAccessInterface
{
    public function checkAccess($userId, $permissionName, $params = []): void
    {
        // Implement checkAccess() method.
    }
}

class ExceptionIdentity extends \yiiunit\framework\filters\stubs\UserIdentity
{
    public static function findIdentity($id): void
    {
        throw new \Exception();
    }
}
