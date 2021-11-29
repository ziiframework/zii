<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web\mocks;

use yii\web\Request;

class TestRequestComponent extends Request
{
    /**
     * @var null|bool override getIsAjax() method return value
     */
    public $getIssAjaxOverride;

    /**
     * @var null|string override getMethod() method return value
     */
    public $getMethodOverride;

    /**
     * @var null|string override getUserAgent() method return value
     */
    public $getUserAgentOverride;

    /**
     * @var null|bool override getIsPjax() method return value
     */
    public $getIsPjaxOverride;

    /**
     * {@inheritDoc}
     */
    public function getMethod()
    {
        if ($this->getMethodOverride !== null) {
            return $this->getMethodOverride;
        }

        return parent::getMethod(); // TODO: Change the autogenerated stub
    }

    /**
     * {@inheritDoc}
     */
    public function getIsAjax()
    {
        if ($this->getIssAjaxOverride !== null) {
            return $this->getIssAjaxOverride;
        }

        return parent::getIsAjax();
    }

    /**
     * {@inheritDoc}
     */
    public function getIsPjax()
    {
        if ($this->getIsPjaxOverride !== null) {
            return $this->getIsPjaxOverride;
        }

        return parent::getIsPjax();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAgent()
    {
        if ($this->getUserAgentOverride !== null) {
            return $this->getUserAgentOverride;
        }

        return parent::getUserAgent();
    }
}
