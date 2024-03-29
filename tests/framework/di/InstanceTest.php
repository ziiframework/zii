<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\di;

use Yii;
use yii\di\Instance;
use yii\di\Container;
use yiiunit\TestCase;
use yii\db\Connection;
use yii\base\Component;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 *
 * @group di
 */
class InstanceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Yii::$container = new Container();
    }

    public function testOf(): void
    {
        $container = new Container();
        $className = Component::className();
        $instance = Instance::of($className);

        $this->assertInstanceOf('\\yii\\di\\Instance', $instance);
        $this->assertInstanceOf(Component::className(), $instance->get($container));
        $this->assertInstanceOf(Component::className(), Instance::ensure($instance, $className, $container));
        $this->assertNotSame($instance->get($container), Instance::ensure($instance, $className, $container));
    }

    public function testEnsure(): void
    {
        $container = new Container();
        $container->set('db', [
            'class' => 'yii\db\Connection',
            'dsn' => 'test',
        ]);

        $this->assertInstanceOf(Connection::className(), Instance::ensure('db', 'yii\db\Connection', $container));
        $this->assertInstanceOf(Connection::className(), Instance::ensure(new Connection(), 'yii\db\Connection', $container));
        $this->assertInstanceOf('\\yii\\db\\Connection', Instance::ensure(['class' => 'yii\db\Connection', 'dsn' => 'test'], 'yii\db\Connection', $container));
    }

    /**
     * ensure an InvalidConfigException is thrown when a component does not exist.
     */
    public function testEnsureNonExistingComponentException(): void
    {
        $container = new Container();
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessageMatches('/^Failed to instantiate component or class/i');
        Instance::ensure('cache', 'yii\cache\Cache', $container);
    }

    /**
     * ensure an InvalidConfigException is thrown when a class does not exist.
     */
    public function testEnsureNonExistingClassException(): void
    {
        $container = new Container();
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessageMatches('/^Failed to instantiate component or class/i');
        Instance::ensure('yii\cache\DoesNotExist', 'yii\cache\Cache', $container);
    }

    public function testEnsureWithoutType(): void
    {
        $container = new Container();
        $container->set('db', [
            'class' => 'yii\db\Connection',
            'dsn' => 'test',
        ]);

        $this->assertInstanceOf(Connection::className(), Instance::ensure('db', null, $container));
        $this->assertInstanceOf(Connection::className(), Instance::ensure(new Connection(), null, $container));
        $this->assertInstanceOf('\\yii\\db\\Connection', Instance::ensure(['class' => 'yii\db\Connection', 'dsn' => 'test'], null, $container));
    }

    public function testEnsureMinimalSettings(): void
    {
        Yii::$container->set('db', [
            'class' => 'yii\db\Connection',
            'dsn' => 'test',
        ]);

        $this->assertInstanceOf(Connection::className(), Instance::ensure('db'));
        $this->assertInstanceOf(Connection::className(), Instance::ensure(new Connection()));
        $this->assertInstanceOf(Connection::className(), Instance::ensure(['class' => 'yii\db\Connection', 'dsn' => 'test']));
        Yii::$container = new Container();
    }

    public function testExceptionRefersTo(): void
    {
        $container = new Container();
        $container->set('db', [
            'class' => 'yii\db\Connection',
            'dsn' => 'test',
        ]);

        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('"db" refers to a yii\db\Connection component. yii\base\Widget is expected.');

        Instance::ensure('db', 'yii\base\Widget', $container);
    }

    public function testExceptionInvalidDataType(): void
    {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('Invalid data type: yii\db\Connection. yii\base\Widget is expected.');
        Instance::ensure(new Connection(), 'yii\base\Widget');
    }

    public function testExceptionComponentIsNotSpecified(): void
    {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('The required component is not specified.');
        Instance::ensure('');
    }

    public function testGet(): void
    {
        $this->mockApplication([
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'test',
                ],
            ],
        ]);

        $container = Instance::of('db');

        $this->assertInstanceOf(Connection::className(), $container->get());

        $this->destroyApplication();
    }

    /**
     * This tests the usage example given in yii\di\Instance class PHPDoc.
     */
    public function testLazyInitializationExample(): void
    {
        Yii::$container = new Container();
        Yii::$container->set('cache', [
            'class' => 'yii\caching\DbCache',
            'db' => Instance::of('db'),
        ]);
        Yii::$container->set('db', [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite:path/to/file.db',
        ]);

        $this->assertInstanceOf('yii\caching\DbCache', $cache = Yii::$container->get('cache'));
        $this->assertInstanceOf('yii\db\Connection', $db = $cache->db);
        $this->assertEquals('sqlite:path/to/file.db', $db->dsn);
    }

    public function testRestoreAfterVarExport(): void
    {
        $instance = Instance::of('something');
        $export = var_export($instance, true);

        $this->assertMatchesRegularExpression('~yii\\\\di\\\\Instance::__set_state\(array\(\s+\'id\' => \'something\',\s+\'optional\' => false,\s+\)\)~', $export);

        $this->assertEquals($instance, Instance::__set_state([
            'id' => 'something',
        ]));
    }

    public function testRestoreAfterVarExportRequiresId(): void
    {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('Failed to instantiate class "Instance". Required parameter "id" is missing');

        Instance::__set_state([]);
    }

    public function testExceptionInvalidDataTypeInArray(): void
    {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('Invalid data type: yii\db\Connection. yii\base\Widget is expected.');
        Instance::ensure([
            'class' => Connection::className(),
        ], 'yii\base\Widget');
    }
}
