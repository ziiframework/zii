<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\console\controllers;

use Yii;
use yiiunit\data\ar\ActiveRecord;
use yiiunit\framework\db\DatabaseTestCase;
use yii\console\controllers\FixtureController;
use yiiunit\data\console\controllers\fixtures\FixtureStorage;
use yiiunit\data\console\controllers\fixtures\DependentActiveFixture;

/**
 * Unit test for [[\yii\console\controllers\FixtureController]].
 *
 * @see FixtureController
 *
 * @group console
 * @group db
 */
class FixtureControllerTest extends DatabaseTestCase
{
    /**
     * @var \yiiunit\framework\console\controllers\FixtureConsoledController
     */
    private $_fixtureController;

    protected $driverName = 'mysql';

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->getConnection();
        Yii::$app->set('db', $db);
        ActiveRecord::$db = $db;

        $this->_fixtureController = Yii::createObject([
            'class' => 'yiiunit\framework\console\controllers\FixtureConsoledController',
            'interactive' => false,
            'globalFixtures' => [],
            'namespace' => 'yiiunit\data\console\controllers\fixtures',
        ], [null, null]); // id and module are null
    }

    protected function tearDown(): void
    {
        $this->_fixtureController = null;
        FixtureStorage::clear();

        parent::tearDown();
    }

    public function testLoadGlobalFixture(): void
    {
        $this->_fixtureController->globalFixtures = [
            '\yiiunit\data\console\controllers\fixtures\Global',
        ];

        $this->_fixtureController->actionLoad(['First']);

        $this->assertCount(1, FixtureStorage::$globalFixturesData, 'global fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');
    }

    public function testLoadGlobalFixtureWithFixture(): void
    {
        $this->_fixtureController->globalFixtures = [
            '\yiiunit\data\console\controllers\fixtures\GlobalFixture',
        ];

        $this->_fixtureController->actionLoad(['First']);

        $this->assertCount(1, FixtureStorage::$globalFixturesData, 'global fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');
    }

    public function testUnloadGlobalFixture(): void
    {
        $this->_fixtureController->globalFixtures = [
            '\yiiunit\data\console\controllers\fixtures\Global',
        ];

        FixtureStorage::$globalFixturesData[] = 'some seeded global fixture data';
        FixtureStorage::$firstFixtureData[] = 'some seeded first fixture data';

        $this->assertCount(1, FixtureStorage::$globalFixturesData, 'global fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');

        $this->_fixtureController->actionUnload(['First']);

        $this->assertEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should be unloaded');
    }

    public function testLoadAll(): void
    {
        $this->assertEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should be empty');
        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should be empty');
        $this->assertEmpty(FixtureStorage::$secondFixtureData, 'second fixture data should be empty');
        $this->assertEmpty(FixtureStorage::$subdirFirstFixtureData, 'subdir / first fixture data should be empty');
        $this->assertEmpty(FixtureStorage::$subdirSecondFixtureData, 'subdir / second fixture data should be empty');

        $this->_fixtureController->actionLoad(['*']);

        $this->assertCount(1, FixtureStorage::$globalFixturesData, 'global fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$secondFixtureData, 'second fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$subdirFirstFixtureData, 'subdir / first fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$subdirSecondFixtureData, 'subdir / second fixture data should be loaded');
    }

    public function testUnloadAll(): void
    {
        FixtureStorage::$globalFixturesData[] = 'some seeded global fixture data';
        FixtureStorage::$firstFixtureData[] = 'some seeded first fixture data';
        FixtureStorage::$secondFixtureData[] = 'some seeded second fixture data';
        FixtureStorage::$subdirFirstFixtureData[] = 'some seeded subdir/first fixture data';
        FixtureStorage::$subdirSecondFixtureData[] = 'some seeded subdir/second fixture data';

        $this->assertCount(1, FixtureStorage::$globalFixturesData, 'global fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$secondFixtureData, 'second fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$subdirFirstFixtureData, 'subdir/first fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$subdirSecondFixtureData, 'subdir/second fixture data should be loaded');

        $this->_fixtureController->actionUnload(['*']);

        $this->assertEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$secondFixtureData, 'second fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$subdirFirstFixtureData, 'subdir/first fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$subdirSecondFixtureData, 'subdir/second fixture data should be unloaded');
    }

    public function testLoadParticularExceptOnes(): void
    {
        $this->_fixtureController->actionLoad(['First', 'subdir/First', '-Second', '-Global', '-subdir/Second']);

        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$subdirFirstFixtureData, 'subdir/first fixture data should be loaded');
        $this->assertEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should not be loaded');
        $this->assertEmpty(FixtureStorage::$secondFixtureData, 'second fixture data should not be loaded');
        $this->assertEmpty(FixtureStorage::$subdirSecondFixtureData, 'subdir/second fixture data should not be loaded');
    }

    public function testUnloadParticularExceptOnes(): void
    {
        FixtureStorage::$globalFixturesData[] = 'some seeded global fixture data';
        FixtureStorage::$firstFixtureData[] = 'some seeded first fixture data';
        FixtureStorage::$secondFixtureData[] = 'some seeded second fixture data';
        FixtureStorage::$subdirFirstFixtureData[] = 'some seeded subdir/first fixture data';
        FixtureStorage::$subdirSecondFixtureData[] = 'some seeded subdir/second fixture data';

        $this->_fixtureController->actionUnload([
            'First',
            'subdir/First',
            '-Second',
            '-Global',
            '-subdir/Second',
        ]);

        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$subdirFirstFixtureData, 'subdir/first fixture data should be unloaded');
        $this->assertNotEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should not be unloaded');
        $this->assertNotEmpty(FixtureStorage::$secondFixtureData, 'second fixture data should not be unloaded');
        $this->assertNotEmpty(FixtureStorage::$subdirSecondFixtureData, 'subdir/second fixture data should not be unloaded');
    }

    public function testLoadAllExceptOnes(): void
    {
        $this->_fixtureController->actionLoad(['*', '-Second', '-Global', '-subdir/First']);

        $this->assertCount(1, FixtureStorage::$firstFixtureData, 'first fixture data should be loaded');
        $this->assertCount(1, FixtureStorage::$subdirSecondFixtureData, 'subdir/second fixture data should be loaded');
        $this->assertEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should not be loaded');
        $this->assertEmpty(FixtureStorage::$secondFixtureData, 'second fixture data should not be loaded');
        $this->assertEmpty(FixtureStorage::$subdirFirstFixtureData, 'subdir/first fixture data should not be loaded');
    }

    public function testUnloadAllExceptOnes(): void
    {
        FixtureStorage::$globalFixturesData[] = 'some seeded global fixture data';
        FixtureStorage::$firstFixtureData[] = 'some seeded first fixture data';
        FixtureStorage::$secondFixtureData[] = 'some seeded second fixture data';
        FixtureStorage::$subdirFirstFixtureData[] = 'some seeded subdir/first fixture data';
        FixtureStorage::$subdirSecondFixtureData[] = 'some seeded subdir/second fixture data';

        $this->_fixtureController->actionUnload(['*', '-Second', '-Global', '-subdir/First']);

        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should be unloaded');
        $this->assertEmpty(FixtureStorage::$subdirSecondFixtureData, 'subdir/second fixture data should be unloaded');
        $this->assertNotEmpty(FixtureStorage::$globalFixturesData, 'global fixture data should not be unloaded');
        $this->assertNotEmpty(FixtureStorage::$secondFixtureData, 'second fixture data should not be unloaded');
        $this->assertNotEmpty(FixtureStorage::$subdirFirstFixtureData, 'subdir/first fixture data should not be unloaded');
    }

    public function testNothingToLoadParticularExceptOnes(): void
    {
        $this->_fixtureController->actionLoad(['First', '-First']);

        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should not be loaded');
    }

    public function testNothingToUnloadParticularExceptOnes(): void
    {
        $this->_fixtureController->actionUnload(['First', '-First']);

        $this->assertEmpty(FixtureStorage::$firstFixtureData, 'first fixture data should not be loaded');
    }

    public function testNoFixturesWereFoundInLoad(): void
    {
        $this->expectException(\yii\console\Exception::class);

        $this->_fixtureController->actionLoad(['NotExistingFixture']);
    }

    public function testNoFixturesWereFoundInUnload(): void
    {
        $this->expectException(\yii\console\Exception::class);

        $this->_fixtureController->actionUnload(['NotExistingFixture']);
    }

    public function testLoadActiveFixtureSequence(): void
    {
        $this->assertEmpty(FixtureStorage::$activeFixtureSequence, 'Active fixture sequence should be empty.');

        $this->_fixtureController->actionLoad(['*']);

        $lastFixture = end(FixtureStorage::$activeFixtureSequence);

        $this->assertEquals(DependentActiveFixture::className(), $lastFixture);
    }
}

class FixtureConsoledController extends FixtureController
{
    public function stdout($string): void
    {
    }
}
