<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

use ArrayAccess;
use Closure;

/**
 * CacheInterface is the base interface for cache.
 *
 * A data item can be stored in the cache by calling [[set()]] and be retrieved back
 * later (in the same or different request) by [[get()]]. In both operations,
 * a key identifying the data item is required. An expiration time and/or a [[Dependency|dependency]]
 * can also be specified when calling [[set()]]. If the data item expires or the dependency
 * changes at the time of calling [[get()]], the cache will return no data.
 *
 * A typical usage pattern of cache is like the following:
 *
 * ```php
 * $key = 'demo';
 * $data = $cache->get($key);
 * if ($data === false) {
 *     // ...generate $data here...
 *     $cache->set($key, $data, $duration, $dependency);
 * }
 * ```
 *
 * Because CacheInterface extends the [[\ArrayAccess]] interface, it can be used like an array. For example,
 *
 * ```php
 * $cache['foo'] = 'some data';
 * echo $cache['foo'];
 * ```
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 *
 * @since 2.0.13. Previous framework versions used abstract class [[yii\caching\Cache]] as interface.
 */
interface CacheInterface extends ArrayAccess
{
    /**
     * Builds a normalized cache key from a given key.
     *
     * If the given key is a string containing alphanumeric characters only and no more than 32 characters,
     * then the key will be returned back prefixed with [[keyPrefix]]. Otherwise, a normalized key
     * is generated by serializing the given key, applying MD5 hashing, and prefixing with [[keyPrefix]].
     *
     * @param mixed $key the key to be normalized
     *
     * @return string the generated cache key
     */
    public function buildKey($key);

    /**
     * Retrieves a value from cache with a specified key.
     *
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     *
     * @return mixed the value stored in cache, false if the value is not in the cache, expired,
     * or the dependency associated with the cached data has changed.
     */
    public function get($key);

    /**
     * Checks whether a specified key exists in the cache.
     * This can be faster than getting the value from the cache if the data is big.
     * In case a cache does not support this feature natively, this method will try to simulate it
     * but has no performance improvement over getting it.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     *
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     *
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key);

    /**
     * Retrieves multiple values from cache with the specified keys.
     * Some caches (such as memcache, apc) allow retrieving multiple cached values at the same time,
     * which may improve the performance. In case a cache does not support this feature natively,
     * this method will try to simulate it.
     *
     * @param string[] $keys list of string keys identifying the cached values
     *
     * @return array list of cached values corresponding to the specified keys. The array
     * is returned in terms of (key, value) pairs.
     * If a value is not cached or expired, the corresponding array value will be false.
     */
    public function multiGet($keys);

    /**
     * Stores a value identified by a key into cache.
     * If the cache already contains such a key, the existing value and
     * expiration time will be replaced with the new ones, respectively.
     *
     * @param mixed $key a key identifying the value to be cached. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @param mixed $value the value to be cached
     * @param int $duration default duration in seconds before the cache will expire. If not set,
     * default [[defaultDuration]] value is used.
     * @param Dependency $dependency dependency of the cached item. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via [[get()]].
     * This parameter is ignored if [[serializer]] is false.
     *
     * @return bool whether the value is successfully stored into cache
     */
    public function set($key, $value, $duration = null, $dependency = null);

    /**
     * Stores multiple items in cache. Each item contains a value identified by a key.
     * If the cache already contains such a key, the existing value and
     * expiration time will be replaced with the new ones, respectively.
     *
     * @param array $items the items to be cached, as key-value pairs.
     * @param int $duration default duration in seconds before the cache will expire. If not set,
     * default [[defaultDuration]] value is used.
     * @param Dependency $dependency dependency of the cached items. If the dependency changes,
     * the corresponding values in the cache will be invalidated when it is fetched via [[get()]].
     * This parameter is ignored if [[serializer]] is false.
     *
     * @return array array of failed keys
     */
    public function multiSet($items, $duration = null, $dependency = null);

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * Nothing will be done if the cache already contains the key.
     *
     * @param mixed $key a key identifying the value to be cached. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @param mixed $value the value to be cached
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @param Dependency $dependency dependency of the cached item. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via [[get()]].
     * This parameter is ignored if [[serializer]] is false.
     *
     * @return bool whether the value is successfully stored into cache
     */
    public function add($key, $value, $duration = 0, $dependency = null);

    /**
     * Stores multiple items in cache. Each item contains a value identified by a key.
     * If the cache already contains such a key, the existing value and expiration time will be preserved.
     *
     * @param array $items the items to be cached, as key-value pairs.
     * @param int $duration default number of seconds in which the cached values will expire. 0 means never expire.
     * @param Dependency $dependency dependency of the cached items. If the dependency changes,
     * the corresponding values in the cache will be invalidated when it is fetched via [[get()]].
     * This parameter is ignored if [[serializer]] is false.
     *
     * @return array array of failed keys
     */
    public function multiAdd($items, $duration = 0, $dependency = null);

    /**
     * Deletes a value with the specified key from cache.
     *
     * @param mixed $key a key identifying the value to be deleted from cache. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     *
     * @return bool if no error happens during deletion
     */
    public function delete($key);

    /**
     * Deletes all values from cache.
     * Be careful of performing this operation if the cache is shared among multiple applications.
     *
     * @return bool whether the flush operation was successful.
     */
    public function flush();

    /**
     * Method combines both [[set()]] and [[get()]] methods to retrieve value identified by a $key,
     * or to store the result of $callable execution if there is no cache available for the $key.
     *
     * Usage example:
     *
     * ```php
     * public function getTopProducts($count = 10) {
     *     $cache = $this->cache; // Could be Yii::$app->cache
     *     return $cache->getOrSet(['top-n-products', 'n' => $count], function ($cache) use ($count) {
     *         return Products::find()->mostPopular()->limit($count)->all();
     *     }, 1000);
     * }
     * ```
     *
     * @param mixed $key a key identifying the value to be cached. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @param callable|Closure $callable the callable or closure that will be used to generate a value to be cached.
     * In case $callable returns `false`, the value will not be cached.
     * @param int $duration default duration in seconds before the cache will expire. If not set,
     * [[defaultDuration]] value will be used.
     * @param Dependency $dependency dependency of the cached item. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via [[get()]].
     * This parameter is ignored if [[serializer]] is `false`.
     *
     * @return mixed result of $callable execution
     */
    public function getOrSet($key, $callable, $duration = null, $dependency = null);
}
