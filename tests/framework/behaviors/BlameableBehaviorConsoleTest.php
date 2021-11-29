<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\behaviors;

use function extension_loaded;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yiiunit\TestCase;

/**
 * Unit test emulating console app (without user component) for [[\yii\behaviors\BlameableBehavior]].
 *
 * @group behaviors
 *
 * @internal
 * @coversNothing
 */
final class BlameableBehaviorConsoleTest extends TestCase
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
                    'dsn' => 'sqlite::memory:',
                ],
            ],
        ]);

        $columns = [
            'name' => 'string',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
        Yii::$app->getDb()->createCommand()->createTable('test_blame', $columns)->execute();
    }

    protected function tearDown(): void
    {
        Yii::$app->getDb()->close();
        parent::tearDown();
        gc_enable();
        gc_collect_cycles();
    }

    public function testDefaultValue(): void
    {
        $model = new ActiveRecordBlameableConsole([
            'as blameable' => [
                'class' => BlameableBehavior::className(),
                'defaultValue' => 2,
            ],
        ]);

        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertSame(2, $model->created_by);
        $this->assertSame(2, $model->updated_by);
    }

    public function testDefaultValueWithClosure(): void
    {
        $model = new ActiveRecordBlameableConsoleWithDefaultValueClosure();
        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertSame(11, $model->created_by);
        $this->assertSame(11, $model->updated_by);
    }
}

class ActiveRecordBlameableConsoleWithDefaultValueClosure extends ActiveRecordBlameableConsole
{
    public function behaviors()
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'defaultValue' => static fn () => 10 + 1,
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
class ActiveRecordBlameableConsole extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::className(),
            ],
        ];
    }

    public static function tableName()
    {
        return 'test_blame';
    }

    /**
     * @return BlameableBehavior
     */
    public function getBlameable()
    {
        return $this->getBehavior('blameable');
    }

    public static function primaryKey()
    {
        return ['name'];
    }
}
