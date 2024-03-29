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
use yii\validators\StringValidator;
use yiiunit\data\validators\models\FakedValidationModel;

/**
 * @group validators
 */
class StringValidatorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // destroy application, Validator must work without Yii::$app
        $this->destroyApplication();
    }

    public function testValidateValue(): void
    {
        $val = new StringValidator();
        $this->assertFalse($val->validate(['not a string']));
        $this->assertTrue($val->validate('Just some string'));
        $this->assertFalse($val->validate(true));
        $this->assertFalse($val->validate(false));
    }

    public function testValidateValueLength(): void
    {
        $val = new StringValidator(['length' => 25]);
        $this->assertTrue($val->validate(str_repeat('x', 25)));
        $this->assertTrue($val->validate(str_repeat('€', 25)));
        $this->assertFalse($val->validate(str_repeat('x', 125)));
        $this->assertFalse($val->validate(''));
        $val = new StringValidator(['length' => [25]]);
        $this->assertTrue($val->validate(str_repeat('x', 25)));
        $this->assertTrue($val->validate(str_repeat('x', 1250)));
        $this->assertFalse($val->validate(str_repeat('Ä', 24)));
        $this->assertFalse($val->validate(''));
        $val = new StringValidator(['length' => [10, 20]]);
        $this->assertTrue($val->validate(str_repeat('x', 15)));
        $this->assertTrue($val->validate(str_repeat('x', 10)));
        $this->assertTrue($val->validate(str_repeat('x', 20)));
        $this->assertFalse($val->validate(str_repeat('x', 5)));
        $this->assertFalse($val->validate(str_repeat('x', 25)));
        $this->assertFalse($val->validate(''));
        // make sure min/max are overridden
        $val = new StringValidator(['length' => [10, 20], 'min' => 25, 'max' => 35]);
        $this->assertTrue($val->validate(str_repeat('x', 15)));
        $this->assertFalse($val->validate(str_repeat('x', 30)));
    }

    public function testValidateValueMinMax(): void
    {
        $val = new StringValidator(['min' => 10]);
        $this->assertTrue($val->validate(str_repeat('x', 10)));
        $this->assertFalse($val->validate('xxxx'));
        $val = new StringValidator(['max' => 10]);
        $this->assertTrue($val->validate('xxxx'));
        $this->assertFalse($val->validate(str_repeat('y', 20)));
        $val = new StringValidator(['min' => 10, 'max' => 20]);
        $this->assertTrue($val->validate(str_repeat('y', 15)));
        $this->assertFalse($val->validate('abc'));
        $this->assertFalse($val->validate(str_repeat('b', 25)));
    }

    public function testValidateAttribute(): void
    {
        $val = new StringValidator();
        $model = new FakedValidationModel();
        $model->attr_string = 'a tet string';
        $val->validateAttribute($model, 'attr_string');
        $this->assertFalse($model->hasErrors());
        $model->attr_string = true;
        $val->validateAttribute($model, 'attr_string');
        $this->assertTrue($model->hasErrors());
        $model->attr_string = false;
        $val->validateAttribute($model, 'attr_string');
        $this->assertTrue($model->hasErrors());
        $val = new StringValidator(['length' => 20]);
        $model = new FakedValidationModel();
        $model->attr_string = str_repeat('x', 20);
        $val->validateAttribute($model, 'attr_string');
        $this->assertFalse($model->hasErrors());
        $model = new FakedValidationModel();
        $model->attr_string = 'abc';
        $val->validateAttribute($model, 'attr_string');
        $this->assertTrue($model->hasErrors('attr_string'));
        $val = new StringValidator(['max' => 2]);
        $model = new FakedValidationModel();
        $model->attr_string = 'a';
        $val->validateAttribute($model, 'attr_string');
        $this->assertFalse($model->hasErrors());
        $model = new FakedValidationModel();
        $model->attr_string = 'abc';
        $val->validateAttribute($model, 'attr_string');
        $this->assertTrue($model->hasErrors('attr_string'));
        $val = new StringValidator(['max' => 1]);
        $model = FakedValidationModel::createWithAttributes(['attr_str' => ['abc']]);
        $val->validateAttribute($model, 'attr_str');
        $this->assertTrue($model->hasErrors('attr_str'));
    }

    public function testEnsureMessagesOnInit(): void
    {
        $val = new StringValidator(['min' => 1, 'max' => 2]);
        $this->assertIsString($val->message);
        $this->assertIsString($val->tooLong);
        $this->assertIsString($val->tooShort);
    }

    public function testCustomErrorMessageInValidateAttribute(): void
    {
        $val = new StringValidator([
            'min' => 5,
            'tooShort' => '{attribute} to short. Min is {min}',
        ]);
        $model = new FakedValidationModel();
        $model->attr_string = 'abc';
        $val->validateAttribute($model, 'attr_string');
        $this->assertTrue($model->hasErrors('attr_string'));
        $errorMsg = $model->getErrors('attr_string');
        $this->assertEquals('attr_string to short. Min is 5', $errorMsg[0]);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13327
     */
    public function testValidateValueInNonStrictMode(): void
    {
        $val = new StringValidator();
        $val->strict = false;

        // string
        $this->assertTrue($val->validate('Just some string'));

        // non-scalar
        $this->assertFalse($val->validate(['array']));
        $this->assertFalse($val->validate(new stdClass()));
        $this->assertFalse($val->validate(null));

        // bool
        $this->assertTrue($val->validate(true));
        $this->assertTrue($val->validate(false));

        // number
        $this->assertTrue($val->validate(42));
        $this->assertTrue($val->validate(36.6));
    }
}
