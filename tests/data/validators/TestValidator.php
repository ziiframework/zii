<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\validators;

use yii\validators\Validator;

use function get_class;

class TestValidator extends Validator
{
    private $_validatedAttributes = [];
    private $_setErrorOnValidateAttribute = false;

    public function validateAttribute($object, $attribute): void
    {
        $this->markAttributeValidated($attribute);

        if ($this->_setErrorOnValidateAttribute == true) {
            $this->addError($object, $attribute, sprintf('%s##%s', $attribute, get_class($object)));
        }
    }

    protected function markAttributeValidated($attr, $increaseBy = 1): void
    {
        if (!isset($this->_validatedAttributes[$attr])) {
            $this->_validatedAttributes[$attr] = 1;
        } else {
            $this->_validatedAttributes[$attr] = $this->_validatedAttributes[$attr] + $increaseBy;
        }
    }

    public function countAttributeValidations($attr)
    {
        return $this->_validatedAttributes[$attr] ?? 0;
    }

    public function isAttributeValidated($attr)
    {
        return isset($this->_validatedAttributes[$attr]);
    }

    public function enableErrorOnValidateAttribute(): void
    {
        $this->_setErrorOnValidateAttribute = true;
    }
}
