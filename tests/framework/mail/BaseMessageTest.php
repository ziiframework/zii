<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\mail;

use Yii;
use yii\mail\BaseMailer;
use yii\mail\BaseMessage;
use yiiunit\TestCase;

/**
 * @group mail
 *
 * @internal
 * @coversNothing
 */
final class BaseMessageTest extends TestCase
{
    protected function setUp(): void
    {
        $this->mockApplication([
            'components' => [
                'mailer' => $this->createTestEmailComponent(),
            ],
        ]);
    }

    // Tests :

    public function testSend(): void
    {
        $mailer = $this->getMailer();
        $message = $mailer->compose();
        $message->send($mailer);
        $this->assertSame($message, $mailer->sentMessages[0], 'Unable to send message!');
    }

    public function testToString(): void
    {
        $mailer = $this->getMailer();
        $message = $mailer->compose();
        $this->assertSame($message->toString(), '' . $message);
    }

    /**
     * @return Mailer test email component instance
     */
    protected function createTestEmailComponent()
    {
        return new TestMailer();
    }

    /**
     * @return TestMailer mailer instance
     */
    protected function getMailer()
    {
        return Yii::$app->get('mailer');
    }
}

/**
 * Test Mailer class.
 */
class TestMailer extends BaseMailer
{
    public $messageClass = 'yiiunit\framework\mail\TestMessage';
    public $sentMessages = [];

    protected function sendMessage($message): void
    {
        $this->sentMessages[] = $message;
    }
}

/**
 * Test Message class.
 */
class TestMessage extends BaseMessage
{
    public $text;
    public $html;

    public function getCharset()
    {
        return '';
    }

    public function setCharset($charset): void
    {
    }

    public function getFrom()
    {
        return '';
    }

    public function setFrom($from): void
    {
    }

    public function getReplyTo()
    {
        return '';
    }

    public function setReplyTo($replyTo): void
    {
    }

    public function getTo()
    {
        return '';
    }

    public function setTo($to): void
    {
    }

    public function getCc()
    {
        return '';
    }

    public function setCc($cc): void
    {
    }

    public function getBcc()
    {
        return '';
    }

    public function setBcc($bcc): void
    {
    }

    public function getSubject()
    {
        return '';
    }

    public function setSubject($subject): void
    {
    }

    public function setTextBody($text): void
    {
        $this->text = $text;
    }

    public function setHtmlBody($html): void
    {
        $this->html = $html;
    }

    public function attachContent($content, array $options = []): void
    {
    }

    public function attach($fileName, array $options = []): void
    {
    }

    public function embed($fileName, array $options = []): void
    {
    }

    public function embedContent($content, array $options = []): void
    {
    }

    public function toString()
    {
        return static::class;
    }
}
