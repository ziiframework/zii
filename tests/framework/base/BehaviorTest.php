<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use yii\base\Behavior;
use yii\base\Component;
use yiiunit\TestCase;

class BarClass extends Component
{
}

class FooClass extends Component
{
    public function behaviors()
    {
        return [
            'foo' => __NAMESPACE__ . '\BarBehavior',
        ];
    }
}

class BarBehavior extends Behavior
{
    public static $attachCount = 0;
    public static $detachCount = 0;

    public $behaviorProperty = 'behavior property';

    public function __call($name, $params)
    {
        if ($name == 'magicBehaviorMethod') {
            return 'Magic Behavior Method Result!';
        }

        return parent::__call($name, $params);
    }

    public function behaviorMethod()
    {
        return 'behavior method';
    }

    public function hasMethod($name)
    {
        if ($name == 'magicBehaviorMethod') {
            return true;
        }

        return parent::hasMethod($name);
    }

    public function attach($owner): void
    {
        ++self::$attachCount;
        parent::attach($owner);
    }

    public function detach(): void
    {
        ++self::$detachCount;
        parent::detach();
    }
}

/**
 * @group base
 *
 * @internal
 * @coversNothing
 */
final class BehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        gc_enable();
        gc_collect_cycles();
    }

    public function testAttachAndAccessingWithName(): void
    {
        BarBehavior::$attachCount = 0;
        BarBehavior::$detachCount = 0;

        $bar = new BarClass();
        $behavior = new BarBehavior();
        $bar->attachBehavior('bar', $behavior);
        $this->assertSame(1, BarBehavior::$attachCount);
        $this->assertSame(0, BarBehavior::$detachCount);
        $this->assertSame('behavior property', $bar->behaviorProperty);
        $this->assertSame('behavior method', $bar->behaviorMethod());
        $this->assertSame('behavior property', $bar->getBehavior('bar')->behaviorProperty);
        $this->assertSame('behavior method', $bar->getBehavior('bar')->behaviorMethod());

        $behavior = new BarBehavior(['behaviorProperty' => 'reattached']);
        $bar->attachBehavior('bar', $behavior);
        $this->assertSame(2, BarBehavior::$attachCount);
        $this->assertSame(1, BarBehavior::$detachCount);
        $this->assertSame('reattached', $bar->behaviorProperty);
    }

    public function testAttachAndAccessingAnonymous(): void
    {
        BarBehavior::$attachCount = 0;
        BarBehavior::$detachCount = 0;

        $bar = new BarClass();
        $behavior = new BarBehavior();
        $bar->attachBehaviors([$behavior]);
        $this->assertSame(1, BarBehavior::$attachCount);
        $this->assertSame(0, BarBehavior::$detachCount);
        $this->assertSame('behavior property', $bar->behaviorProperty);
        $this->assertSame('behavior method', $bar->behaviorMethod());
    }

    public function testAutomaticAttach(): void
    {
        BarBehavior::$attachCount = 0;
        BarBehavior::$detachCount = 0;

        $foo = new FooClass();
        $this->assertSame(0, BarBehavior::$attachCount);
        $this->assertSame(0, BarBehavior::$detachCount);
        $this->assertSame('behavior property', $foo->behaviorProperty);
        $this->assertSame('behavior method', $foo->behaviorMethod());
        $this->assertSame(1, BarBehavior::$attachCount);
        $this->assertSame(0, BarBehavior::$detachCount);
    }

    public function testMagicMethods(): void
    {
        $bar = new BarClass();
        $behavior = new BarBehavior();

        $this->assertFalse($bar->hasMethod('magicBehaviorMethod'));
        $bar->attachBehavior('bar', $behavior);
        $this->assertFalse($bar->hasMethod('magicBehaviorMethod', false));
        $this->assertTrue($bar->hasMethod('magicBehaviorMethod'));

        $this->assertSame('Magic Behavior Method Result!', $bar->magicBehaviorMethod());
    }

    public function testCallUnknownMethod(): void
    {
        $bar = new BarClass();
        $behavior = new BarBehavior();
        $this->expectException('yii\base\UnknownMethodException');

        $this->assertFalse($bar->hasMethod('nomagicBehaviorMethod'));
        $bar->attachBehavior('bar', $behavior);
        $bar->nomagicBehaviorMethod();
    }
}
