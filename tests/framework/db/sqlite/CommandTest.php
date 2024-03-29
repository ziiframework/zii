<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

/**
 * @group db
 * @group sqlite
 */
class CommandTest extends \yiiunit\framework\db\CommandTest
{
    protected $driverName = 'sqlite';

    public function testAutoQuoting(): void
    {
        $db = $this->getConnection(false);

        $sql = 'SELECT [[id]], [[t.name]] FROM {{customer}} t';
        $command = $db->createCommand($sql);
        $this->assertEquals('SELECT `id`, `t`.`name` FROM `customer` t', $command->sql);
    }

    /**
     * @dataProvider upsertProvider
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        if (version_compare($this->getConnection(false)->getServerVersion(), '3.8.3', '<')) {
            $this->markTestSkipped('SQLite < 3.8.3 does not support "WITH" keyword.');

            return;
        }

        parent::testUpsert($firstData, $secondData);
    }

    public function testAddDropPrimaryKey(): void
    {
        $this->markTestSkipped('SQLite does not support adding/dropping primary keys.');
    }

    public function testAddDropForeignKey(): void
    {
        $this->markTestSkipped('SQLite does not support adding/dropping foreign keys.');
    }

    public function testAddDropUnique(): void
    {
        $this->markTestSkipped('SQLite does not support adding/dropping unique constraints.');
    }

    public function testAddDropCheck(): void
    {
        $this->markTestSkipped('SQLite does not support adding/dropping check constraints.');
    }

    public function testMultiStatementSupport(): void
    {
        $db = $this->getConnection(false);
        $sql = <<<'SQL'
DROP TABLE IF EXISTS {{T_multistatement}};
CREATE TABLE {{T_multistatement}} (
    [[intcol]] INTEGER,
    [[textcol]] TEXT
);
INSERT INTO {{T_multistatement}} VALUES(41, :val1);
INSERT INTO {{T_multistatement}} VALUES(42, :val2);
SQL;
        $db->createCommand($sql, [
            'val1' => 'foo',
            'val2' => 'bar',
        ])->execute();
        $this->assertSame([
            [
                'intcol' => '41',
                'textcol' => 'foo',
            ],
            [
                'intcol' => '42',
                'textcol' => 'bar',
            ],
        ], $db->createCommand('SELECT * FROM {{T_multistatement}}')->queryAll());
        $sql = <<<'SQL'
UPDATE {{T_multistatement}} SET [[intcol]] = :newInt WHERE [[textcol]] = :val1;
DELETE FROM {{T_multistatement}} WHERE [[textcol]] = :val2;
SELECT * FROM {{T_multistatement}}
SQL;
        $this->assertSame([
            [
                'intcol' => '410',
                'textcol' => 'foo',
            ],
        ], $db->createCommand($sql, [
            'newInt' => 410,
            'val1' => 'foo',
            'val2' => 'bar',
        ])->queryAll());
    }

    public function batchInsertSqlProvider()
    {
        $parent = parent::batchInsertSqlProvider();
        unset($parent['wrongBehavior']); // Produces SQL syntax error: General error: 1 near ".": syntax error

        return $parent;
    }
}
