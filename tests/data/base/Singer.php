<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\data\base;

use yii\base\Model;

/**
 * Singer.
 */
class Singer extends Model
{
    public static $tableName;

    public $firstName;
    public $lastName;
    public $test;

    public static function tableName()
    {
        return static::$tableName ?: 'singer';
    }

    public function rules()
    {
        return [
            [['lastName'], 'default', 'value' => 'Lennon'],
            [['lastName'], 'required'],
            [['lastName'], 'string', 'max' => 25],
            [['underscore_style'], 'yii\captcha\CaptchaValidator'],
            [['test'], 'required', 'when' => static function ($model) { return $model->firstName === 'cebe'; }],
        ];
    }
}
