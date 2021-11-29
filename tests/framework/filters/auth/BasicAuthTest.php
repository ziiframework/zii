<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\filters\auth;

use function is_string;
use Yii;
use yii\base\Event;
use yii\filters\auth\HttpBasicAuth;
use yii\web\User;
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
final class BasicAuthTest extends AuthTest
{
    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpBasicAuth($token, $login): void
    {
        $original = $_SERVER;

        $_SERVER['PHP_AUTH_USER'] = $token;
        $_SERVER['PHP_AUTH_PW'] = 'whatever, we are testers';
        $filter = ['class' => HttpBasicAuth::className()];
        $this->ensureFilterApplies($token, $login, $filter);
        $_SERVER = $original;
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpBasicAuthWithHttpAuthorizationHeader($token, $login): void
    {
        $original = $_SERVER;

        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode($token . ':' . 'mypw');
        $filter = ['class' => HttpBasicAuth::className()];
        $this->ensureFilterApplies($token, $login, $filter);
        $_SERVER = $original;
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpBasicAuthWithRedirectHttpAuthorizationHeader($token, $login): void
    {
        $original = $_SERVER;

        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode($token . ':' . 'mypw');
        $filter = ['class' => HttpBasicAuth::className()];
        $this->ensureFilterApplies($token, $login, $filter);
        $_SERVER = $original;
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpBasicAuthCustom($token, $login): void
    {
        $_SERVER['PHP_AUTH_USER'] = $login;
        $_SERVER['PHP_AUTH_PW'] = 'whatever, we are testers';
        $filter = [
            'class' => HttpBasicAuth::className(),
            'auth' => static function ($username, $password) {
                if (is_string($username) && preg_match('/\d$/', $username)) {
                    return UserIdentity::findIdentity($username);
                }

                return null;
            },
        ];
        $this->ensureFilterApplies($token, $login, $filter);
    }

    /**
     * This tests checks, that:
     *  - HttpBasicAuth does not call `auth` closure, when a user is already authenticated
     *  - HttpBasicAuth does not switch identity, even when the user identity to be set is the same as current user's one.
     *
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testHttpBasicAuthIssue15658($token, $login): void
    {
        $_SERVER['PHP_AUTH_USER'] = $login;
        $_SERVER['PHP_AUTH_PW'] = 'y0u7h1nk175r34l?';

        $user = Yii::$app->user;
        $session = Yii::$app->session;
        $user->login(UserIdentity::findIdentity('user1'));
        $identity = $user->getIdentity();
        $sessionId = $session->getId();

        $filter = [
            'class' => HttpBasicAuth::className(),
            'auth' => function ($username, $password): void {
                $this->fail('Authentication closure should not be called when user is already authenticated');
            },
        ];
        $this->ensureFilterApplies('token1', 'user1', $filter);

        $this->assertSame($identity, $user->getIdentity());
        $this->assertSame($sessionId, $session->getId());
        $session->destroy();
    }

    public function authMethodProvider()
    {
        return [
            ['yii\filters\auth\HttpBasicAuth'],
        ];
    }

    /**
     * @dataProvider tokenProvider
     *
     * @param null|string $token
     * @param null|string $login
     */
    public function testAfterLoginEventIsTriggered18031($token, $login): void
    {
        $triggered = false;
        Event::on('\yii\web\User', User::EVENT_AFTER_LOGIN, function ($event) use (&$triggered): void {
            $triggered = true;
            $this->assertTrue($triggered);
        });
        $this->testHttpBasicAuthCustom($token, $login);
        Event::off('\yii\web\User', User::EVENT_AFTER_LOGIN); // required because this method runs in foreach loop. See @dataProvider tokenProvider
    }
}
