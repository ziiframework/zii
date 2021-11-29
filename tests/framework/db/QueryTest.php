<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use function in_array;
use function is_int;
use yii\caching\ArrayCache;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;
use yii\db\Schema;

abstract class QueryTest extends DatabaseTestCase
{
    use GetTablesAliasTestTrait;

    public function testSelect(): void
    {
        // default
        $query = new Query();
        $query->select('*');
        $this->assertSame(['*' => '*'], $query->select);
        $this->assertNull($query->distinct);
        $this->assertNull($query->selectOption);

        $query = new Query();
        $query->select('id, name', 'something')->distinct(true);
        $this->assertSame(['id' => 'id', 'name' => 'name'], $query->select);
        $this->assertTrue($query->distinct);
        $this->assertSame('something', $query->selectOption);

        $query = new Query();
        $query->addSelect('email');
        $this->assertSame(['email' => 'email'], $query->select);

        $query = new Query();
        $query->select('id, name');
        $query->addSelect('email');
        $this->assertSame(['id' => 'id', 'name' => 'name', 'email' => 'email'], $query->select);

        $query = new Query();
        $query->select('name, lastname');
        $query->addSelect('name');
        $this->assertSame(['name' => 'name', 'lastname' => 'lastname'], $query->select);

        $query = new Query();
        $query->addSelect(['*', 'abc']);
        $query->addSelect(['*', 'bca']);
        $this->assertSame(['*' => '*', 'abc' => 'abc', 'bca' => 'bca'], $query->select);

        $query = new Query();
        $query->addSelect(['field1 as a', 'field 1 as b']);
        $this->assertSame(['a' => 'field1', 'b' => 'field 1'], $query->select);

        $query = new Query();
        $query->addSelect(['field1 a', 'field 1 b']);
        $this->assertSame(['a' => 'field1', 'b' => 'field 1'], $query->select);

        $query = new Query();
        $query->select(['name' => 'firstname', 'lastname']);
        $query->addSelect(['firstname', 'surname' => 'lastname']);
        $query->addSelect(['firstname', 'lastname']);
        $this->assertSame(['name' => 'firstname', 'lastname' => 'lastname', 'firstname' => 'firstname', 'surname' => 'lastname'], $query->select);

        $query = new Query();
        $query->select('name, name, name as X, name as X');
        $this->assertSame(['name' => 'name', 'X' => 'name'], $query->select);

        /** @see https://github.com/yiisoft/yii2/issues/15676 */
        $query = (new Query())->select('id');
        $this->assertSame(['id' => 'id'], $query->select);
        $query->select(['id', 'brand_id']);
        $this->assertSame(['id' => 'id', 'brand_id' => 'brand_id'], $query->select);

        /** @see https://github.com/yiisoft/yii2/issues/15676 */
        $query = (new Query())->select(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)']);
        $this->assertSame(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)'], $query->select);
        $query->addSelect(['LEFT(name,7) as test']);
        $this->assertSame(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)', 'test' => 'LEFT(name,7)'], $query->select);
        $query->addSelect(['LEFT(name,7) as test']);
        $this->assertSame(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)', 'test' => 'LEFT(name,7)'], $query->select);
        $query->addSelect(['test' => 'LEFT(name,7)']);
        $this->assertSame(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)', 'test' => 'LEFT(name,7)'], $query->select);

        /** @see https://github.com/yiisoft/yii2/issues/15731 */
        $selectedCols = [
            'total_sum' => 'SUM(f.amount)',
            'in_sum' => 'SUM(IF(f.type = :type_in, f.amount, 0))',
            'out_sum' => 'SUM(IF(f.type = :type_out, f.amount, 0))',
        ];
        $query = (new Query())->select($selectedCols)->addParams([
            ':type_in' => 'in',
            ':type_out' => 'out',
            ':type_partner' => 'partner',
        ]);
        $this->assertSame($selectedCols, $query->select);
        $query->select($selectedCols);
        $this->assertSame($selectedCols, $query->select);

        /** @see https://github.com/yiisoft/yii2/issues/17384 */
        $query = new Query();
        $query->select('DISTINCT ON(tour_dates.date_from) tour_dates.date_from, tour_dates.id');
        $this->assertSame(['DISTINCT ON(tour_dates.date_from) tour_dates.date_from', 'tour_dates.id' => 'tour_dates.id'], $query->select);
    }

    public function testFrom(): void
    {
        $query = new Query();
        $query->from('user');
        $this->assertSame(['user'], $query->from);
    }

    public function testFromTableIsArrayWithExpression(): void
    {
        $query = new Query();
        $tables = new Expression('(SELECT id,name FROM user) u');
        $query->from($tables);
        $this->assertInstanceOf('\yii\db\Expression', $query->from[0]);
    }

    public function testWhere(): void
    {
        $query = new Query();
        $query->where('id = :id', [':id' => 1]);
        $this->assertSame('id = :id', $query->where);
        $this->assertSame([':id' => 1], $query->params);

        $query->andWhere('name = :name', [':name' => 'something']);
        $this->assertSame(['and', 'id = :id', 'name = :name'], $query->where);
        $this->assertSame([':id' => 1, ':name' => 'something'], $query->params);

        $query->orWhere('age = :age', [':age' => '30']);
        $this->assertSame(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->where);
        $this->assertSame([':id' => 1, ':name' => 'something', ':age' => '30'], $query->params);
    }

    public function testFilterWhereWithHashFormat(): void
    {
        $query = new Query();
        $query->filterWhere([
            'id' => 0,
            'title' => '   ',
            'author_ids' => [],
        ]);
        $this->assertSame(['id' => 0], $query->where);

        $query->andFilterWhere(['status' => null]);
        $this->assertSame(['id' => 0], $query->where);

        $query->orFilterWhere(['name' => '']);
        $this->assertSame(['id' => 0], $query->where);
    }

    public function testFilterWhereWithOperatorFormat(): void
    {
        $query = new Query();
        $condition = ['like', 'name', 'Alex'];
        $query->filterWhere($condition);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['between', 'id', null, null]);
        $this->assertSame($condition, $query->where);

        $query->orFilterWhere(['not between', 'id', null, null]);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['in', 'id', []]);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['not in', 'id', []]);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['like', 'id', '']);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['or like', 'id', '']);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['not like', 'id', '   ']);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['or not like', 'id', null]);
        $this->assertSame($condition, $query->where);

        $query->andFilterWhere(['or', ['eq', 'id', null], ['eq', 'id', []]]);
        $this->assertSame($condition, $query->where);
    }

    public function testFilterHavingWithHashFormat(): void
    {
        $query = new Query();
        $query->filterHaving([
            'id' => 0,
            'title' => '   ',
            'author_ids' => [],
        ]);
        $this->assertSame(['id' => 0], $query->having);

        $query->andFilterHaving(['status' => null]);
        $this->assertSame(['id' => 0], $query->having);

        $query->orFilterHaving(['name' => '']);
        $this->assertSame(['id' => 0], $query->having);
    }

    public function testFilterHavingWithOperatorFormat(): void
    {
        $query = new Query();
        $condition = ['like', 'name', 'Alex'];
        $query->filterHaving($condition);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['between', 'id', null, null]);
        $this->assertSame($condition, $query->having);

        $query->orFilterHaving(['not between', 'id', null, null]);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['in', 'id', []]);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['not in', 'id', []]);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['like', 'id', '']);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['or like', 'id', '']);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['not like', 'id', '   ']);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['or not like', 'id', null]);
        $this->assertSame($condition, $query->having);

        $query->andFilterHaving(['or', ['eq', 'id', null], ['eq', 'id', []]]);
        $this->assertSame($condition, $query->having);
    }

    public function testFilterRecursively(): void
    {
        $query = new Query();
        $query->filterWhere(['and', ['like', 'name', ''], ['like', 'title', ''], ['id' => 1], ['not', ['like', 'name', '']]]);
        $this->assertSame(['and', ['id' => 1]], $query->where);
    }

    /*public function testJoin()
    {
    }*/

    public function testGroup(): void
    {
        $query = new Query();
        $query->groupBy('team');
        $this->assertSame(['team'], $query->groupBy);

        $query->addGroupBy('company');
        $this->assertSame(['team', 'company'], $query->groupBy);

        $query->addGroupBy('age');
        $this->assertSame(['team', 'company', 'age'], $query->groupBy);
    }

    public function testHaving(): void
    {
        $query = new Query();
        $query->having('id = :id', [':id' => 1]);
        $this->assertSame('id = :id', $query->having);
        $this->assertSame([':id' => 1], $query->params);

        $query->andHaving('name = :name', [':name' => 'something']);
        $this->assertSame(['and', 'id = :id', 'name = :name'], $query->having);
        $this->assertSame([':id' => 1, ':name' => 'something'], $query->params);

        $query->orHaving('age = :age', [':age' => '30']);
        $this->assertSame(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->having);
        $this->assertSame([':id' => 1, ':name' => 'something', ':age' => '30'], $query->params);
    }

    public function testOrder(): void
    {
        $query = new Query();
        $query->orderBy('team');
        $this->assertSame(['team' => SORT_ASC], $query->orderBy);

        $query->addOrderBy('company');
        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_ASC], $query->orderBy);

        $query->addOrderBy('age');
        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_ASC], $query->orderBy);

        $query->addOrderBy(['age' => SORT_DESC]);
        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_DESC], $query->orderBy);

        $query->addOrderBy('age ASC, company DESC');
        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_DESC, 'age' => SORT_ASC], $query->orderBy);

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->orderBy($expression);
        $this->assertSame([$expression], $query->orderBy);

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->addOrderBy($expression);
        $this->assertSame([$expression, $expression], $query->orderBy);
    }

    public function testLimitOffset(): void
    {
        $query = new Query();
        $query->limit(10)->offset(5);
        $this->assertSame(10, $query->limit);
        $this->assertSame(5, $query->offset);
    }

    public function testLimitOffsetWithExpression(): void
    {
        $query = (new Query())->from('customer')->select('id')->orderBy('id');
        $query
            ->limit(new Expression('1 + 1'))
            ->offset(new Expression('1 + 0'))
        ;

        $columnValues = $query->column($this->getConnection());

        $this->assertCount(2, $columnValues);

        // make sure int => string for strict equals
        foreach ($columnValues as $i => $columnValue) {
            if (is_int($columnValue)) {
                $columnValues[$i] = (string) $columnValue;
            }
        }

        $this->assertNotContains('1', $columnValues);
        $this->assertContains('2', $columnValues);
        $this->assertContains('3', $columnValues);
    }

    public function testUnion(): void
    {
        $connection = $this->getConnection();
        $query = (new Query())
            ->select(['id', 'name'])
            ->from('item')
            ->limit(2)
            ->union((new Query())
                    ->select(['id', 'name'])
                    ->from(['category'])
                    ->limit(2))
        ;
        $result = $query->all($connection);
        $this->assertNotEmpty($result);
        $this->assertCount(4, $result);
    }

    public function testOne(): void
    {
        $db = $this->getConnection();

        $result = (new Query())->from('customer')->where(['[[status]]' => 2])->one($db);
        $this->assertSame('user3', $result['name']);

        $result = (new Query())->from('customer')->where(['[[status]]' => 3])->one($db);
        $this->assertFalse($result);
    }

    public function testExists(): void
    {
        $db = $this->getConnection();

        $result = (new Query())->from('customer')->where(['status' => 2])->exists($db);
        $this->assertTrue($result);

        $result = (new Query())->from('customer')->where(['status' => 3])->exists($db);
        $this->assertFalse($result);
    }

    public function testColumn(): void
    {
        $db = $this->getConnection();
        $result = (new Query())->select('name')->from('customer')->orderBy(['id' => SORT_DESC])->column($db);
        $this->assertSame(['user3', 'user2', 'user1'], $result);

        // https://github.com/yiisoft/yii2/issues/7515
        $result = (new Query())->from('customer')
            ->select('name')
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('id')
            ->column($db)
        ;
        $this->assertSame([3 => 'user3', 2 => 'user2', 1 => 'user1'], $result);

        // https://github.com/yiisoft/yii2/issues/17687
        $result = (new Query())->from('customer')
            ->select('name')
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('customer.id')
            ->column($db)
        ;
        $this->assertSame([3 => 'user3', 2 => 'user2', 1 => 'user1'], $result);

        // https://github.com/yiisoft/yii2/issues/12649
        $result = (new Query())->from('customer')
            ->select(['name', 'id'])
            ->orderBy(['id' => SORT_DESC])
            ->indexBy(static fn ($row) => $row['id'] * 2)
            ->column($db)
        ;
        $this->assertSame([6 => 'user3', 4 => 'user2', 2 => 'user1'], $result);

        $result = (new Query())->from('customer')
            ->select(['name'])
            ->indexBy('name')
            ->orderBy(['id' => SORT_DESC])
            ->column($db)
        ;
        $this->assertSame(['user3' => 'user3', 'user2' => 'user2', 'user1' => 'user1'], $result);
    }

    /**
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @see https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy(): void
    {
        switch ($this->driverName) {
            case 'pgsql':
            case 'sqlite':
                $selectExpression = "(customer.name || ' in ' || p.description) AS name";

                break;
            case 'cubird':
            case 'mysql':
                $selectExpression = "concat(customer.name,' in ', p.description) name";

                break;

            default:
                $this->markTestIncomplete('CONCAT syntax for this DBMS is not added to the test yet.');
        }

        $db = $this->getConnection();
        $result = (new Query())->select([$selectExpression])->from('customer')
            ->innerJoin('profile p', '{{customer}}.[[profile_id]] = {{p}}.[[id]]')
            ->indexBy('id')->column($db);
        $this->assertSame([
            1 => 'user1 in profile customer 1',
            3 => 'user3 in profile customer 3',
        ], $result);
    }

    public function testCount(): void
    {
        $db = $this->getConnection();

        $count = (new Query())->from('customer')->count('*', $db);
        $this->assertSame(3, $count);

        $count = (new Query())->from('customer')->where(['status' => 2])->count('*', $db);
        $this->assertSame(1, $count);

        $count = (new Query())->select('[[status]], COUNT([[id]]) cnt')->from('customer')->groupBy('status')->count('*', $db);
        $this->assertSame(2, $count);

        // testing that orderBy() should be ignored here as it does not affect the count anyway.
        $count = (new Query())->from('customer')->orderBy('status')->count('*', $db);
        $this->assertSame(3, $count);

        $count = (new Query())->from('customer')->orderBy('id')->limit(1)->count('*', $db);
        $this->assertSame(3, $count);
    }

    /**
     * @depends testFilterWhereWithHashFormat
     * @depends testFilterWhereWithOperatorFormat
     */
    public function testAndFilterCompare(): void
    {
        $query = new Query();

        $result = $query->andFilterCompare('name', null);
        $this->assertInstanceOf('yii\db\Query', $result);
        $this->assertNull($query->where);

        $query->andFilterCompare('name', '');
        $this->assertNull($query->where);

        $query->andFilterCompare('name', 'John Doe');
        $condition = ['=', 'name', 'John Doe'];
        $this->assertSame($condition, $query->where);

        $condition = ['and', $condition, ['like', 'name', 'Doe']];
        $query->andFilterCompare('name', 'Doe', 'like');
        $this->assertSame($condition, $query->where);

        $condition[] = ['>', 'rating', '9'];
        $query->andFilterCompare('rating', '>9');
        $this->assertSame($condition, $query->where);

        $condition[] = ['<=', 'value', '100'];
        $query->andFilterCompare('value', '<=100');
        $this->assertSame($condition, $query->where);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8068
     *
     * @depends testCount
     */
    public function testCountHavingWithoutGroupBy(): void
    {
        if (!in_array($this->driverName, ['mysql'], true)) {
            $this->markTestSkipped("{$this->driverName} does not support having without group by.");
        }

        $db = $this->getConnection();

        $count = (new Query())->from('customer')->having(['status' => 2])->count('*', $db);
        $this->assertSame(1, $count);
    }

    public function testEmulateExecution(): void
    {
        $db = $this->getConnection();

        $this->assertGreaterThan(0, (new Query())->from('customer')->count('*', $db));

        $rows = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->all($db)
        ;
        $this->assertSame([], $rows);

        $row = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->one($db)
        ;
        $this->assertFalse($row);

        $exists = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->exists($db)
        ;
        $this->assertFalse($exists);

        $count = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->count('*', $db)
        ;
        $this->assertSame(0, $count);

        $sum = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->sum('id', $db)
        ;
        $this->assertSame(0, $sum);

        $sum = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->average('id', $db)
        ;
        $this->assertSame(0, $sum);

        $max = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->max('id', $db)
        ;
        $this->assertNull($max);

        $min = (new Query())
            ->from('customer')
            ->emulateExecution()
            ->min('id', $db)
        ;
        $this->assertNull($min);

        $scalar = (new Query())
            ->select(['id'])
            ->from('customer')
            ->emulateExecution()
            ->scalar($db)
        ;
        $this->assertNull($scalar);

        $column = (new Query())
            ->select(['id'])
            ->from('customer')
            ->emulateExecution()
            ->column($db)
        ;
        $this->assertSame([], $column);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13745
     */
    public function testMultipleLikeConditions(): void
    {
        $db = $this->getConnection();
        $tableName = 'like_test';
        $columnName = 'col';

        if ($db->getSchema()->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }
        $db->createCommand()->createTable($tableName, [
            $columnName => $db->getSchema()->createColumnSchemaBuilder(Schema::TYPE_STRING, 64),
        ])->execute();
        $db->createCommand()->batchInsert($tableName, ['col'], [
            ['test0'],
            ['test\1'],
            ['test\2'],
            ['foo%'],
            ['%bar'],
            ['%baz%'],
        ])->execute();

        // Basic tests
        $this->assertSame(1, $this->countLikeQuery($db, $tableName, $columnName, ['test0']));
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, ['test\\']));
        $this->assertSame(0, $this->countLikeQuery($db, $tableName, $columnName, ['test%']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['%']));

        // Multiple condition tests
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, [
            'test0',
            'test\1',
        ]));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, [
            'test0',
            'test\1',
            'test\2',
        ]));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, [
            'foo',
            '%ba',
        ]));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15355
     */
    public function testExpressionInFrom(): void
    {
        $db = $this->getConnection();
        $query = (new Query())
            ->from(new \yii\db\Expression('(SELECT [[id]], [[name]], [[email]], [[address]], [[status]] FROM {{customer}}) c'))
            ->where(['status' => 2])
        ;

        $result = $query->one($db);
        $this->assertSame('user3', $result['name']);
    }

    public function testQueryCache(): void
    {
        $db = $this->getConnection();
        $db->enableQueryCache = true;
        $db->queryCache = new ArrayCache();
        $query = (new Query())
            ->select(['name'])
            ->from('customer')
        ;
        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');

        $this->assertSame('user1', $query->where(['id' => 1])->scalar($db), 'Asserting initial value');

        // No cache
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();
        $this->assertSame('user11', $query->where(['id' => 1])->scalar($db), 'Query reflects DB changes when caching is disabled');

        // Connection cache
        $db->cache(function (Connection $db) use ($query, $update): void {
            $this->assertSame('user2', $query->where(['id' => 2])->scalar($db), 'Asserting initial value for user #2');

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();
            $this->assertSame('user2', $query->where(['id' => 2])->scalar($db), 'Query does NOT reflect DB changes when wrapped in connection caching');

            $db->noCache(function () use ($query, $db): void {
                $this->assertSame('user22', $query->where(['id' => 2])->scalar($db), 'Query reflects DB changes when wrapped in connection caching and noCache simultaneously');
            });

            $this->assertSame('user2', $query->where(['id' => 2])->scalar($db), 'Cache does not get changes after getting newer data from DB in noCache block.');
        }, 10);

        $db->enableQueryCache = false;
        $db->cache(function ($db) use ($query, $update): void {
            $this->assertSame('user22', $query->where(['id' => 2])->scalar($db), 'When cache is disabled for the whole connection, Query inside cache block does not get cached');
            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();
            $this->assertSame('user2', $query->where(['id' => 2])->scalar($db));
        }, 10);

        $db->enableQueryCache = true;
        $query->cache();

        $this->assertSame('user11', $query->where(['id' => 1])->scalar($db));
        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();
        $this->assertSame('user11', $query->where(['id' => 1])->scalar($db), 'When both Connection and Query have cache enabled, we get cached value');
        $this->assertSame('user1', $query->noCache()->where(['id' => 1])->scalar($db), 'When Query has disabled cache, we get actual data');

        $db->cache(function (Connection $db) use ($query): void {
            $this->assertSame('user1', $query->noCache()->where(['id' => 1])->scalar($db));
            $this->assertSame('user11', $query->cache()->where(['id' => 1])->scalar($db));
        }, 10);
    }

    /**
     * checks that all needed properties copied from source to new query.
     */
    public function testQueryCreation(): void
    {
        $where = 'id > :min_user_id';
        $limit = 50;
        $offset = 2;
        $orderBy = ['name' => SORT_ASC];
        $indexBy = 'id';
        $select = ['id' => 'id', 'name' => 'name', 'articles_count' => 'count(*)'];
        $selectOption = 'SQL_NO_CACHE';
        $from = 'recent_users';
        $groupBy = 'id';
        $having = ['>', 'articles_count', 0];
        $params = [':min_user_id' => 100];
        [$joinType, $joinTable, $joinOn] = $join = ['INNER', 'articles', 'articles.author_id=users.id'];

        $unionQuery = (new Query())
            ->select('id, name, 1000 as articles_count')
            ->from('admins')
        ;

        $withQuery = (new Query())
            ->select('id, name')
            ->from('users')
            ->where('DATE(registered_at) > "2020-01-01"')
        ;

        // build target query
        $sourceQuery = (new Query())
            ->where($where)
            ->limit($limit)
            ->offset($offset)
            ->orderBy($orderBy)
            ->indexBy($indexBy)
            ->select($select, $selectOption)
            ->distinct()
            ->from($from)
            ->groupBy($groupBy)
            ->having($having)
            ->addParams($params)
            ->join($joinType, $joinTable, $joinOn)
            ->union($unionQuery)
            ->withQuery($withQuery, $from)
        ;

        $newQuery = Query::create($sourceQuery);

        $this->assertSame($where, $newQuery->where);
        $this->assertSame($limit, $newQuery->limit);
        $this->assertSame($offset, $newQuery->offset);
        $this->assertSame($orderBy, $newQuery->orderBy);
        $this->assertSame($indexBy, $newQuery->indexBy);
        $this->assertSame($select, $newQuery->select);
        $this->assertSame($selectOption, $newQuery->selectOption);
        $this->assertTrue($newQuery->distinct);
        $this->assertSame([$from], $newQuery->from);
        $this->assertSame([$groupBy], $newQuery->groupBy);
        $this->assertSame($having, $newQuery->having);
        $this->assertSame($params, $newQuery->params);
        $this->assertSame([$join], $newQuery->join);
        $this->assertSame([['query' => $unionQuery, 'all' => false]], $newQuery->union);
        $this->assertSame([['query' => $withQuery, 'alias' => $from, 'recursive' => false]], $newQuery->withQueries);
    }

    protected function createQuery()
    {
        return new Query();
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $operator
     *
     * @return int
     */
    protected function countLikeQuery(Connection $db, $tableName, $columnName, array $condition, $operator = 'or')
    {
        $whereCondition = [$operator];

        foreach ($condition as $value) {
            $whereCondition[] = ['like', $columnName, $value];
        }
        $result = (new Query())
            ->from($tableName)
            ->where($whereCondition)
            ->count('*', $db)
        ;

        if (is_numeric($result)) {
            $result = (int) $result;
        }

        return $result;
    }
}
