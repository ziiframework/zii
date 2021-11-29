<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\data;

use ReflectionClass;
use yii\data\BaseDataProvider;
use yiiunit\TestCase;

/**
 * @group data
 */
class BaseDataProviderTest extends TestCase
{
    public function testGenerateId(): void
    {
        $rc = new ReflectionClass(BaseDataProvider::className());
        $rp = $rc->getProperty('counter');
        $rp->setAccessible(true);
        $rp->setValue(null);

        $this->assertNull((new ConcreteDataProvider())->id);
        $this->assertNotNull((new ConcreteDataProvider())->id);
    }
}

/**
 * ConcreteDataProvider.
 */
class ConcreteDataProvider extends BaseDataProvider
{
    /**
     * @inheritDoc
     */
    protected function prepareModels()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function prepareKeys($models)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function prepareTotalCount()
    {
        return 0;
    }
}
