<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\mutex;

use Yii;
use yiiunit\TestCase;
use yii\mutex\FileMutex;
use yii\base\InvalidConfigException;

/**
 * Class FileMutexTest.
 *
 * @group mutex
 */
class FileMutexTest extends TestCase
{
    use MutexTestTrait;

    /**
     * @return FileMutex
     *
     * @throws InvalidConfigException
     */
    protected function createMutex()
    {
        return Yii::createObject([
            'class' => FileMutex::className(),
            'mutexPath' => '@yiiunit/runtime/mutex',
        ]);
    }

    /**
     * @dataProvider mutexDataProvider()
     *
     * @param string $mutexName
     *
     * @throws InvalidConfigException
     */
    public function testDeleteLockFile($mutexName): void
    {
        $mutex = $this->createMutex();
        $fileName = $mutex->mutexPath . '/' . md5($mutexName) . '.lock';

        $mutex->acquire($mutexName);
        $this->assertFileExists($fileName);

        $mutex->release($mutexName);
        $this->assertFileDoesNotExist($fileName);
    }
}
