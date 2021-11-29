<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use function count;
use yii\base\Event;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\QueryBuilder;
use yiiunit\data\ar\ActiveRecord;
use yiiunit\data\ar\Category;
use yiiunit\data\ar\Customer;
use yiiunit\data\ar\Order;
use yiiunit\data\ar\Profile;

/**
 * Class ActiveQueryTest the base class for testing ActiveQuery.
 */
abstract class ActiveQueryTest extends DatabaseTestCase
{
    use GetTablesAliasTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
    }

    public function testConstructor(): void
    {
        $config = [
            'on' => ['a' => 'b'],
            'joinWith' => ['dummy relation'],
        ];
        $query = new ActiveQuery(Customer::className(), $config);
        $this->assertSame($query->modelClass, Customer::className());
        $this->assertSame($query->on, $config['on']);
        $this->assertSame($query->joinWith, $config['joinWith']);
    }

    public function testTriggerInitEvent(): void
    {
        $where = '1==1';
        $callback = static function (Event $event) use ($where): void {
            $event->sender->where = $where;
        };
        Event::on(ActiveQuery::className(), ActiveQuery::EVENT_INIT, $callback);
        $result = new ActiveQuery(Customer::className());
        $this->assertSame($where, $result->where);
        Event::off(ActiveQuery::className(), ActiveQuery::EVENT_INIT, $callback);
    }

    /**
     * @todo tests for internal logic of prepare()
     */
    public function testPrepare(): void
    {
        $query = new ActiveQuery(Customer::className());
        $builder = new QueryBuilder(new Connection());
        $result = $query->prepare($builder);
        $this->assertInstanceOf('yii\db\Query', $result);
    }

    public function testPopulateEmptyRows(): void
    {
        $query = new ActiveQuery(Customer::className());
        $rows = [];
        $result = $query->populate([]);
        $this->assertSame($rows, $result);
    }

    /**
     * @todo tests for internal logic of populate()
     */
    public function testPopulateFilledRows(): void
    {
        $query = new ActiveQuery(Customer::className());
        $rows = $query->all();
        $result = $query->populate($rows);
        $this->assertSame($rows, $result);
    }

    /**
     * @todo tests for internal logic of one()
     */
    public function testOne(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->one();
        $this->assertInstanceOf('yiiunit\data\ar\Customer', $result);
    }

    /**
     * @todo test internal logic of createCommand()
     */
    public function testCreateCommand(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->createCommand();
        $this->assertInstanceOf('yii\db\Command', $result);
    }

    /**
     * @todo tests for internal logic of queryScalar()
     */
    public function testQueryScalar(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $this->invokeMethod($query, 'queryScalar', ['name', null]);
        $this->assertSame('user1', $result);
    }

    /**
     * @todo tests for internal logic of joinWith()
     */
    public function testJoinWith(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->joinWith('profile');
        $this->assertSame([
            [['profile'], true, 'LEFT JOIN'],
        ], $result->joinWith);
    }

    /**
     * @todo tests for internal logic of innerJoinWith()
     */
    public function testInnerJoinWith(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->innerJoinWith('profile');
        $this->assertSame([
            [['profile'], true, 'INNER JOIN'],
        ], $result->joinWith);
    }

    public function testBuildJoinWithRemoveDuplicateJoinByTableName(): void
    {
        $query = new ActiveQuery(Customer::className());
        $query->innerJoinWith('orders')
            ->joinWith('orders.orderItems')
        ;
        $this->invokeMethod($query, 'buildJoinWith');
        $this->assertSame([
            [
                'INNER JOIN',
                'order',
                '{{customer}}.[[id]] = {{order}}.[[customer_id]]',
            ],
            [
                'LEFT JOIN',
                'order_item',
                '{{order}}.[[id]] = {{order_item}}.[[order_id]]',
            ],
        ], $query->join);
    }

    /**
     * @todo tests for the regex inside getQueryTableName
     */
    public function testGetQueryTableNameFromNotSet(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $this->invokeMethod($query, 'getTableNameAndAlias');
        $this->assertSame(['customer', 'customer'], $result);
    }

    public function testGetQueryTableNameFromSet(): void
    {
        $options = ['from' => ['alias' => 'customer']];
        $query = new ActiveQuery(Customer::className(), $options);
        $result = $this->invokeMethod($query, 'getTableNameAndAlias');
        $this->assertSame(['customer', 'alias'], $result);
    }

    public function testOnCondition(): void
    {
        $query = new ActiveQuery(Customer::className());
        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->onCondition($on, $params);
        $this->assertSame($on, $result->on);
        $this->assertSame($params, $result->params);
    }

    public function testAndOnConditionOnNotSet(): void
    {
        $query = new ActiveQuery(Customer::className());
        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->andOnCondition($on, $params);
        $this->assertSame($on, $result->on);
        $this->assertSame($params, $result->params);
    }

    public function testAndOnConditionOnSet(): void
    {
        $onOld = ['active' => true];
        $query = new ActiveQuery(Customer::className());
        $query->on = $onOld;

        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->andOnCondition($on, $params);
        $this->assertSame(['and', $onOld, $on], $result->on);
        $this->assertSame($params, $result->params);
    }

    public function testOrOnConditionOnNotSet(): void
    {
        $query = new ActiveQuery(Customer::className());
        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->orOnCondition($on, $params);
        $this->assertSame($on, $result->on);
        $this->assertSame($params, $result->params);
    }

    public function testOrOnConditionOnSet(): void
    {
        $onOld = ['active' => true];
        $query = new ActiveQuery(Customer::className());
        $query->on = $onOld;

        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->orOnCondition($on, $params);
        $this->assertSame(['or', $onOld, $on], $result->on);
        $this->assertSame($params, $result->params);
    }

    /**
     * @todo tests for internal logic of viaTable()
     */
    public function testViaTable(): void
    {
        $query = new ActiveQuery(Customer::className(), ['primaryModel' => new Order()]);
        $result = $query->viaTable(Profile::className(), ['id' => 'item_id']);
        $this->assertInstanceOf('yii\db\ActiveQuery', $result);
        $this->assertInstanceOf('yii\db\ActiveQuery', $result->via);
    }

    public function testAliasNotSet(): void
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->alias('alias');
        $this->assertInstanceOf('yii\db\ActiveQuery', $result);
        $this->assertSame(['alias' => 'customer'], $result->from);
    }

    public function testAliasYetSet(): void
    {
        $aliasOld = ['old'];
        $query = new ActiveQuery(Customer::className());
        $query->from = $aliasOld;
        $result = $query->alias('alias');
        $this->assertInstanceOf('yii\db\ActiveQuery', $result);
        $this->assertSame(['alias' => 'old'], $result->from);
    }

    public function testGetTableNamesNotFilledFrom(): void
    {
        $query = new ActiveQuery(Profile::className());

        $tables = $query->getTablesUsedInFrom();

        $this->assertSame([
            '{{' . Profile::tableName() . '}}' => '{{' . Profile::tableName() . '}}',
        ], $tables);
    }

    public function testGetTableNamesWontFillFrom(): void
    {
        $query = new ActiveQuery(Profile::className());
        $this->assertSame($query->from, null);
        $query->getTablesUsedInFrom();
        $this->assertSame($query->from, null);
    }

    /**
     * https://github.com/yiisoft/yii2/issues/5341.
     *
     * Issue:     Plan     1 -- * Account * -- * User
     * Our Tests: Category 1 -- * Item    * -- * Order
     */
    public function testDeeplyNestedTableRelationWith(): void
    {
        /** @var Category $category */
        $categories = Category::find()->with('orders')->indexBy('id')->all();

        $category = $categories[1];
        $this->assertNotNull($category);
        $orders = $category->orders;
        $this->assertSame(2, count($orders));
        $this->assertInstanceOf(Order::className(), $orders[0]);
        $this->assertInstanceOf(Order::className(), $orders[1]);
        $ids = [$orders[0]->id, $orders[1]->id];
        sort($ids);
        $this->assertSame([1, 3], $ids);

        $category = $categories[2];
        $this->assertNotNull($category);
        $orders = $category->orders;
        $this->assertSame(1, count($orders));
        $this->assertInstanceOf(Order::className(), $orders[0]);
        $this->assertSame(2, $orders[0]->id);
    }

    protected function createQuery()
    {
        return new ActiveQuery(null);
    }
}
