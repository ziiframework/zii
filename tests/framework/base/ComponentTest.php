<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use function get_class;
use const PHP_VERSION_ID;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Event;
use yiiunit\TestCase;

function globalEventHandler($event): void
{
    $event->sender->eventHandled = true;
}

function globalEventHandler2($event): void
{
    $event->sender->eventHandled = true;
    $event->handled = true;
}

/**
 * @group base
 *
 * @internal
 * @coversNothing
 */
final class ComponentTest extends TestCase
{
    /**
     * @var NewComponent
     */
    protected $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        $this->component = new NewComponent();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->component = null;
        gc_enable();
        gc_collect_cycles();
    }

    public function testClone(): void
    {
        $component = new NewComponent();
        $behavior = new NewBehavior();
        $component->attachBehavior('a', $behavior);
        $this->assertSame($behavior, $component->getBehavior('a'));
        $component->on('test', 'fake');
        $this->assertTrue($component->hasEventHandlers('test'));
        $component->on('*', 'fakeWildcard');
        $this->assertTrue($component->hasEventHandlers('foo'));

        $clone = clone $component;
        $this->assertNotSame($component, $clone);
        $this->assertNull($clone->getBehavior('a'));
        $this->assertFalse($clone->hasEventHandlers('test'));
        $this->assertFalse($clone->hasEventHandlers('foo'));
        $this->assertFalse($clone->hasEventHandlers('*'));
    }

    public function testHasProperty(): void
    {
        $this->assertTrue($this->component->hasProperty('Text'));
        $this->assertTrue($this->component->hasProperty('text'));
        $this->assertFalse($this->component->hasProperty('Caption'));
        $this->assertTrue($this->component->hasProperty('content'));
        $this->assertFalse($this->component->hasProperty('content', false));
        $this->assertFalse($this->component->hasProperty('Content'));
    }

    public function testCanGetProperty(): void
    {
        $this->assertTrue($this->component->canGetProperty('Text'));
        $this->assertTrue($this->component->canGetProperty('text'));
        $this->assertFalse($this->component->canGetProperty('Caption'));
        $this->assertTrue($this->component->canGetProperty('content'));
        $this->assertFalse($this->component->canGetProperty('content', false));
        $this->assertFalse($this->component->canGetProperty('Content'));
    }

    public function testCanSetProperty(): void
    {
        $this->assertTrue($this->component->canSetProperty('Text'));
        $this->assertTrue($this->component->canSetProperty('text'));
        $this->assertFalse($this->component->canSetProperty('Object'));
        $this->assertFalse($this->component->canSetProperty('Caption'));
        $this->assertTrue($this->component->canSetProperty('content'));
        $this->assertFalse($this->component->canSetProperty('content', false));
        $this->assertFalse($this->component->canSetProperty('Content'));

        // behavior
        $this->assertFalse($this->component->canSetProperty('p2'));
        $behavior = new NewBehavior();
        $this->component->attachBehavior('a', $behavior);
        $this->assertTrue($this->component->canSetProperty('p2'));
        $this->component->detachBehavior('a');
    }

    public function testGetProperty(): void
    {
        $this->assertSame('default', $this->component->Text);
        $this->expectException('yii\base\UnknownPropertyException');
        $value2 = $this->component->Caption;
    }

    public function testSetProperty(): void
    {
        $value = 'new value';
        $this->component->Text = $value;
        $this->assertSame($value, $this->component->Text);
        $this->expectException('yii\base\UnknownPropertyException');
        $this->component->NewMember = $value;
    }

    public function testIsset(): void
    {
        $this->assertTrue(isset($this->component->Text));
        $this->assertNotEmpty($this->component->Text);

        $this->component->Text = '';
        $this->assertTrue(isset($this->component->Text));
        $this->assertEmpty($this->component->Text);

        $this->component->Text = null;
        $this->assertFalse(isset($this->component->Text));
        $this->assertEmpty($this->component->Text);

        $this->assertFalse(isset($this->component->p2));
        $this->component->attachBehavior('a', new NewBehavior());
        $this->component->setP2('test');
        $this->assertTrue(isset($this->component->p2));
    }

    public function testCallUnknownMethod(): void
    {
        $this->expectException('yii\base\UnknownMethodException');
        $this->component->unknownMethod();
    }

    public function testUnset(): void
    {
        unset($this->component->Text);
        $this->assertFalse(isset($this->component->Text));
        $this->assertEmpty($this->component->Text);

        $this->component->attachBehavior('a', new NewBehavior());
        $this->component->setP2('test');
        $this->assertSame('test', $this->component->getP2());

        unset($this->component->p2);
        $this->assertNull($this->component->getP2());
    }

    public function testUnsetReadonly(): void
    {
        $this->expectException('yii\base\InvalidCallException');
        unset($this->component->object);
    }

    public function testOn(): void
    {
        $this->assertFalse($this->component->hasEventHandlers('click'));
        $this->component->on('click', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('click'));

        $this->assertFalse($this->component->hasEventHandlers('click2'));
        $p = 'on click2';
        $this->component->{$p} = 'foo2';
        $this->assertTrue($this->component->hasEventHandlers('click2'));
    }

    /**
     * @depends testOn
     */
    public function testOff(): void
    {
        $this->assertFalse($this->component->hasEventHandlers('click'));
        $this->component->on('click', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('click'));
        $this->component->off('click', 'foo');
        $this->assertFalse($this->component->hasEventHandlers('click'));

        $this->component->on('click2', 'foo');
        $this->component->on('click2', 'foo2');
        $this->component->on('click2', 'foo3');
        $this->assertTrue($this->component->hasEventHandlers('click2'));
        $this->component->off('click2', 'foo3');
        $this->assertTrue($this->component->hasEventHandlers('click2'));
        $this->component->off('click2');
        $this->assertFalse($this->component->hasEventHandlers('click2'));
    }

    /**
     * @depends testOn
     */
    public function testTrigger(): void
    {
        $this->component->on('click', [$this->component, 'myEventHandler']);
        $this->assertFalse($this->component->eventHandled);
        $this->assertNull($this->component->event);
        $this->component->raiseEvent();
        $this->assertTrue($this->component->eventHandled);
        $this->assertSame('click', $this->component->event->name);
        $this->assertSame($this->component, $this->component->event->sender);
        $this->assertFalse($this->component->event->handled);

        $eventRaised = false;
        $this->component->on('click', static function ($event) use (&$eventRaised): void {
            $eventRaised = true;
        });
        $this->component->raiseEvent();
        $this->assertTrue($eventRaised);

        // raise event w/o parameters
        $eventRaised = false;
        $this->component->on('test', static function ($event) use (&$eventRaised): void {
            $eventRaised = true;
        });
        $this->component->trigger('test');
        $this->assertTrue($eventRaised);
    }

    /**
     * @depends testOn
     */
    public function testOnWildcard(): void
    {
        $this->assertFalse($this->component->hasEventHandlers('group.click'));
        $this->component->on('group.*', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('group.click'));
        $this->assertFalse($this->component->hasEventHandlers('category.click'));
    }

    /**
     * @depends testOnWildcard
     * @depends testOff
     */
    public function testOffWildcard(): void
    {
        $this->assertFalse($this->component->hasEventHandlers('group.click'));
        $this->component->on('group.*', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('group.click'));
        $this->component->off('*', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('group.click'));
        $this->component->off('group.*', 'foo');
        $this->assertFalse($this->component->hasEventHandlers('group.click'));

        $this->component->on('category.*', 'foo');
        $this->component->on('category.*', 'foo2');
        $this->component->on('category.*', 'foo3');
        $this->assertTrue($this->component->hasEventHandlers('category.click'));
        $this->component->off('category.*', 'foo3');
        $this->assertTrue($this->component->hasEventHandlers('category.click'));
        $this->component->off('category.*');
        $this->assertFalse($this->component->hasEventHandlers('category.click'));
    }

    /**
     * @depends testTrigger
     */
    public function testTriggerWildcard(): void
    {
        $this->component->on('cli*', [$this->component, 'myEventHandler']);
        $this->assertFalse($this->component->eventHandled);
        $this->assertNull($this->component->event);
        $this->component->raiseEvent();
        $this->assertTrue($this->component->eventHandled);
        $this->assertSame('click', $this->component->event->name);
        $this->assertSame($this->component, $this->component->event->sender);
        $this->assertFalse($this->component->event->handled);

        $eventRaised = false;
        $this->component->on('cli*', static function ($event) use (&$eventRaised): void {
            $eventRaised = true;
        });
        $this->component->raiseEvent();
        $this->assertTrue($eventRaised);

        // raise event w/o parameters
        $eventRaised = false;
        $this->component->on('group.*', static function ($event) use (&$eventRaised): void {
            $eventRaised = true;
        });
        $this->component->trigger('group.test');
        $this->assertTrue($eventRaised);
    }

    public function testHasEventHandlers(): void
    {
        $this->assertFalse($this->component->hasEventHandlers('click'));

        $this->component->on('click', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('click'));

        $this->component->on('*', 'foo');
        $this->assertTrue($this->component->hasEventHandlers('some'));
    }

    public function testStopEvent(): void
    {
        $component = new NewComponent();
        $component->on('click', 'yiiunit\framework\base\globalEventHandler2');
        $component->on('click', [$this->component, 'myEventHandler']);
        $component->raiseEvent();
        $this->assertTrue($component->eventHandled);
        $this->assertFalse($this->component->eventHandled);
    }

    public function testAttachBehavior(): void
    {
        $component = new NewComponent();
        $this->assertFalse($component->hasProperty('p'));
        $this->assertFalse($component->behaviorCalled);
        $this->assertNull($component->getBehavior('a'));

        $behavior = new NewBehavior();
        $component->attachBehavior('a', $behavior);
        $this->assertSame($behavior, $component->getBehavior('a'));
        $this->assertTrue($component->hasProperty('p'));
        $component->test();
        $this->assertTrue($component->behaviorCalled);

        $this->assertSame($behavior, $component->detachBehavior('a'));
        $this->assertFalse($component->hasProperty('p'));
        $this->expectException('yii\base\UnknownMethodException');
        $component->test();

        $p = 'as b';
        $component = new NewComponent();
        $component->{$p} = ['class' => 'NewBehavior'];
        $this->assertSame($behavior, $component->getBehavior('a'));
        $this->assertTrue($component->hasProperty('p'));
        $component->test();
        $this->assertTrue($component->behaviorCalled);
    }

    public function testAttachBehaviors(): void
    {
        $component = new NewComponent();
        $this->assertNull($component->getBehavior('a'));
        $this->assertNull($component->getBehavior('b'));

        $behavior = new NewBehavior();

        $component->attachBehaviors([
            'a' => $behavior,
            'b' => $behavior,
        ]);

        $this->assertSame(['a' => $behavior, 'b' => $behavior], $component->getBehaviors());
    }

    public function testDetachBehavior(): void
    {
        $component = new NewComponent();
        $behavior = new NewBehavior();

        $component->attachBehavior('a', $behavior);
        $this->assertSame($behavior, $component->getBehavior('a'));

        $detachedBehavior = $component->detachBehavior('a');
        $this->assertSame($detachedBehavior, $behavior);
        $this->assertNull($component->getBehavior('a'));

        $detachedBehavior = $component->detachBehavior('z');
        $this->assertNull($detachedBehavior);
    }

    public function testDetachBehaviors(): void
    {
        $component = new NewComponent();
        $behavior = new NewBehavior();

        $component->attachBehavior('a', $behavior);
        $this->assertSame($behavior, $component->getBehavior('a'));
        $component->attachBehavior('b', $behavior);
        $this->assertSame($behavior, $component->getBehavior('b'));

        $component->detachBehaviors();
        $this->assertNull($component->getBehavior('a'));
        $this->assertNull($component->getBehavior('b'));
    }

    public function testSetReadOnlyProperty(): void
    {
        $this->expectException('\yii\base\InvalidCallException');
        $this->expectExceptionMessage('Setting read-only property: yiiunit\framework\base\NewComponent::object');
        $this->component->object = 'z';
    }

    public function testSetPropertyOfBehavior(): void
    {
        $this->assertNull($this->component->getBehavior('a'));

        $behavior = new NewBehavior();
        $this->component->attachBehaviors([
            'a' => $behavior,
        ]);
        $this->component->p = 'Yii is cool.';

        $this->assertSame('Yii is cool.', $this->component->getBehavior('a')->p);
    }

    public function testSettingBehaviorWithSetter(): void
    {
        $behaviorName = 'foo';
        $this->assertNull($this->component->getBehavior($behaviorName));
        $p = 'as ' . $behaviorName;
        $this->component->{$p} = __NAMESPACE__ . '\NewBehavior';
        $this->assertSame(__NAMESPACE__ . '\NewBehavior', get_class($this->component->getBehavior($behaviorName)));
    }

    public function testWriteOnlyProperty(): void
    {
        $this->expectException('\yii\base\InvalidCallException');
        $this->expectExceptionMessage('Getting write-only property: yiiunit\framework\base\NewComponent::writeOnly');
        $this->component->writeOnly;
    }

    public function testSuccessfulMethodCheck(): void
    {
        $this->assertTrue($this->component->hasMethod('hasProperty'));
    }

    public function testTurningOffNonExistingBehavior(): void
    {
        $this->assertFalse($this->component->hasEventHandlers('foo'));
        $this->assertFalse($this->component->off('foo'));
    }

    public function testDetachNotAttachedHandler(): void
    {
        $obj = new NewComponent();

        $obj->on('test', [$this, 'handler']);
        $this->assertFalse($obj->off('test', [$this, 'handler2']), 'Trying to remove the handler that is not attached');
        $this->assertTrue($obj->off('test', [$this, 'handler']), 'Trying to remove the attached handler');
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/17223
     */
    public function testEventClosureDetachesItself(): void
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Can not be tested on PHP < 7.0');

            return;
        }

        $obj = require __DIR__ . '/stub/AnonymousComponentClass.php';

        $obj->trigger('barEventOnce');
        $this->assertSame(1, $obj->foo);
        $obj->trigger('barEventOnce');
        $this->assertSame(1, $obj->foo);
    }
}

class NewComponent extends Component
{
    public $content;

    public $eventHandled = false;
    public $event;
    public $behaviorCalled = false;
    private $_object;
    private $_text = 'default';
    private $_items = [];

    public function getText()
    {
        return $this->_text;
    }

    public function setText($value): void
    {
        $this->_text = $value;
    }

    public function getObject()
    {
        if (!$this->_object) {
            $this->_object = new self();
            $this->_object->_text = 'object text';
        }

        return $this->_object;
    }

    public function getExecute()
    {
        return static fn ($param) => $param * 2;
    }

    public function getItems()
    {
        return $this->_items;
    }

    public function myEventHandler($event): void
    {
        $this->eventHandled = true;
        $this->event = $event;
    }

    public function raiseEvent(): void
    {
        $this->trigger('click', new Event());
    }

    public function setWriteOnly(): void
    {
    }
}

class NewBehavior extends Behavior
{
    public $p;
    private $p2;

    public function getP2()
    {
        return $this->p2;
    }

    public function setP2($value): void
    {
        $this->p2 = $value;
    }

    public function test()
    {
        $this->owner->behaviorCalled = true;

        return 2;
    }
}

class NewComponent2 extends Component
{
    public $a;
    public $b;
    public $c;

    public function __construct($b, $c)
    {
        $this->b = $b;
        $this->c = $c;
    }
}
