<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mysql;

use yii\db\Query;
use yii\helpers\Json;
use yii\db\JsonExpression;
use yii\db\ExpressionInterface;
use yii\db\ExpressionBuilderTrait;
use yii\db\ExpressionBuilderInterface;

/**
 * Class JsonExpressionBuilder builds [[JsonExpression]] for MySQL DBMS.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @since 2.0.14
 */
class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    public const PARAM_PREFIX = ':qp';

    /**
     * {@inheritdoc}
     *
     * @param JsonExpression|ExpressionInterface $expression the expression to be built
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $value = $expression->getValue();

        if ($value instanceof Query) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);

            return "($sql)";
        }

        $placeholder = static::PARAM_PREFIX . count($params);
        $params[$placeholder] = Json::encode($value);

        return "CAST($placeholder AS JSON)";
    }
}
