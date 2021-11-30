<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

$obj = new class() extends \yii\base\Component {
    public $foo = 0;
};

$obj->attachBehavior('bar', (new class() extends \yii\base\Behavior {
    public function events()
    {
        return [
            'barEventOnce' => function ($event) {
                ++$this->owner->foo;
                $this->detach();
            },
        ];
    }
}));

return $obj;
