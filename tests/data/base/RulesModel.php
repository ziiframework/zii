<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\base;

use yii\base\Model;

/**
 * Model to test different rules combinations in ModelTest.
 */
class RulesModel extends Model
{
    public $account_id;
    public $user_id;
    public $email;
    public $name;

    public $rules = [];

    public function rules()
    {
        return $this->rules;
    }
}
