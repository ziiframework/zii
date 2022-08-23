<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit;

/**
 * Class ResultPrinter overrides \PHPUnit\TextUI\ResultPrinter constructor
 * to change default output to STDOUT and prevent some tests from fail when
 * they can not be executed after headers have been sent.
 */
class ResultPrinter extends \PHPUnit\TextUI\DefaultResultPrinter
{
    private bool $is_stdout = false;

    public function __construct($out = null, $verbose = false, $colors = \PHPUnit\TextUI\DefaultResultPrinter::COLOR_DEFAULT, $debug = false, $numberOfColumns = 80, $reverse = false)
    {
        if ($out === null) {
            $out = STDOUT;
        }

        parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns, $reverse);

        // https://github.com/sebastianbergmann/phpunit/blob/8.5/src/Util/Printer.php#L64-L88
        if (is_resource($out)) {
            $this->is_stdout = $out === STDOUT;
        }

        if (is_string($out)) {
            $this->is_stdout = strpos($out, 'php://stdout') === 0;
        }
    }

    public function flush(): void
    {
        if (!$this->is_stdout) {
            parent::flush();
        }
    }
}
