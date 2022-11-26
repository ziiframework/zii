<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

use Rector\Compatibility\Rector\Class_\AttributeCompatibleAnnotationRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php81\Rector\FunctionLike\IntersectionTypesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\TypeDeclaration\Rector\FunctionLike\ParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src/base',
        // __DIR__ . '/tests',
    ]);

    // register a single rule
    // $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
    ]);

    $rectorConfig->skip([
        ParamTypeFromStrictTypedPropertyRector::class,
        ReturnTypeDeclarationRector::class,
        UnionTypesRector::class,
        MixedTypeRector::class,
        IntersectionTypesRector::class,
        AttributeCompatibleAnnotationRector::class,
        InlineConstructorDefaultToPropertyRector::class,
    ]);
};
