<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

use yii\di\Container;
use yiiunit\framework\di\stubs\QuxInterface;

$container = new Container();
$func = static fn (QuxInterface ...$quxes) => "That's a whole lot of quxes!";
$container->invoke($func);
