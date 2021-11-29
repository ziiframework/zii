<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\base;

use yii\base\Security;

/**
 * ExposedSecurity exposes protected methods for direct testing.
 */
class ExposedSecurity extends Security
{
    /**
     * @inheritDoc
     */
    public function hkdf($algo, $inputKey, $salt = null, $info = null, $length = 0)
    {
        return parent::hkdf($algo, $inputKey, $salt, $info, $length);
    }

    /**
     * @inheritDoc
     */
    public function pbkdf2($algo, $password, $salt, $iterations, $length = 0)
    {
        return parent::pbkdf2($algo, $password, $salt, $iterations, $length);
    }

    /**
     * @inheritDoc
     */
    public function shouldUseLibreSSL()
    {
        return parent::shouldUseLibreSSL();
    }
}
