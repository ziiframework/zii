<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\mutex;

use Yii;
use yii\base\InvalidConfigException;
use yii\mutex\FileMutex;
use yiiunit\TestCase;

/**
 * Class FileMutexTest.
 *
 * @group mutex
 *
 * @internal
 * @coversNothing
 */
final class FileMutexTest extends TestCase
{
    use MutexTestTrait;

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

    /**
     * @throws InvalidConfigException
     *
     * @return FileMutex
     */
    protected function createMutex()
    {
        return Yii::createObject([
            'class' => FileMutex::className(),
            'mutexPath' => '@yiiunit/runtime/mutex',
        ]);
    }
}
