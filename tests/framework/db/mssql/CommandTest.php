<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use yii\db\Query;

/**
 * @group db
 * @group mssql
 *
 * @internal
 * @coversNothing
 */
class CommandTest extends \yiiunit\framework\db\CommandTest
{
    protected $driverName = 'sqlsrv';

    public function testAutoQuoting(): void
    {
        $db = $this->getConnection(false);

        $sql = 'SELECT [[id]], [[t.name]] FROM {{customer}} t';
        $command = $db->createCommand($sql);
        $this->assertSame('SELECT [id], [t].[name] FROM [customer] t', $command->sql);
    }

    public function testPrepareCancel(): void
    {
        $this->markTestSkipped('MSSQL driver does not support this feature.');
    }

    public function testBindParamValue(): void
    {
        $db = $this->getConnection();

        // bindParam
        $sql = 'INSERT INTO customer(email, name, address) VALUES (:email, :name, :address)';
        $command = $db->createCommand($sql);
        $email = 'user4@example.com';
        $name = 'user4';
        $address = 'address4';
        $command->bindParam(':email', $email);
        $command->bindParam(':name', $name);
        $command->bindParam(':address', $address);
        $command->execute();

        $sql = 'SELECT name FROM customer WHERE email=:email';
        $command = $db->createCommand($sql);
        $command->bindParam(':email', $email);
        $this->assertSame($name, $command->queryScalar());

        $sql = 'INSERT INTO type (int_col, char_col, float_col, blob_col, numeric_col, bool_col) VALUES (:int_col, :char_col, :float_col, CONVERT([varbinary], :blob_col), :numeric_col, :bool_col)';
        $command = $db->createCommand($sql);
        $intCol = 123;
        $charCol = 'abc';
        $floatCol = 1.23;
        $blobCol = "\x10\x11\x12";
        $numericCol = '1.23';
        $boolCol = false;
        $command->bindParam(':int_col', $intCol);
        $command->bindParam(':char_col', $charCol);
        $command->bindParam(':float_col', $floatCol);
        $command->bindParam(':blob_col', $blobCol);
        $command->bindParam(':numeric_col', $numericCol);
        $command->bindParam(':bool_col', $boolCol);
        $this->assertSame(1, $command->execute());

        $sql = 'SELECT int_col, char_col, float_col, CONVERT([nvarchar], blob_col) AS blob_col, numeric_col FROM type';
        $row = $db->createCommand($sql)->queryOne();
        $this->assertSame($intCol, $row['int_col']);
        $this->assertSame($charCol, trim($row['char_col']));
        $this->assertContains($row['float_col'], [$floatCol, sprintf('%.3f', $floatCol)]);
        $this->assertSame($blobCol, $row['blob_col']);
        $this->assertSame($numericCol, $row['numeric_col']);

        // bindValue
        $sql = 'INSERT INTO customer(email, name, address) VALUES (:email, \'user5\', \'address5\')';
        $command = $db->createCommand($sql);
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();

        $sql = 'SELECT email FROM customer WHERE name=:name';
        $command = $db->createCommand($sql);
        $command->bindValue(':name', 'user5');
        $this->assertSame('user5@example.com', $command->queryScalar());
    }

    public function paramsNonWhereProvider()
    {
        return [
            ['SELECT SUBSTRING(name, :len, 6) AS name FROM {{customer}} WHERE [[email]] = :email GROUP BY name'],
            ['SELECT SUBSTRING(name, :len, 6) as name FROM {{customer}} WHERE [[email]] = :email ORDER BY name'],
            ['SELECT SUBSTRING(name, :len, 6) FROM {{customer}} WHERE [[email]] = :email'],
        ];
    }

    public function testAddDropDefaultValue(): void
    {
        $db = $this->getConnection(false);
        $tableName = 'test_def';
        $name = 'test_def_constraint';
        /** @var \yii\db\pgsql\Schema $schema */
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }
        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer',
        ])->execute();

        $this->assertEmpty($schema->getTableDefaultValues($tableName, true));
        $db->createCommand()->addDefaultValue($name, $tableName, 'int1', 41)->execute();
        $this->assertMatchesRegularExpression('/^.*41.*$/', $schema->getTableDefaultValues($tableName, true)[0]->value);

        $db->createCommand()->dropDefaultValue($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableDefaultValues($tableName, true));
    }

    public function batchInsertSqlProvider()
    {
        $data = parent::batchInsertSqlProvider();
        $data['issue11242']['expected'] = 'INSERT INTO [type] ([int_col], [float_col], [char_col]) VALUES (NULL, NULL, \'Kyiv {{city}}, Ukraine\')';
        $data['wrongBehavior']['expected'] = 'INSERT INTO [type] ([type].[int_col], [float_col], [char_col]) VALUES (\'\', \'\', \'Kyiv {{city}}, Ukraine\')';
        $data['batchInsert binds params from expression']['expected'] = 'INSERT INTO [type] ([int_col]) VALUES (:qp1)';
        unset($data['batchIsert empty rows represented by ArrayObject']);

        return $data;
    }

    public function testUpsertVarbinary(): void
    {
        $db = $this->getConnection();

        $testData = json_encode(['test' => 'string', 'test2' => 'integer']);
        $params = [];

        $qb = $db->getQueryBuilder();
        $sql = $qb->upsert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData], ['blob_col' => $testData], $params);

        $result = $db->createCommand($sql, $params)->execute();

        $this->assertSame(1, $result);

        $query = (new Query())
            ->select(['convert(nvarchar(max),blob_col) as blob_col'])
            ->from('T_upsert_varbinary')
            ->where(['id' => 1])
        ;

        $resultData = $query->createCommand($db)->queryOne();
        $this->assertSame($testData, $resultData['blob_col']);
    }
}
