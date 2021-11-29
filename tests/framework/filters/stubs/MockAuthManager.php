<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\filters\stubs;

use yii\rbac\PhpManager;

class MockAuthManager extends PhpManager
{
    /**
     * This mock does not persist.
     * {@inheritDoc}
     */
    protected function saveToFile($data, $file): void
    {
    }
}
