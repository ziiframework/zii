<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use RuntimeException;
use Yii;
use yii\base\InlineAction;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yiiunit\framework\web\stubs\VendorImage;
use yiiunit\TestCase;

/**
 * @group web
 *
 * @internal
 * @coversNothing
 */
final class ControllerTest extends TestCase
{
    /** @var FakeController */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebApplication();
        $this->controller = new FakeController('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));

        Yii::$app->controller = $this->controller;
    }

    public function testBindActionParams(): void
    {
        $aksi1 = new InlineAction('aksi1', $this->controller, 'actionAksi1');

        $params = ['fromGet' => 'from query params', 'q' => 'd426', 'validator' => 'available'];
        [$fromGet, $other] = $this->controller->bindActionParams($aksi1, $params);
        $this->assertSame('from query params', $fromGet);
        $this->assertSame('default', $other);

        $params = ['fromGet' => 'from query params', 'q' => 'd426', 'other' => 'available'];
        [$fromGet, $other] = $this->controller->bindActionParams($aksi1, $params);
        $this->assertSame('from query params', $fromGet);
        $this->assertSame('available', $other);
    }

    public function testNullableInjectedActionParams(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }

        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,

            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        $this->mockWebApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionNullableInjection');
        $params = [];
        $args = $this->controller->bindActionParams($injectionAction, $params);
        $this->assertSame(Yii::$app->request, $args[0]);
        $this->assertNull($args[1]);
    }

    public function testModelBindingHttpException(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }

        $this->controller = new FakePhp71Controller('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'container' => [
                'definitions' => [
                    \yiiunit\framework\web\stubs\ModelBindingStub::className() => [\yiiunit\framework\web\stubs\ModelBindingStub::className(), 'build'],
                ],
            ],
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        Yii::$container->set(VendorImage::className(), VendorImage::className());
        $this->mockWebApplication(['controller' => $this->controller]);
        $injectionAction = new InlineAction('injection', $this->controller, 'actionModelBindingInjection');
        $this->expectException(get_class(new NotFoundHttpException('Not Found Item.')));
        $this->expectExceptionMessage('Not Found Item.');
        $this->controller->bindActionParams($injectionAction, []);
    }

    public function testInjectionContainerException(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,

            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        $this->mockWebApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionInjection');
        $params = ['between' => 'test', 'after' => 'another', 'before' => 'test'];
        Yii::$container->set(VendorImage::className(), static function (): void { throw new RuntimeException('uh oh'); });

        $this->expectException(get_class(new RuntimeException()));
        $this->expectExceptionMessage('uh oh');
        $this->controller->bindActionParams($injectionAction, $params);
    }

    public function testUnknownInjection(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        $this->mockWebApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionInjection');
        $params = ['between' => 'test', 'after' => 'another', 'before' => 'test'];
        Yii::$container->clear(VendorImage::className());
        $this->expectException(get_class(new ServerErrorHttpException()));
        $this->expectExceptionMessage('Could not load required service: vendorImage');
        $this->controller->bindActionParams($injectionAction, $params);
    }

    public function testInjectedActionParams(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        $this->mockWebApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionInjection');
        $params = ['between' => 'test', 'after' => 'another', 'before' => 'test'];
        Yii::$container->set(VendorImage::className(), VendorImage::className());
        $args = $this->controller->bindActionParams($injectionAction, $params);
        $this->assertSame($params['before'], $args[0]);
        $this->assertSame(Yii::$app->request, $args[1]);
        $this->assertSame('Component: yii\web\Request $request', Yii::$app->requestedParams['request']);
        $this->assertSame($params['between'], $args[2]);
        $this->assertInstanceOf(VendorImage::className(), $args[3]);
        $this->assertSame('Container DI: yiiunit\framework\web\stubs\VendorImage $vendorImage', Yii::$app->requestedParams['vendorImage']);
        $this->assertNull($args[4]);
        $this->assertSame('Unavailable service: post', Yii::$app->requestedParams['post']);
        $this->assertSame($params['after'], $args[5]);
    }

    public function testInjectedActionParamsFromModule(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('Can not be tested on PHP < 7.1');

            return;
        }
        $module = new \yii\base\Module('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        $module->set('yii\data\DataProviderInterface', [
            'class' => \yii\data\ArrayDataProvider::className(),
        ]);
        // Use the PHP71 controller for this test
        $this->controller = new FakePhp71Controller('fake', $module);
        $this->mockWebApplication(['controller' => $this->controller]);

        $injectionAction = new InlineAction('injection', $this->controller, 'actionModuleServiceInjection');
        $args = $this->controller->bindActionParams($injectionAction, []);
        $this->assertInstanceOf(\yii\data\ArrayDataProvider::className(), $args[0]);
        $this->assertSame('Module yii\base\Module DI: yii\data\DataProviderInterface $dataProvider', Yii::$app->requestedParams['dataProvider']);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/17701
     */
    public function testBindTypedActionParams(): void
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Can not be tested on PHP < 7.0');

            return;
        }

        // Use the PHP7 controller for this test
        $this->controller = new FakePhp7Controller('fake', new \yii\web\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]));
        $this->mockWebApplication(['controller' => $this->controller]);

        $aksi1 = new InlineAction('aksi1', $this->controller, 'actionAksi1');

        $params = ['foo' => '100', 'bar' => null, 'true' => 'on', 'false' => 'false'];
        [$foo, $bar, $true, $false] = $this->controller->bindActionParams($aksi1, $params);
        $this->assertSame(100, $foo);
        $this->assertNull($bar);
        $this->assertTrue($true);
        $this->assertFalse($false);

        // allow nullable argument to be set to empty string (as null)
        // https://github.com/yiisoft/yii2/issues/18450
        $params = ['foo' => 100, 'bar' => '', 'true' => true, 'false' => true];
        [, $bar] = $this->controller->bindActionParams($aksi1, $params);
        $this->assertNull($bar);

        // make sure nullable string argument is not set to null when empty string is passed
        $stringy = new InlineAction('stringy', $this->controller, 'actionStringy');
        [$foo] = $this->controller->bindActionParams($stringy, ['foo' => '']);
        $this->assertSame('', $foo);

        $params = ['foo' => 'oops', 'bar' => null];
        $this->expectException('yii\web\BadRequestHttpException');
        $this->expectExceptionMessage('Invalid data received for parameter "foo".');
        $this->controller->bindActionParams($aksi1, $params);
    }

    public function testAsJson(): void
    {
        $data = [
            'test' => 123,
            'example' => 'data',
        ];
        $result = $this->controller->asJson($data);
        $this->assertInstanceOf('yii\web\Response', $result);
        $this->assertSame(Yii::$app->response, $result, 'response should be the same as Yii::$app->response');
        $this->assertSame(Response::FORMAT_JSON, $result->format);
        $this->assertSame($data, $result->data);
    }

    public function testAsXml(): void
    {
        $data = [
            'test' => 123,
            'example' => 'data',
        ];
        $result = $this->controller->asXml($data);
        $this->assertInstanceOf('yii\web\Response', $result);
        $this->assertSame(Yii::$app->response, $result, 'response should be the same as Yii::$app->response');
        $this->assertSame(Response::FORMAT_XML, $result->format);
        $this->assertSame($data, $result->data);
    }

    public function testRedirect(): void
    {
        $_SERVER['REQUEST_URI'] = 'http://test-domain.com/';
        $this->assertSame($this->controller->redirect('')->headers->get('location'), '/');
        $this->assertSame($this->controller->redirect('http://some-external-domain.com')->headers->get('location'), 'http://some-external-domain.com');
        $this->assertSame($this->controller->redirect('/')->headers->get('location'), '/');
        $this->assertSame($this->controller->redirect('/something-relative')->headers->get('location'), '/something-relative');
        $this->assertSame($this->controller->redirect(['/'])->headers->get('location'), '/index.php?r=');
        $this->assertSame($this->controller->redirect(['view'])->headers->get('location'), '/index.php?r=fake%2Fview');
        $this->assertSame($this->controller->redirect(['/controller'])->headers->get('location'), '/index.php?r=controller');
        $this->assertSame($this->controller->redirect(['/controller/index'])->headers->get('location'), '/index.php?r=controller%2Findex');
        $this->assertSame($this->controller->redirect(['//controller/index'])->headers->get('location'), '/index.php?r=controller%2Findex');
        $this->assertSame($this->controller->redirect(['//controller/index', 'id' => 3])->headers->get('location'), '/index.php?r=controller%2Findex&id=3');
        $this->assertSame($this->controller->redirect(['//controller/index', 'id_1' => 3, 'id_2' => 4])->headers->get('location'), '/index.php?r=controller%2Findex&id_1=3&id_2=4');
        $this->assertSame($this->controller->redirect(['//controller/index', 'slug' => 'äöüß!"§$%&/()'])->headers->get('location'), '/index.php?r=controller%2Findex&slug=%C3%A4%C3%B6%C3%BC%C3%9F%21%22%C2%A7%24%25%26%2F%28%29');
    }
}
