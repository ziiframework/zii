<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\web;

use yii\base\BaseObject;

/**
 * @inheritDoc
 */
class Post extends BaseObject
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @param int    $id
     * @param string $title
     * @param array  $config
     */
    public function __construct($id, $title, $config = [])
    {
        $this->id    = $id;
        $this->title = $title;

        parent::__construct($config);
    }
}
