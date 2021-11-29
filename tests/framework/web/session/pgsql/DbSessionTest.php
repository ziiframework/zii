<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web\session\pgsql;

/**
 * Class DbSessionTest.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @group db
 * @group pgsql
 *
 * @internal
 * @coversNothing
 */
final class DbSessionTest extends \yiiunit\framework\web\session\AbstractDbSessionTest
{
    protected function setUp(): void
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVMs PgSQL implementation does not seem to support blob columns in the way they are used here.');
        }

        parent::setUp();
    }

    protected function getDriverNames()
    {
        return ['pgsql'];
    }
}
