<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\models;

use JsonSerializable;
use yii\base\DynamicModel;

/**
 * JSON serializable model for tests.
 *
 * {@inheritDoc}
 */
class JsonModel extends DynamicModel implements JsonSerializable
{
    /**
     * @var array
     */
    public $data = ['json' => 'serializable'];

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->defineAttribute('name');
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['name', 'required'],
            ['name', 'string', 'max' => 100],
        ];
    }
}
