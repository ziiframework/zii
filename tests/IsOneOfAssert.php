<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit;

use yii\helpers\VarDumper;

/**
 * IsOneOfAssert asserts that the value is one of the expected values.
 */
class IsOneOfAssert extends \PHPUnit\Framework\Constraint\Constraint
{
    private $allowedValues;

    /**
     * IsOneOfAssert constructor.
     */
    public function __construct(array $allowedValues)
    {
        parent::__construct();
        $this->allowedValues = $allowedValues;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        $allowedValues = array_map(static fn ($value) => VarDumper::dumpAsString($value), $this->allowedValues);
        $expectedAsString = implode(', ', $allowedValues);

        return "is one of $expectedAsString";
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        return in_array($other, $this->allowedValues, false);
    }
}
