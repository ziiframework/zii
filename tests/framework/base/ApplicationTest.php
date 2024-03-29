<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use Yii;
use yii\base\Module;
use yiiunit\TestCase;
use yii\base\Component;
use yii\log\Dispatcher;
use yii\base\BootstrapInterface;

/**
 * @group base
 */
class ApplicationTest extends TestCase
{
    public function testContainerSettingsAffectBootstrap(): void
    {
        $this->mockApplication([
            'container' => [
                'definitions' => [
                    Dispatcher::className() => DispatcherMock::className(),
                ],
            ],
            'bootstrap' => ['log'],
        ]);

        $this->assertInstanceOf(DispatcherMock::className(), Yii::$app->log);
    }

    public function testBootstrap(): void
    {
        Yii::getLogger()->flush();

        $this->mockApplication([
            'components' => [
                'withoutBootstrapInterface' => [
                    'class' => Component::className(),
                ],
                'withBootstrapInterface' => [
                    'class' => BootstrapComponentMock::className(),
                ],
            ],
            'modules' => [
                'moduleX' => [
                    'class' => Module::className(),
                ],
            ],
            'bootstrap' => [
                'withoutBootstrapInterface',
                'withBootstrapInterface',
                'moduleX',
                static function (): void {
                },
            ],
        ]);
        $this->assertSame('Bootstrap with yii\base\Component', Yii::getLogger()->messages[0][0]);
        $this->assertSame('Bootstrap with yiiunit\framework\base\BootstrapComponentMock::bootstrap()', Yii::getLogger()->messages[1][0]);
        $this->assertSame('Loading module: moduleX', Yii::getLogger()->messages[2][0]);
        $this->assertSame('Bootstrap with yii\base\Module', Yii::getLogger()->messages[3][0]);
        $this->assertSame('Bootstrap with Closure', Yii::getLogger()->messages[4][0]);
    }

    public function testModuleId(): void
    {
        $this->mockApplication(['id' => 'app-basic']);
        $child = new Module('child');
        Yii::$app->setModules(['child' => $child]);

        $this->assertEquals('app-basic', Yii::$app->getModule('child')->module->id);
        $this->assertEquals('', Yii::$app->getModule('child')->module->getUniqueId());
        $this->assertEquals('child', Yii::$app->getModule('child')->getUniqueId());
    }
}

class DispatcherMock extends Dispatcher
{
}

class BootstrapComponentMock extends Component implements BootstrapInterface
{
    public function bootstrap($app): void
    {
    }
}
