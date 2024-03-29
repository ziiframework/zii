<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit;

use Exception;
use ReflectionObject;
use ReflectionException;

class Support
{
    /**
     * @see https://github.com/guzzle/guzzle/blob/master/tests/Helpers.php
     */
    public static function readObjectAttribute(object $object, string $attributeName)
    {
        $reflector = new ReflectionObject($object);

        do {
            try {
                $attribute = $reflector->getProperty($attributeName);

                if (!$attribute || $attribute->isPublic()) {
                    return $object->$attributeName;
                }

                $attribute->setAccessible(true);

                try {
                    return $attribute->getValue($object);
                } finally {
                    $attribute->setAccessible(false);
                }
            } catch (ReflectionException $e) {
                // do nothing
            }
        } while ($reflector = $reflector->getParentClass());

        throw new Exception(sprintf('Attribute "%s" not found in object.', $attributeName));
    }
}
