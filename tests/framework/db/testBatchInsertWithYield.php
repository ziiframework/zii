<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

/* @var $this \yiiunit\framework\db\CommandTest */

$rows = call_user_func(static function () {
    if (false) {
        yield [];
    }
});

$command = $this->getConnection()->createCommand();
$command->batchInsert('{{customer}}', ['email', 'name', 'address'], $rows);
$this->assertEquals(0, $command->execute());
