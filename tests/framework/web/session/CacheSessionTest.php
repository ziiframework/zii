<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web\session;

use Yii;
use yii\web\CacheSession;
use yii\caching\FileCache;

/**
 * @group web
 */
class CacheSessionTest extends \yiiunit\TestCase
{
    use SessionTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        Yii::$app->set('cache', new FileCache());
    }

    public function testCacheSession(): void
    {
        $session = new CacheSession();

        $session->writeSession('test', 'sessionData');
        $this->assertEquals('sessionData', $session->readSession('test'));
        $session->destroySession('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    public function testInvalidCache(): void
    {
        $this->expectException('\Exception');
        new CacheSession(['cache' => 'invalid']);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13537
     */
    public function testNotWrittenSessionDestroying(): void
    {
        $session = new CacheSession();

        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));

        $this->assertTrue($session->destroySession($session->getId()));
    }

    public function testInitUseStrictMode(): void
    {
        $this->initStrictModeTest(CacheSession::className());
    }

    public function testUseStrictMode(): void
    {
        $this->useStrictModeTest(CacheSession::className());
    }
}
