<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit;

use Yii;
use ReflectionClass;
use ReflectionObject;
use yii\helpers\ArrayHelper;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public static $params;

    /**
     * Clean up after test case.
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $logger = Yii::getLogger();
        $logger->flush();
    }

    /**
     * Returns a test configuration param from /data/config.php.
     *
     * @param string $name params name
     * @param mixed $default default value to use when param is not set.
     *
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require __DIR__ . '/data/config.php';
        }

        return static::$params[$name] ?? $default;
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     *
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application'): void
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => $this->getVendorPath(),
        ], $config));
    }

    protected function mockWebApplication($config = [], $appClass = '\yii\web\Application'): void
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => $this->getVendorPath(),
            'aliases' => [
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                    'isConsoleRequest' => false,
                ],
            ],
        ], $config));
    }

    protected function getVendorPath()
    {
        $vendor = dirname(__DIR__, 2) . '/vendor';

        if (!is_dir($vendor)) {
            $vendor = dirname(__DIR__, 4);
        }

        return $vendor;
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication(): void
    {
        if (Yii::$app && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        Yii::$app = null;
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE($expected, $actual, $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that a haystack contains a needle ignoring line endings.
     *
     * @param mixed $needle
     * @param mixed $haystack
     * @param string $message
     */
    protected function assertContainsWithoutLE($needle, $haystack, $message = ''): void
    {
        $needle = str_replace("\r\n", "\n", $needle);
        $haystack = str_replace("\r\n", "\n", $haystack);

        $this->assertStringContainsString($needle, $haystack, $message);
    }

    /**
     * Invokes a inaccessible method.
     *
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     *
     * @return mixed
     *
     * @since 2.0.11
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $reflection = new ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);

        if ($revoke) {
            $method->setAccessible(false);
        }

        return $result;
    }

    /**
     * Sets an inaccessible object property to a designated value.
     *
     * @param bool $revoke whether to make property inaccessible after setting
     *
     * @since 2.0.11
     */
    protected function setInaccessibleProperty($object, $propertyName, $value, $revoke = true): void
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        if ($revoke) {
            $property->setAccessible(false);
        }
    }

    /**
     * Gets an inaccessible object property.
     *
     * @param bool $revoke whether to make property inaccessible after getting
     *
     * @return mixed
     */
    protected function getInaccessibleProperty($object, $propertyName, $revoke = true)
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);

        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }

    /**
     * Asserts that value is one of expected values.
     *
     * @param mixed $actual
     * @param string $message
     */
    public function assertIsOneOf($actual, array $expected, $message = ''): void
    {
        $this->assertThat($actual, new IsOneOfAssert($expected), $message);
    }

    /**
     * Changes db component config.
     */
    protected function switchDbConnection($db): void
    {
        $databases = $this->getParam('databases');

        if (isset($databases[$db])) {
            $database = $databases[$db];
            Yii::$app->db->close();
            Yii::$app->db->dsn = $database['dsn'] ?? null;
            Yii::$app->db->username = $database['username'] ?? null;
            Yii::$app->db->password = $database['password'] ?? null;
        }
    }
}
