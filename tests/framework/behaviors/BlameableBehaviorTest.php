<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\behaviors;

use Yii;
use yii\base\BaseObject;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yiiunit\TestCase;

/**
 * Unit test for [[\yii\behaviors\BlameableBehavior]].
 *
 * @group behaviors
 */
class BlameableBehaviorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
            static::markTestSkipped('PDO and SQLite extensions are required.');
        }
    }

    protected function setUp(): void
    {
        $this->mockApplication([
            'components' => [
                'db' => [
                    'class' => '\yii\db\Connection',
                    'dsn'   => 'sqlite::memory:',
                ],
                'user' => [
                    'class' => 'yiiunit\framework\behaviors\UserMock',
                ],
            ],
        ]);

        $columns = [
            'name'       => 'string',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
        Yii::$app->getDb()->createCommand()->createTable('test_blame', $columns)->execute();

        $this->getUser()->login(10);
    }

    protected function tearDown(): void
    {
        Yii::$app->getDb()->close();
        parent::tearDown();
        gc_enable();
        gc_collect_cycles();
    }

    public function testInsertUserIsGuest(): void
    {
        $this->getUser()->logout();

        $model       = new ActiveRecordBlameable();
        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertNull($model->created_by);
        $this->assertNull($model->updated_by);
    }

    public function testInsertUserIsNotGuest(): void
    {
        $model       = new ActiveRecordBlameable();
        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertEquals(10, $model->created_by);
        $this->assertEquals(10, $model->updated_by);
    }

    public function testUpdateUserIsNotGuest(): void
    {
        $model       = new ActiveRecordBlameable();
        $model->name = __METHOD__;
        $model->save();

        $this->getUser()->login(20);
        $model       = ActiveRecordBlameable::findOne(['name' => __METHOD__]);
        $model->name = __CLASS__;
        $model->save();

        $this->assertEquals(10, $model->created_by);
        $this->assertEquals(20, $model->updated_by);
    }

    public function testInsertCustomValue(): void
    {
        $model                        = new ActiveRecordBlameable();
        $model->name                  = __METHOD__;
        $model->getBlameable()->value = 42;
        $model->beforeSave(true);

        $this->assertEquals(42, $model->created_by);
        $this->assertEquals(42, $model->updated_by);
    }

    public function testInsertClosure(): void
    {
        $model                        = new ActiveRecordBlameable();
        $model->name                  = __METHOD__;
        $model->getBlameable()->value = static function ($event)
        {
            return strlen($event->sender->name); // $model->name;
        };
        $model->beforeSave(true);

        $this->assertEquals(strlen($model->name), $model->created_by);
        $this->assertEquals(strlen($model->name), $model->updated_by);
    }

    public function testCustomAttributesAndEvents(): void
    {
        $model = new ActiveRecordBlameable([
            'as blameable' => [
                'class'      => BlameableBehavior::className(),
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'created_by',
                    BaseActiveRecord::EVENT_BEFORE_INSERT   => ['created_by', 'updated_by'],
                ],
            ],
        ]);
        $model->name = __METHOD__;

        $this->assertNull($model->created_by);
        $this->assertNull($model->updated_by);

        $model->beforeValidate();
        $this->assertEquals(10, $model->created_by);
        $this->assertNull($model->updated_by);

        $this->getUser()->login(20);
        $model->beforeSave(true);
        $this->assertEquals(20, $model->created_by);
        $this->assertEquals(20, $model->updated_by);
    }

    public function testDefaultValue(): void
    {
        $this->getUser()->logout();

        $model = new ActiveRecordBlameable([
            'as blameable' => [
                'class'        => BlameableBehavior::className(),
                'defaultValue' => 2,
            ],
        ]);

        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertEquals(2, $model->created_by);
        $this->assertEquals(2, $model->updated_by);
    }

    public function testDefaultValueWithClosure(): void
    {
        $model       = new ActiveRecordBlameableWithDefaultValueClosure();
        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->getUser()->logout();
        $model->beforeSave(true);

        $this->assertEquals(11, $model->created_by);
        $this->assertEquals(11, $model->updated_by);
    }

    /**
     * @return UserMock
     */
    private function getUser()
    {
        return Yii::$app->get('user');
    }
}

class ActiveRecordBlameableWithDefaultValueClosure extends ActiveRecordBlameable
{
    public function behaviors()
    {
        return [
            'blameable' => [
                'class'        => BlameableBehavior::className(),
                'defaultValue' => function ()
                {
                    return $this->created_by + 1;
                },
            ],
        ];
    }
}

/**
 * Test Active Record class with [[BlameableBehavior]] behavior attached.
 *
 * @property string            $name
 * @property int               $created_by
 * @property int               $updated_by
 * @property BlameableBehavior $blameable
 */
class ActiveRecordBlameable extends ActiveRecord
{
    public static function tableName()
    {
        return 'test_blame';
    }

    public static function primaryKey()
    {
        return ['name'];
    }

    public function behaviors()
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::className(),
            ],
        ];
    }

    /**
     * @return BlameableBehavior
     */
    public function getBlameable()
    {
        return $this->getBehavior('blameable');
    }
}

class UserMock extends BaseObject
{
    public $id;

    public $isGuest = true;

    public function login($id): void
    {
        $this->isGuest = false;
        $this->id      = $id;
    }

    public function logout(): void
    {
        $this->isGuest = true;
        $this->id      = null;
    }
}
