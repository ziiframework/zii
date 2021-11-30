<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\caching;

use yii\caching\ArrayCache;
use yii\caching\DbQueryDependency;
use yii\db\Query;
use yiiunit\framework\db\DatabaseTestCase;

class DbQueryDependencyTest extends DatabaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected $driverName = 'sqlite';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->getConnection(false);

        $db->createCommand()->createTable('dependency_item', [
            'id' => 'pk',
            'value' => 'string',
        ])->execute();

        $db->createCommand()->insert('dependency_item', ['value' => 'initial'])->execute();
    }

    public function testIsChanged(): void
    {
        $db = $this->getConnection(false);
        $cache = new ArrayCache();

        $dependency = new DbQueryDependency();
        $dependency->db = $db;
        $dependency->query = (new Query())
            ->select(['id'])
            ->from('dependency_item')
            ->orderBy(['id' => SORT_DESC])
            ->limit(1);
        $dependency->reusable = false;

        $dependency->evaluateDependency($cache);
        $this->assertFalse($dependency->isChanged($cache));

        $db->createCommand()->insert('dependency_item', ['value' => 'new'])->execute();

        $this->assertTrue($dependency->isChanged($cache));
    }

    /**
     * @depends testIsChanged
     */
    public function testCustomMethod(): void
    {
        $db = $this->getConnection(false);
        $cache = new ArrayCache();

        $dependency = new DbQueryDependency();
        $dependency->db = $db;
        $dependency->query = (new Query())
            ->from('dependency_item')
            ->andWhere(['value' => 'active']);
        $dependency->reusable = false;
        $dependency->method = 'exists';

        $dependency->evaluateDependency($cache);
        $this->assertFalse($dependency->isChanged($cache));

        $db->createCommand()->insert('dependency_item', ['value' => 'active'])->execute();

        $this->assertTrue($dependency->isChanged($cache));
    }

    /**
     * @depends testCustomMethod
     */
    public function testCustomMethodCallback(): void
    {
        $db = $this->getConnection(false);
        $cache = new ArrayCache();

        $dependency = new DbQueryDependency();
        $dependency->db = $db;
        $dependency->query = (new Query())
            ->from('dependency_item')
            ->andWhere(['value' => 'not exist']);
        $dependency->reusable = false;
        $dependency->method = static function (Query $query, $db) {
            return $query->orWhere(['value' => 'initial'])->exists($db);
        };

        $dependency->evaluateDependency($cache);
        $this->assertFalse($dependency->isChanged($cache));

        $db->createCommand()->delete('dependency_item')->execute();

        $this->assertTrue($dependency->isChanged($cache));
    }
}
