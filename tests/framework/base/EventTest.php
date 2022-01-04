<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\base;

use stdClass;
use yii\base\Component;
use yii\base\Event;
use yiiunit\TestCase;

/**
 * @group base
 */
class EventTest extends TestCase
{
    public $counter;

    protected function setUp(): void
    {
        $this->counter = 0;
        Event::offAll();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Event::offAll();
    }

    public function testOn(): void
    {
        Event::on(Post::className(), 'save', function ($event): void {
            ++$this->counter;
        });
        Event::on(ActiveRecord::className(), 'save', function ($event): void {
            $this->counter += 3;
        });
        Event::on('yiiunit\framework\base\SomeInterface', SomeInterface::EVENT_SUPER_EVENT, function ($event): void {
            $this->counter += 5;
        });
        $this->assertEquals(0, $this->counter);
        $post = new Post();
        $post->save();
        $this->assertEquals(4, $this->counter);
        $user = new User();
        $user->save();
        $this->assertEquals(7, $this->counter);
        $someClass = new SomeClass();
        $someClass->emitEvent();
        $this->assertEquals(12, $this->counter);
        $childClass = new SomeSubclass();
        $childClass->emitEventInSubclass();
        $this->assertEquals(17, $this->counter);
    }

    public function testOff(): void
    {
        $handler = function ($event): void {
            ++$this->counter;
        };
        $this->assertFalse(Event::hasHandlers(Post::className(), 'save'));
        Event::on(Post::className(), 'save', $handler);
        $this->assertTrue(Event::hasHandlers(Post::className(), 'save'));
        Event::off(Post::className(), 'save', $handler);
        $this->assertFalse(Event::hasHandlers(Post::className(), 'save'));
    }

    public function testHasHandlers(): void
    {
        $this->assertFalse(Event::hasHandlers(Post::className(), 'save'));
        $this->assertFalse(Event::hasHandlers(ActiveRecord::className(), 'save'));
        $this->assertFalse(Event::hasHandlers('yiiunit\framework\base\SomeInterface', SomeInterface::EVENT_SUPER_EVENT));
        Event::on(Post::className(), 'save', function ($event): void {
            ++$this->counter;
        });
        Event::on('yiiunit\framework\base\SomeInterface', SomeInterface::EVENT_SUPER_EVENT, function ($event): void {
            ++$this->counter;
        });
        $this->assertTrue(Event::hasHandlers(Post::className(), 'save'));
        $this->assertFalse(Event::hasHandlers(ActiveRecord::className(), 'save'));

        $this->assertFalse(Event::hasHandlers(User::className(), 'save'));
        Event::on(ActiveRecord::className(), 'save', function ($event): void {
            ++$this->counter;
        });
        $this->assertTrue(Event::hasHandlers(User::className(), 'save'));
        $this->assertTrue(Event::hasHandlers(ActiveRecord::className(), 'save'));
        $this->assertTrue(Event::hasHandlers('yiiunit\framework\base\SomeInterface', SomeInterface::EVENT_SUPER_EVENT));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/17336
     */
    public function testHasHandlersWithWildcard(): void
    {
        Event::on('\yiiunit\framework\base\*', 'save.*', static function ($event): void {
            // do nothing
        });

        $this->assertTrue(Event::hasHandlers('yiiunit\framework\base\SomeInterface', 'save.it'), 'save.it');
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/17300
     */
    public function testRunHandlersWithWildcard(): void
    {
        $triggered = false;

        Event::on('\yiiunit\framework\base\*', 'super*', static function ($event) use (&$triggered): void {
            $triggered = true;
        });

        // instance-level
        $this->assertFalse($triggered);
        $someClass = new SomeClass();
        $someClass->emitEvent();
        $this->assertTrue($triggered);

        // reset
        $triggered = false;

        // class-level
        $this->assertFalse($triggered);
        Event::trigger(SomeClass::className(), 'super.test');
        $this->assertTrue($triggered);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/17377
     */
    public function testNoFalsePositivesWithHasHandlers(): void
    {
        $this->assertFalse(Event::hasHandlers(new stdClass(), 'foobar'));

        $component = new Component();
        $this->assertFalse($component->hasEventHandlers('foobar'));
    }

    public function testOffUnmatchedHandler(): void
    {
        $this->assertFalse(Event::hasHandlers(Post::className(), 'afterSave'));
        Event::on(Post::className(), 'afterSave', [$this, 'bla-bla']);
        $this->assertFalse(Event::off(Post::className(), 'afterSave', [$this, 'bla-bla-bla']));
        $this->assertTrue(Event::off(Post::className(), 'afterSave', [$this, 'bla-bla']));
    }

    /**
     * @depends testOn
     * @depends testHasHandlers
     */
    public function testOnWildcard(): void
    {
        Event::on(Post::className(), '*', function ($event): void {
            ++$this->counter;
        });
        Event::on('*\Post', 'save', function ($event): void {
            $this->counter += 3;
        });

        $post = new Post();
        $post->save();
        $this->assertEquals(4, $this->counter);

        $this->assertTrue(Event::hasHandlers(Post::className(), 'save'));
    }

    /**
     * @depends testOnWildcard
     * @depends testOff
     */
    public function testOffWildcard(): void
    {
        $handler = function ($event): void {
            ++$this->counter;
        };
        $this->assertFalse(Event::hasHandlers(Post::className(), 'save'));
        Event::on('*\Post', 'save', $handler);
        $this->assertTrue(Event::hasHandlers(Post::className(), 'save'));
        Event::off('*\Post', 'save', $handler);
        $this->assertFalse(Event::hasHandlers(Post::className(), 'save'));
    }
}

class ActiveRecord extends Component
{
    public function save(): void
    {
        $this->trigger('save');
    }
}

class Post extends ActiveRecord
{
}

class User extends ActiveRecord
{
}

interface SomeInterface
{
    public const EVENT_SUPER_EVENT = 'superEvent';
}

class SomeClass extends Component implements SomeInterface
{
    public function emitEvent(): void
    {
        $this->trigger(self::EVENT_SUPER_EVENT);
    }
}

class SomeSubclass extends SomeClass
{
    public function emitEventInSubclass(): void
    {
        $this->trigger(self::EVENT_SUPER_EVENT);
    }
}
