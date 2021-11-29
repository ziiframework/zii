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
 * Speaker.
 */
class Speaker extends Model
{
    public $firstName;
    public $lastName;

    public $customLabel;
    public $underscore_style;

    public static $formName = 'Speaker';

    protected $protectedProperty;
    private $_privateProperty;

    private $_checkedValues = [];

    public function formName()
    {
        return static::$formName;
    }

    public function attributeLabels()
    {
        return [
            'customLabel' => 'This is the custom label',
        ];
    }

    public function rules()
    {
        return [];
    }

    public function scenarios()
    {
        return [
            'test' => ['firstName', 'lastName', '!underscore_style'],
            'duplicates' => ['firstName', 'firstName', '!underscore_style', '!underscore_style'],
        ];
    }

    public function customValidatingMethod($attribute, $params, $validator, $current): void
    {
        $this->_checkedValues[] = $current;
        $this->addError($attribute, 'Custom method error');
    }

    public function getCheckedValues()
    {
        return $this->_checkedValues;
    }
}
