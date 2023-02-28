<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\web;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Parses a raw HTTP request using [[\Symfony\Component\Serializer\Serializer::decode()]].
 *
 * To enable parsing for XML requests you can configure [[Request::parsers]] using this class:
 *
 * ```php
 * 'request' => [
 *     'parsers' => [
 *         'application/xml' => 'yii\web\XmlParser',
 *         'text/xml' => 'yii\web\XmlParser',
 *     ]
 * ]
 * ```
 *
 * @author Ruitang Du <charescape@outlook.com>
 *
 * @since 3.0
 */
class XmlParser implements RequestParserInterface
{
    /**
     * @var string ROOT_NODE_NAME for XmlEncoder.
     */
    public string $rootNode = 'xml';

    /**
     * Parses a HTTP request body.
     *
     * @param string $rawBody the raw HTTP request body.
     * @param string $contentType the content type specified for the request body.
     *
     * @return array parameters parsed from the request body
     */
    public function parse($rawBody, $contentType): array
    {
        $encoder = new XmlEncoder([XmlEncoder::ROOT_NODE_NAME => $this->rootNode, XmlEncoder::ENCODING => 'UTF-8']);

        $serializer = new Serializer([], [$encoder]);

        return $serializer->decode($rawBody, 'xml');
    }
}
