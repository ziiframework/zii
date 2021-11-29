<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\data\base;

use Countable;
use Exception;
use Iterator;

/**
 * TraversableObject
 * Object that implements `\Traversable` and `\Countable`, but counting throws an exception;
 * Used for testing support for traversable objects instead of arrays.
 *
 * @author Sam Mousa <sam@mousa.nl>
 *
 * @since 2.0.8
 */
class TraversableObject implements Countable, Iterator
{
    protected $data;

    private $position = 0;

    public function __construct(array $array)
    {
        $this->data = $array;
    }

    /**
     * @throws Exception
     *
     * @since 5.1.0
     */
    public function count(): void
    {
        throw new Exception('Count called on object that should only be traversed.');
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->data[$this->position];
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return array_key_exists($this->position, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->position = 0;
    }
}
