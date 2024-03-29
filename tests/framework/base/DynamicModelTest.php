<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use yiiunit\TestCase;
use yii\base\DynamicModel;

/**
 * @group base
 */
class DynamicModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    public function testValidateData(): void
    {
        $email = 'invalid';
        $name = 'long name';
        $age = '';
        $model = DynamicModel::validateData(compact('name', 'email', 'age'), [
            [['email', 'name', 'age'], 'required'],
            ['email', 'email'],
            ['name', 'string', 'max' => 3],
        ]);
        $this->assertTrue($model->hasErrors());
        $this->assertTrue($model->hasErrors('email'));
        $this->assertTrue($model->hasErrors('name'));
        $this->assertTrue($model->hasErrors('age'));
    }

    public function testAddRule(): void
    {
        $model = new DynamicModel();
        $this->assertEquals(0, $model->getValidators()->count());
        $model->addRule('name', 'string', ['min' => 12]);
        $this->assertEquals(1, $model->getValidators()->count());
        $model->addRule('email', 'email');
        $this->assertEquals(2, $model->getValidators()->count());
        $model->addRule(['name', 'email'], 'required');
        $this->assertEquals(3, $model->getValidators()->count());
    }

    public function testValidateWithAddRule(): void
    {
        $email = 'invalid';
        $name = 'long name';
        $age = '';
        $model = new DynamicModel(compact('name', 'email', 'age'));
        $model->addRule(['email', 'name', 'age'], 'required')
            ->addRule('email', 'email')
            ->addRule('name', 'string', ['max' => 3])
            ->validate();
        $this->assertTrue($model->hasErrors());
        $this->assertTrue($model->hasErrors('email'));
        $this->assertTrue($model->hasErrors('name'));
        $this->assertTrue($model->hasErrors('age'));
    }

    public function testDynamicProperty(): void
    {
        $email = 'invalid';
        $name = 'long name';
        $model = new DynamicModel(compact('name', 'email'));
        $this->assertEquals($email, $model->email);
        $this->assertEquals($name, $model->name);
        $this->assertTrue($model->canGetProperty('email'));
        $this->assertTrue($model->canGetProperty('name'));
        $this->assertTrue($model->canSetProperty('email'));
        $this->assertTrue($model->canSetProperty('name'));
        $this->expectException('yii\base\UnknownPropertyException');
        $age = $model->age;
    }

    public function testLoad(): void
    {
        $dynamic = new DynamicModel();
        // define two attributes
        $dynamic->defineAttribute('name');
        $dynamic->defineAttribute('mobile');
        // define rule
        $dynamic->addRule(['name', 'mobile'], 'required');
        // define your sample data
        $data = [
            'DynamicModel' => [
                'name' => $name = 'your name 2',
                'mobile' => $mobile = 'my number mobile',
            ],
        ];
        // load data
        $this->assertFalse($dynamic->load([]));
        $this->assertTrue($dynamic->load($data));

        $this->assertTrue($dynamic->validate());
        $this->assertEquals($name, $dynamic->name);
        $this->assertEquals($mobile, $dynamic->mobile);
    }
}
