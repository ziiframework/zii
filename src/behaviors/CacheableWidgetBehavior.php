<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\behaviors;

use yii\base\Widget;
use yii\di\Instance;
use yii\base\Behavior;
use yii\base\WidgetEvent;
use yii\caching\Dependency;
use yii\caching\CacheInterface;
use yii\base\InvalidConfigException;

/**
 * Cacheable widget behavior automatically caches widget contents according to duration and dependencies specified.
 *
 * The behavior may be used without any configuration if an application has `cache` component configured.
 * By default the widget will be cached for one minute.
 *
 * The following example will cache the posts widget for an indefinite duration until any post is modified.
 *
 * ```php
 * use yii\behaviors\CacheableWidgetBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => CacheableWidgetBehavior::class,
 *             'cacheDuration' => 0,
 *             'cacheDependency' => [
 *                 'class' => 'yii\caching\DbDependency',
 *                 'sql' => 'SELECT MAX(updated_at) FROM posts',
 *             ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Nikolay Oleynikov <oleynikovny@mail.ru>
 *
 * @since 2.0.14
 */
class CacheableWidgetBehavior extends Behavior
{
    /**
     * @var CacheInterface|string|array a cache object or a cache component ID
     * or a configuration array for creating a cache object.
     * Defaults to the `cache` application component.
     */
    public $cache = 'cache';

    /**
     * @var int cache duration in seconds.
     * Set to `0` to indicate that the cached data will never expire.
     * Defaults to 60 seconds or 1 minute.
     */
    public $cacheDuration = 60;

    /**
     * @var Dependency|array|null a cache dependency or a configuration array
     * for creating a cache dependency or `null` meaning no cache dependency.
     *
     * For example,
     *
     * ```php
     * [
     *     'class' => 'yii\caching\DbDependency',
     *     'sql' => 'SELECT MAX(updated_at) FROM posts',
     * ]
     * ```
     *
     * would make the widget cache depend on the last modified time of all posts.
     * If any post has its modification time changed, the cached content would be invalidated.
     */
    public $cacheDependency;

    /**
     * @var string[]|string an array of strings or a single string which would cause
     * the variation of the content being cached (e.g. an application language, a GET parameter).
     *
     * The following variation setting will cause the content to be cached in different versions
     * according to the current application language:
     *
     * ```php
     * [
     *     Yii::$app->language,
     * ]
     * ```
     */
    public $cacheKeyVariations = [];

    /**
     * @var bool whether to enable caching or not. Allows to turn the widget caching
     * on and off according to specific conditions.
     * The following configuration will disable caching when a special GET parameter is passed:
     *
     * ```php
     * empty(Yii::$app->request->get('disable-caching'))
     * ```
     */
    public $cacheEnabled = true;

    /**
     * {@inheritdoc}
     */
    public function attach($owner): void
    {
        parent::attach($owner);

        $this->initializeEventHandlers();
    }

    /**
     * Begins fragment caching. Prevents owner widget from execution
     * if its contents can be retrieved from the cache.
     *
     * @param WidgetEvent $event `Widget::EVENT_BEFORE_RUN` event.
     */
    public function beforeRun($event): void
    {
        $cacheKey = $this->getCacheKey();
        $fragmentCacheConfiguration = $this->getFragmentCacheConfiguration();

        if (!$this->owner->view->beginCache($cacheKey, $fragmentCacheConfiguration)) {
            $event->isValid = false;
        }
    }

    /**
     * Outputs widget contents and ends fragment caching.
     *
     * @param WidgetEvent $event `Widget::EVENT_AFTER_RUN` event.
     */
    public function afterRun($event): void
    {
        echo $event->result;
        $event->result = null;

        $this->owner->view->endCache();
    }

    /**
     * Initializes widget event handlers.
     */
    private function initializeEventHandlers(): void
    {
        $this->owner->on(Widget::EVENT_BEFORE_RUN, [$this, 'beforeRun']);
        $this->owner->on(Widget::EVENT_AFTER_RUN, [$this, 'afterRun']);
    }

    /**
     * Returns the cache instance.
     *
     * @return CacheInterface cache instance.
     *
     * @throws InvalidConfigException if cache instance instantiation fails.
     */
    private function getCacheInstance()
    {
        $cacheInterface = 'yii\caching\CacheInterface';

        return Instance::ensure($this->cache, $cacheInterface);
    }

    /**
     * Returns the widget cache key.
     *
     * @return string[] an array of strings representing the cache key.
     */
    private function getCacheKey()
    {
        // `$cacheKeyVariations` may be a `string` and needs to be cast to an `array`.
        $cacheKey = array_merge((array) get_class($this->owner), (array) $this->cacheKeyVariations);

        return $cacheKey;
    }

    /**
     * Returns a fragment cache widget configuration array.
     *
     * @return array a fragment cache widget configuration array.
     */
    private function getFragmentCacheConfiguration()
    {
        $cache = $this->getCacheInstance();
        $fragmentCacheConfiguration = [
            'cache' => $cache,
            'duration' => $this->cacheDuration,
            'dependency' => $this->cacheDependency,
            'enabled' => $this->cacheEnabled,
        ];

        return $fragmentCacheConfiguration;
    }
}
