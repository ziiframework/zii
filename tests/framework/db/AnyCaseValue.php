<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use function is_array;

class AnyCaseValue extends CompareValue
{
    public $value;

    /**
     * Constructor.
     *
     * @param string|string[] $value
     * @param array $config
     */
    public function __construct($value, $config = [])
    {
        if (is_array($value)) {
            $this->value = array_map('strtolower', $value);
        } else {
            $this->value = strtolower($value);
        }
        parent::__construct($config);
    }
}
