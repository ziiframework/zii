<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\validators;

use stdClass;
use yiiunit\TestCase;
use yii\validators\DefaultValueValidator;

/**
 * @group validators
 */
class DefaultValueValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // destroy application, Validator must work without Yii::$app
        $this->destroyApplication();
    }

    public function testValidateAttribute(): void
    {
        $val = new DefaultValueValidator();
        $val->value = 'test_value';
        $obj = new stdClass();
        $obj->attrA = 'attrA';
        $obj->attrB = null;
        $obj->attrC = '';
        // original values to chek which attritubes where modified
        $objB = clone $obj;
        $val->validateAttribute($obj, 'attrB');
        $this->assertEquals($val->value, $obj->attrB);
        $this->assertEquals($objB->attrA, $obj->attrA);
        $val->value = 'new_test_value';
        $obj = clone $objB; // get clean object
        $val->validateAttribute($obj, 'attrC');
        $this->assertEquals('new_test_value', $obj->attrC);
        $this->assertEquals($objB->attrA, $obj->attrA);
        $val->validateAttribute($obj, 'attrA');
        $this->assertEquals($objB->attrA, $obj->attrA);
    }
}
