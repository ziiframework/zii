<?php declare(strict_types=1);
namespace yiiunit\framework\web\stubs;

use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

class ModelBindingStub extends ActiveRecord
{
    /**
     * @return self;
     * @throw NotFoundHttpException
     */
    public static function build()
    {
        throw new NotFoundHttpException('Not Found Item.');
    }
}
