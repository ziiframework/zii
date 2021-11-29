<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use yiiunit\data\ar\TestTrigger;
use yiiunit\data\ar\TestTriggerAlert;

/**
 * @group db
 * @group mssql
 *
 * @internal
 * @coversNothing
 */
class ActiveRecordTest extends \yiiunit\framework\db\ActiveRecordTest
{
    public $driverName = 'sqlsrv';

    public function testExplicitPkOnAutoIncrement(): void
    {
        $this->markTestSkipped('MSSQL does not support explicit value for an IDENTITY column.');
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testSaveWithTrigger(): void
    {
        $db = $this->getConnection();

        // drop trigger if exist
        $sql = 'IF (OBJECT_ID(N\'[dbo].[test_alert]\') IS NOT NULL)
BEGIN
      DROP TRIGGER [dbo].[test_alert];
END';
        $db->createCommand($sql)->execute();

        // create trigger
        $sql = 'CREATE TRIGGER [dbo].[test_alert] ON [dbo].[test_trigger]
AFTER INSERT
AS
BEGIN
    INSERT INTO [dbo].[test_trigger_alert] ( [stringcol] )
    SELECT [stringcol]
    FROM [inserted]
END';
        $db->createCommand($sql)->execute();

        $record = new TestTrigger();
        $record->stringcol = 'test';
        $this->assertTrue($record->save(false));
        $this->assertSame(1, $record->id);

        $testRecord = TestTriggerAlert::findOne(1);
        $this->assertSame('test', $testRecord->stringcol);
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testSaveWithComputedColumn(): void
    {
        $db = $this->getConnection();

        $sql = 'IF OBJECT_ID(\'TESTFUNC\') IS NOT NULL EXEC(\'DROP FUNCTION TESTFUNC\')';
        $db->createCommand($sql)->execute();

        $sql = 'CREATE FUNCTION TESTFUNC(@Number INT)
RETURNS VARCHAR(15)
AS
BEGIN
      RETURN (SELECT CONVERT(VARCHAR(15),@Number))
END';
        $db->createCommand($sql)->execute();

        $sql = 'ALTER TABLE [dbo].[test_trigger] ADD [computed_column] AS dbo.TESTFUNC([ID])';
        $db->createCommand($sql)->execute();

        $record = new TestTrigger();
        $record->stringcol = 'test';
        $this->assertTrue($record->save(false));
        $this->assertSame(1, $record->id);
    }
}
