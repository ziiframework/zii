<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

$obj = new class() extends \yii\base\Component {
    public $foo = 0;
};

$obj->attachBehavior('bar', (new class() extends \yii\base\Behavior {
    public function events()
    {
        return [
            'barEventOnce' => function ($event): void {
                ++$this->owner->foo;
                $this->detach();
            },
        ];
    }
}));

return $obj;
