<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use Yii;
use Error;
use Exception;
use yii\web\Cookie;
use RuntimeException;
use yii\web\Response;
use yii\web\HttpException;
use yii\helpers\StringHelper;
use yiiunit\framework\web\mocks\TestRequestComponent;

/**
 * @group web
 */
class ResponseTest extends \yiiunit\TestCase
{
    /**
     * @var \yii\web\Response
     */
    public $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockWebApplication([
            'components' => [
                'request' => [
                    'class' => TestRequestComponent::className(),
                ],
            ],
        ]);
        $this->response = new \yii\web\Response();
    }

    public function rightRanges()
    {
        // TODO test more cases for range requests and check for rfc compatibility
        // https://tools.ietf.org/html/rfc2616
        return [
            ['0-5', '0-5', 6, '12ёж'],
            ['2-', '2-66', 65, 'ёжик3456798áèabcdefghijklmnopqrstuvwxyz!"§$%&/(ёжик)=?'],
            ['-12', '55-66', 12, '(ёжик)=?'],
        ];
    }

    /**
     * @dataProvider rightRanges
     *
     * @param string $rangeHeader
     * @param string $expectedHeader
     * @param int $length
     * @param string $expectedContent
     */
    public function testSendFileRanges($rangeHeader, $expectedHeader, $length, $expectedContent): void
    {
        $dataFile = Yii::getAlias('@yiiunit/data/web/data.txt');
        $fullContent = file_get_contents($dataFile);
        $_SERVER['HTTP_RANGE'] = 'bytes=' . $rangeHeader;
        ob_start();
        $this->response->sendFile($dataFile)->send();
        $content = ob_get_clean();

        $this->assertEquals($expectedContent, $content);
        $this->assertEquals(206, $this->response->statusCode);
        $headers = $this->response->headers;
        $this->assertEquals('bytes', $headers->get('Accept-Ranges'));
        $this->assertEquals('bytes ' . $expectedHeader . '/' . StringHelper::byteLength($fullContent), $headers->get('Content-Range'));
        $this->assertEquals('text/plain', $headers->get('Content-Type'));
        $this->assertEquals((string) $length, $headers->get('Content-Length'));
    }

    public function wrongRanges()
    {
        // TODO test more cases for range requests and check for rfc compatibility
        // https://tools.ietf.org/html/rfc2616
        return [
            ['1-2,3-5,6-10'], // multiple range request not supported
            ['5-1'],          // last-byte-pos value is less than its first-byte-pos value
            ['-100000'],      // last-byte-pos bigger then content length
            ['10000-'],       // first-byte-pos bigger then content length
        ];
    }

    /**
     * @dataProvider wrongRanges
     *
     * @param string $rangeHeader
     */
    public function testSendFileWrongRanges($rangeHeader): void
    {
        $this->expectException('yii\web\RangeNotSatisfiableHttpException');

        $dataFile = Yii::getAlias('@yiiunit/data/web/data.txt');
        $_SERVER['HTTP_RANGE'] = 'bytes=' . $rangeHeader;
        $this->response->sendFile($dataFile);
    }

    protected function generateTestFileContent()
    {
        return '12ёжик3456798áèabcdefghijklmnopqrstuvwxyz!"§$%&/(ёжик)=?';
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/7529
     */
    public function testSendContentAsFile(): void
    {
        ob_start();
        $this->response->sendContentAsFile('test', 'test.txt')->send();
        $content = ob_get_clean();

        $this->assertEquals('test', $content);
        $this->assertEquals(200, $this->response->statusCode);
        $headers = $this->response->headers;
        $this->assertEquals('application/octet-stream', $headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="test.txt"', $headers->get('Content-Disposition'));
        $this->assertEquals(4, $headers->get('Content-Length'));
    }

    public function testRedirect(): void
    {
        $_SERVER['REQUEST_URI'] = 'http://test-domain.com/';
        $this->assertEquals('/', $this->response->redirect('')->headers->get('location'));
        $this->assertFalse($this->response->redirect(null)->headers->get('location'));
        $this->assertEquals('http://some-external-domain.com', $this->response->redirect('http://some-external-domain.com')->headers->get('location'));
        $this->assertEquals('/', $this->response->redirect('/')->headers->get('location'));
        $this->assertEquals('/something-relative', $this->response->redirect('/something-relative')->headers->get('location'));
        $this->assertEquals('/index.php?r=', $this->response->redirect(['/'])->headers->get('location'));
        $this->assertEquals('/index.php?r=view', $this->response->redirect(['view'])->headers->get('location'));
        $this->assertEquals('/index.php?r=controller', $this->response->redirect(['/controller'])->headers->get('location'));
        $this->assertEquals('/index.php?r=controller%2Findex', $this->response->redirect(['/controller/index'])->headers->get('location'));
        $this->assertEquals('/index.php?r=controller%2Findex', $this->response->redirect(['//controller/index'])->headers->get('location'));
        $this->assertEquals('/index.php?r=controller%2Findex&id=3', $this->response->redirect(['//controller/index', 'id' => 3])->headers->get('location'));
        $this->assertEquals('/index.php?r=controller%2Findex&id_1=3&id_2=4', $this->response->redirect(['//controller/index', 'id_1' => 3, 'id_2' => 4])->headers->get('location'));
        $this->assertEquals('/index.php?r=controller%2Findex&slug=%C3%A4%C3%B6%C3%BC%C3%9F%21%22%C2%A7%24%25%26%2F%28%29', $this->response->redirect(['//controller/index', 'slug' => 'äöüß!"§$%&/()'])->headers->get('location'));
    }

    /**
     * @dataProvider dataProviderAjaxRedirectInternetExplorer11
     */
    public function testAjaxRedirectInternetExplorer11($userAgent, $statusCodes): void
    {
        $_SERVER['REQUEST_URI'] = 'http://test-domain.com/';
        $request = Yii::$app->request;
        /* @var $request TestRequestComponent */
        $request->getIssAjaxOverride = true;
        $request->getUserAgentOverride = $userAgent;

        foreach ([true, false] as $pjaxOverride) {
            $request->getIsPjaxOverride = $pjaxOverride;

            foreach (['GET', 'POST'] as $methodOverride) {
                $request->getMethodOverride = $methodOverride;

                foreach ($statusCodes as $statusCode => $expectStatusCode) {
                    $this->assertEquals($expectStatusCode, $this->response->redirect(['view'], $statusCode)->statusCode);
                }
            }
        }
    }

    /**
     * @link https://blogs.msdn.microsoft.com/ieinternals/2013/09/21/internet-explorer-11s-many-user-agent-strings/
     * @link https://stackoverflow.com/questions/30591706/what-is-the-user-agent-string-name-for-microsoft-edge/31279980#31279980
     * @link https://developers.whatismybrowser.com/useragents/explore/software_name/chrome/
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent/Firefox
     *
     * @return array
     */
    public function dataProviderAjaxRedirectInternetExplorer11()
    {
        return [
            ['Mozilla/5.0 (Android 4.4; Mobile; rv:41.0) Gecko/41.0 Firefox/41.0', [301 => 301, 302 => 302]], // Firefox
            ['Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko', [301 => 200, 302 => 200]], // IE 11
            [ // IE 11
                'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C; rv:11.0) like Gecko',
                [301 => 200, 302 => 200],
            ],
            [ // Chrome
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36',
                [301 => 301, 302 => 302],
            ],
            [ // Edge
                'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10136',
                [301 => 301, 302 => 302],
            ],
            [ // special windows versions (for tablets or IoT devices)
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.2; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; Tablet PC 2.0)',
                [301 => 200, 302 => 200],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderSetStatusCodeByException
     *
     * @param Exception $exception
     * @param int $statusCode
     */
    public function testSetStatusCodeByException($exception, $statusCode): void
    {
        $this->response->setStatusCodeByException($exception);
        $this->assertEquals($statusCode, $this->response->getStatusCode());
    }

    /**
     * @see https://github.com/yiisoft/yii2/pull/18290
     */
    public function testNonSeekableStream(): void
    {
        $stream = fopen('php://output', 'r+b');
        ob_start();
        $this->response->sendStreamAsFile($stream, 'test-stream')->send();
        ob_get_clean();
        $this->assertEquals(200, $this->response->statusCode);
    }

    public function dataProviderSetStatusCodeByException()
    {
        $data = [
            [
                new Exception(),
                500,
            ],
            [
                new RuntimeException(),
                500,
            ],
            [
                new HttpException(500),
                500,
            ],
            [
                new HttpException(403),
                403,
            ],
            [
                new HttpException(404),
                404,
            ],
            [
                new HttpException(301),
                301,
            ],
            [
                new HttpException(200),
                200,
            ],
        ];

        if (class_exists('Error')) {
            $data[] = [
                new Error(),
                500,
            ];
        }

        return $data;
    }

    public function formatDataProvider()
    {
        return [
            [Response::FORMAT_JSON, '{"value":1}'],
            [Response::FORMAT_HTML, '<html><head><title>Test</title></head><body>Test Body</body></html>'],
            [Response::FORMAT_XML, '<?xml ?><test></test>'],
            [Response::FORMAT_RAW, 'Something'],
        ];
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testSkipFormatter($format, $content): void
    {
        $response = new Response();
        $response->format = $format;
        $response->content = $content;
        ob_start();
        $response->send();
        $actualContent = ob_get_clean();

        $this->assertSame($content, $actualContent);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/17094
     */
    public function testEmptyContentOn204(): void
    {
        $this->assertEmptyContentOn(204);
    }

    public function testSettingContentToNullOn204(): void
    {
        $this->assertEmptyContentOn(204, function ($response): void {
            /* @var $response Response */
            $this->assertSame($response->content, '');
        });
    }

    public function testSettingStreamToNullOn204(): void
    {
        $this->assertSettingStreamToNullOn(204);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/18199
     */
    public function testEmptyContentOn304(): void
    {
        $this->assertEmptyContentOn(304);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/18199
     */
    public function testSettingContentToNullOn304(): void
    {
        $this->assertEmptyContentOn(304, function ($response): void {
            /* @var $response Response */
            $this->assertSame($response->content, '');
        });
    }

    public function testSettingStreamToNullOn304(): void
    {
        $this->assertSettingStreamToNullOn(304);
    }

    public function testSendFileWithInvalidCharactersInFileName(): void
    {
        $response = new Response();
        $dataFile = Yii::getAlias('@yiiunit/data/web/data.txt');

        $response->sendFile($dataFile, "test\x7Ftest.txt");

        $this->assertSame("attachment; filename=\"test_test.txt\"; filename*=utf-8''test%7Ftest.txt", $response->headers['content-disposition']);
    }

    public function testSameSiteCookie(): void
    {
        $response = new Response();
        $response->cookies->add(new Cookie([
            'name' => 'test',
            'value' => 'testValue',
            'sameSite' => Cookie::SAME_SITE_STRICT,
        ]));

        ob_start();
        $response->send();
        $content = ob_get_clean();

        // Only way to test is that it doesn't create any errors
        $this->assertEquals('', $content);
    }

    /**
     * Asserts that given a status code, the response will have an empty content body. If the lambda is present, it will
     * call the lambda what is supposed to handle other assertions.
     *
     * @param int $statusCode
     * @param callable|null $callback lambda in charge to handle other assertions
     *                                callable(\yii\web\Response $response):void
     */
    protected function assertEmptyContentOn($statusCode, $callback = null): void
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
        $response->content = 'not empty content';

        ob_start();
        $response->send();
        $content = ob_get_clean();
        $this->assertSame($content, '');

        if ($callback && is_callable($callback)) {
            $callback($response);
        }
    }

    /**
     * Asserts that given a status code, the response will have an empty content body, no matter
     * if the response is a stream as file.
     *
     * @param int $statusCode
     */
    protected function assertSettingStreamToNullOn($statusCode): void
    {
        $response = new Response();
        $dataFile = Yii::getAlias('@yiiunit/data/web/data.txt');

        $response->sendFile($dataFile);
        $response->setStatusCode($statusCode);

        ob_start();
        $response->send();
        $content = ob_get_clean();
        $this->assertSame($content, '');
        $this->assertNull($response->stream);
    }
}
