<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/** @var \yiiunit\framework\db\CommandTest $this */
$rows = call_user_func(static function () {
    if (false) {
        yield [];
    }
});

$command = $this->getConnection()->createCommand();
$command->batchInsert('{{customer}}', ['email', 'name', 'address'], $rows);
$this->assertEquals(0, $command->execute());
