<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

use yii\db\Migration;
use yii\caching\DbCache;
use yii\base\InvalidConfigException;

/**
 * Initializes Cache tables.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 *
 * @since 2.0.7
 */
class m150909_153426_cache_init extends Migration
{
    /**
     * @return DbCache
     *
     * @throws yii\base\InvalidConfigException
     */
    protected function getCache()
    {
        $cache = Yii::$app->getCache();

        if (!$cache instanceof DbCache) {
            throw new InvalidConfigException('You should configure "cache" component to use database before executing this migration.');
        }

        return $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        $cache = $this->getCache();
        $this->db = $cache->db;

        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            // https://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($cache->cacheTable, [
            'id' => $this->string(128)->notNull(),
            'expire' => $this->integer(),
            'data' => $this->binary(),
            'PRIMARY KEY ([[id]])',
            ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $cache = $this->getCache();
        $this->db = $cache->db;

        $this->dropTable($cache->cacheTable);
    }
}
