<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\caching;

use PDO;
use Yii;
use Exception;
use yii\db\Query;
use yii\db\PdoValue;
use yii\di\Instance;
use yii\db\Connection;
use yii\base\InvalidConfigException;

use function time;
use function in_array;
use function random_int;
use function is_resource;
use function get_resource_type;
use function stream_get_contents;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * By default, DbCache stores session data in a DB table named 'cache'. This table
 * must be pre-created. The table name can be changed by setting [[cacheTable]].
 *
 * Please refer to [[Cache]] for common cache operations that are supported by DbCache.
 *
 * The following example shows how you can configure the application to use DbCache:
 *
 * ```php
 * 'cache' => [
 *     'class' => 'yii\caching\DbCache',
 *     // 'db' => 'mydb',
 *     // 'cacheTable' => 'my_cache',
 * ]
 * ```
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class DbCache extends Cache
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbCache object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'db';

    /**
     * @var string name of the DB table to store cache content.
     * The table should be pre-created as follows:
     *
     * ```php
     * CREATE TABLE cache (
     *     id char(128) NOT NULL PRIMARY KEY,
     *     expire int(11),
     *     data BLOB
     * );
     * ```
     *
     * For MSSQL:
     * ```php
     * CREATE TABLE cache (
     *     id VARCHAR(128) NOT NULL PRIMARY KEY,
     *     expire INT(11),
     *     data VARBINARY(MAX)
     * );
     * ```
     *
     * where 'BLOB' refers to the BLOB-type of your preferred DBMS. Below are the BLOB type
     * that can be used for some popular DBMS:
     *
     * - MySQL: LONGBLOB
     * - PostgreSQL: BYTEA
     *
     * When using DbCache in a production server, we recommend you create a DB index for the 'expire'
     * column in the cache table to improve the performance.
     */
    public $cacheTable = '{{%cache}}';

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 100, meaning 0.01% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public $gcProbability = 100;

    protected $isVarbinaryDataField;

    /**
     * Initializes the DbCache component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DB connection.
     *
     * @throws InvalidConfigException if [[db]] is invalid.
     */
    public function init(): void
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * Checks whether a specified key exists in the cache.
     * This can be faster than getting the value from the cache if the data is big.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     *
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     *
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key)
    {
        $key = $this->buildKey($key);

        $query = new Query();
        $query->select(['COUNT(*)'])
            ->from($this->cacheTable)
            ->where('[[id]] = :id AND ([[expire]] = 0 OR [[expire]] >' . time() . ')', [':id' => $key]);

        if ($this->db->enableQueryCache) {
            // temporarily disable and re-enable query caching
            $this->db->enableQueryCache = false;
            $result = $query->createCommand($this->db)->queryScalar();
            $this->db->enableQueryCache = true;
        } else {
            $result = $query->createCommand($this->db)->queryScalar();
        }

        return $result > 0;
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key a unique key identifying the cached value
     *
     * @return string|false the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        $query = new Query();
        $query->select([$this->getDataFieldName()])
            ->from($this->cacheTable)
            ->where('[[id]] = :id AND ([[expire]] = 0 OR [[expire]] >' . time() . ')', [':id' => $key]);

        if ($this->db->enableQueryCache) {
            // temporarily disable and re-enable query caching
            $this->db->enableQueryCache = false;
            $result = $query->createCommand($this->db)->queryScalar();
            $this->db->enableQueryCache = true;

            return $result;
        }

        return $query->createCommand($this->db)->queryScalar();
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     *
     * @param array $keys a list of keys identifying the cached values
     *
     * @return array a list of cached values indexed by the keys
     */
    protected function getValues($keys)
    {
        if (empty($keys)) {
            return [];
        }
        $query = new Query();
        $query->select(['id', $this->getDataFieldName()])
            ->from($this->cacheTable)
            ->where(['id' => $keys])
            ->andWhere('([[expire]] = 0 OR [[expire]] > ' . time() . ')');

        if ($this->db->enableQueryCache) {
            $this->db->enableQueryCache = false;
            $rows = $query->createCommand($this->db)->queryAll();
            $this->db->enableQueryCache = true;
        } else {
            $rows = $query->createCommand($this->db)->queryAll();
        }

        $results = [];

        foreach ($keys as $key) {
            $results[$key] = false;
        }

        foreach ($rows as $row) {
            if (is_resource($row['data']) && get_resource_type($row['data']) === 'stream') {
                $results[$row['id']] = stream_get_contents($row['data']);
            } else {
                $results[$row['id']] = $row['data'];
            }
        }

        return $results;
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached. Other types (if you have disabled [[serializer]]) cannot be saved.
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     *
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $duration)
    {
        try {
            $this->db->noCache(function (Connection $db) use ($key, $value, $duration): void {
                $db->createCommand()->upsert($this->cacheTable, [
                    'id' => $key,
                    'expire' => $duration > 0 ? $duration + time() : 0,
                    'data' => $this->getDataFieldValue($value),
                ])->execute();
            });

            $this->gc();

            return true;
        } catch (Exception $e) {
            Yii::warning("Unable to update or insert cache data: {$e->getMessage()}", __METHOD__);

            return false;
        }
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached. Other types (if you have disabled [[serializer]]) cannot be saved.
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     *
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $duration)
    {
        $this->gc();

        try {
            $this->db->noCache(function (Connection $db) use ($key, $value, $duration): void {
                $db->createCommand()
                    ->insert($this->cacheTable, [
                        'id' => $key,
                        'expire' => $duration > 0 ? $duration + time() : 0,
                        'data' => $this->getDataFieldValue($value),
                    ])->execute();
            });

            return true;
        } catch (Exception $e) {
            Yii::warning("Unable to insert cache data: {$e->getMessage()}", __METHOD__);

            return false;
        }
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key of the value to be deleted
     *
     * @return bool if no error happens during deletion
     */
    protected function deleteValue($key)
    {
        $this->db->noCache(function (Connection $db) use ($key): void {
            $db->createCommand()
                ->delete($this->cacheTable, ['id' => $key])
                ->execute();
        });

        return true;
    }

    /**
     * Removes the expired data values.
     *
     * @param bool $force whether to enforce the garbage collection regardless of [[gcProbability]].
     * Defaults to false, meaning the actual deletion happens with the probability as specified by [[gcProbability]].
     */
    public function gc($force = false): void
    {
        if ($force || random_int(0, 1000000) < $this->gcProbability) {
            $this->db->createCommand()
                ->delete($this->cacheTable, '[[expire]] > 0 AND [[expire]] < ' . time())
                ->execute();
        }
    }

    /**
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @return bool whether the flush operation was successful.
     */
    protected function flushValues()
    {
        $this->db->createCommand()
            ->delete($this->cacheTable)
            ->execute();

        return true;
    }

    /**
     * @return bool whether field is MSSQL varbinary
     *
     * @since 2.0.42
     */
    protected function isVarbinaryDataField()
    {
        if ($this->isVarbinaryDataField === null) {
            $this->isVarbinaryDataField = in_array($this->db->getDriverName(), ['sqlsrv', 'dblib']) &&
                $this->db->getTableSchema($this->cacheTable)->columns['data']->dbType === 'varbinary';
        }

        return $this->isVarbinaryDataField;
    }

    /**
     * @return string `data` field name converted for usage in MSSQL (if needed)
     *
     * @since 2.0.42
     */
    protected function getDataFieldName()
    {
        return $this->isVarbinaryDataField() ? 'convert(nvarchar(max),[data]) data' : 'data';
    }

    /**
     * @return PdoValue PdoValue or direct $value for usage in MSSQL
     *
     * @since 2.0.42
     */
    protected function getDataFieldValue($value)
    {
        return $this->isVarbinaryDataField() ? $value : new PdoValue($value, PDO::PARAM_LOB);
    }
}
