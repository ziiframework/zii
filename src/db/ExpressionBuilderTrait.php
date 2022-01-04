<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * Trait ExpressionBuilderTrait provides common constructor for classes that
 * should implement [[ExpressionBuilderInterface]].
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @since 2.0.14
 */
trait ExpressionBuilderTrait
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * ExpressionBuilderTrait constructor.
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
