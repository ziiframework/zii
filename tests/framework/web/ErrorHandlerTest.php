<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use Yii;
use Exception;
use yii\web\View;
use yiiunit\TestCase;
use yii\web\NotFoundHttpException;

class ErrorHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockWebApplication([
            'controllerNamespace' => 'yiiunit\\data\\controllers',
            'components' => [
                'errorHandler' => [
                    'class' => 'yiiunit\framework\web\ErrorHandler',
                    'errorView' => '@yiiunit/data/views/errorHandler.php',
                    'exceptionView' => '@yiiunit/data/views/errorHandlerForAssetFiles.php',
                ],
            ],
        ]);
    }

    public function testCorrectResponseCodeInErrorView(): void
    {
        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();
        ob_start(); // suppress response output
        $this->invokeMethod($handler, 'renderException', [new NotFoundHttpException('This message is displayed to end user')]);
        ob_get_clean();
        $out = Yii::$app->response->data;
        $this->assertEqualsWithoutLE('Code: 404
Message: This message is displayed to end user
Exception: yii\web\NotFoundHttpException', $out);
    }

    public function testClearAssetFilesInErrorView(): void
    {
        Yii::$app->getView()->registerJsFile('somefile.js');

        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();
        ob_start(); // suppress response output
        $this->invokeMethod($handler, 'renderException', [new Exception('Some Exception')]);
        ob_get_clean();
        $out = Yii::$app->response->data;
        $this->assertEqualsWithoutLE('Exception View
', $out);
    }

    public function testClearAssetFilesInErrorActionView(): void
    {
        Yii::$app->getErrorHandler()->errorAction = 'test/error';
        Yii::$app->getView()->registerJs("alert('hide me')", View::POS_END);

        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();
        ob_start(); // suppress response output
        $this->invokeMethod($handler, 'renderException', [new NotFoundHttpException()]);
        ob_get_clean();
        $out = Yii::$app->response->data;
        $this->assertStringNotContainsString('<script', $out);
    }

    public function testRenderCallStackItem(): void
    {
        $handler = Yii::$app->getErrorHandler();
        $handler->traceLine = '<a href="netbeans://open?file={file}&line={line}">{html}</a>';
        $file = \yii\BaseYii::getAlias('@yii/web/Application.php');

        $out = $handler->renderCallStackItem($file, 63, \yii\web\Application::className(), null, null, null);

        $this->assertStringContainsString('<a href="netbeans://open?file=' . $file . '&line=63">', $out);
    }

    public function dataHtmlEncode()
    {
        return [
            [
                "a \t=<>&\"'\x80`\n",
                "a \t=&lt;&gt;&amp;\"'�`\n",
            ],
            [
                '<b>test</b>',
                '&lt;b&gt;test&lt;/b&gt;',
            ],
            [
                '"hello"',
                '"hello"',
            ],
            [
                "'hello world'",
                "'hello world'",
            ],
            [
                'Chip&amp;Dale',
                'Chip&amp;amp;Dale',
            ],
            [
                "\t\$x=24;",
                "\t\$x=24;",
            ],
        ];
    }

    /**
     * @dataProvider dataHtmlEncode
     */
    public function testHtmlEncode($text, $expected): void
    {
        $handler = Yii::$app->getErrorHandler();

        $this->assertSame($expected, $handler->htmlEncode($text));
    }

    public function testHtmlEncodeWithUnicodeSequence(): void
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Can not be tested on PHP < 7.0');

            return;
        }

        $handler = Yii::$app->getErrorHandler();

        $text = "a \t=<>&\"'\x80\u{20bd}`\u{000a}\u{000c}\u{0000}";
        $expected = "a \t=&lt;&gt;&amp;\"'�₽`\n\u{000c}\u{0000}";

        $this->assertSame($expected, $handler->htmlEncode($text));
    }
}

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @return bool if simple HTML should be rendered
     */
    protected function shouldRenderSimpleHtml()
    {
        return false;
    }
}
