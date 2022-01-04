<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use yii\web\HeaderCollection;
use yiiunit\TestCase;

/**
 * @group web
 */
class HeaderCollectionTest extends TestCase
{
    public function testFromArray(): void
    {
        $headerCollection = new HeaderCollection();
        $location = 'my-test-location';
        $headerCollection->fromArray([
            'Location' => [$location],
        ]);
        $this->assertEquals($location, $headerCollection->get('Location'));
    }
}
