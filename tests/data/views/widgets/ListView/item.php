<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/* @var $model array|object */
/* @var $key string */
/* @var $index int */
/* @var $widget \yii\widgets\ListView */

print "Item #{$index}: {$model['login']} - Widget: " . $widget->className();
