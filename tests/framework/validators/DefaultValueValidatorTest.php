<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\validators;

use stdclass;
use yii\validators\DefaultValueValidator;
use yiiunit\TestCase;

/**
 * @group validators
 *
 * @internal
 * @coversNothing
 */
final class DefaultValueValidatorTest extends TestCase
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
        $obj = new stdclass();
        $obj->attrA = 'attrA';
        $obj->attrB = null;
        $obj->attrC = '';
        // original values to chek which attritubes where modified
        $objB = clone $obj;
        $val->validateAttribute($obj, 'attrB');
        $this->assertSame($val->value, $obj->attrB);
        $this->assertSame($objB->attrA, $obj->attrA);
        $val->value = 'new_test_value';
        $obj = clone $objB; // get clean object
        $val->validateAttribute($obj, 'attrC');
        $this->assertSame('new_test_value', $obj->attrC);
        $this->assertSame($objB->attrA, $obj->attrA);
        $val->validateAttribute($obj, 'attrA');
        $this->assertSame($objB->attrA, $obj->attrA);
    }
}
