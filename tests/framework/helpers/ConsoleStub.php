<?php declare(strict_types=1);
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yiiunit\framework\helpers;

use const STDERR;
use const STDIN;
use const STDOUT;
use yii\helpers\Console;

/**
 * Console helper stub for STDIN/STDOUT/STDERR replacement.
 *
 * @author Pavel Dovlatov <mysterydragon@yandex.ru>
 */
class ConsoleStub extends Console
{
    /**
     * @var resource input stream
     */
    public static $inputStream = STDIN;

    /**
     * @var resource output stream
     */
    public static $outputStream = STDOUT;

    /**
     * @var resource error stream
     */
    public static $errorStream = STDERR;

    /**
     * @inheritDoc
     */
    public static function stdin($raw = false)
    {
        return $raw ? fgets(self::$inputStream) : rtrim(fgets(self::$inputStream), PHP_EOL);
    }

    /**
     * @inheritDoc
     */
    public static function stdout($string)
    {
        return fwrite(self::$outputStream, $string);
    }

    /**
     * @inheritDoc
     */
    public static function stderr($string)
    {
        return fwrite(self::$errorStream, $string);
    }
}
