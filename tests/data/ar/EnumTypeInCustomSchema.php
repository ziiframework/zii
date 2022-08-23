<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\ar;

use yii\db\ArrayExpression;

/**
 * Class EnumTypeInCustomSchema.
 *
 * @property int             $id
 * @property ArrayExpression $test_type
 */
class EnumTypeInCustomSchema extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%schema2.custom_type_test_table}}';
    }
}
