<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\data\ar;

/**
 * Class Animal.
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 *
 * @property int    $id
 * @property string $type
 */
class Animal extends ActiveRecord
{
    public $does;

    public static function tableName()
    {
        return 'animal';
    }

    /**
     * @param type $row
     *
     * @return \yiiunit\data\ar\Animal
     */
    public static function instantiate($row)
    {
        $class = $row['type'];

        return new $class();
    }

    public function init(): void
    {
        parent::init();
        $this->type = static::class;
    }

    public function getDoes()
    {
        return $this->does;
    }
}
