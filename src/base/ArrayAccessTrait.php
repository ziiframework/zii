<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\base;

use ArrayIterator;
use ReturnTypeWillChange;

/**
 * ArrayAccessTrait provides the implementation for [[\IteratorAggregate]], [[\ArrayAccess]] and [[\Countable]].
 *
 * Note that ArrayAccessTrait requires the class using it contain a property named `data` which should be an array.
 * The data will be exposed by ArrayAccessTrait to support accessing the class object like an array.
 *
 * @property array $data
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
trait ArrayAccessTrait
{
    /**
     * Returns an iterator for traversing the data.
     * This method is required by the SPL interface [[\IteratorAggregate]].
     * It will be implicitly called when you use `foreach` to traverse the collection.
     *
     * @return ArrayIterator an iterator for traversing the cookies in the collection.
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Returns the number of data items.
     * This method is required by Countable interface.
     *
     * @return int number of data elements.
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return count($this->data);
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param mixed $offset the offset to check on
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param int $offset the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param int $offset the offset to set element
     * @param mixed $item the element value
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $item): void
    {
        $this->data[$offset] = $item;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param mixed $offset the offset to unset element
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
}
