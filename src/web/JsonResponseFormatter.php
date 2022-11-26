<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\web;

use const JSON_PRETTY_PRINT;

use Yii;
use yii\helpers\Json;
use yii\base\Component;

use function sprintf;
use function is_array;
use function str_contains;

/**
 * JsonResponseFormatter formats the given data into a JSON or JSONP response content.
 *
 * It is used by [[Response]] to format response data.
 *
 * To configure properties like [[encodeOptions]] or [[prettyPrint]], you can configure the `response`
 * application component like the following:
 *
 * ```php
 * 'response' => [
 *     // ...
 *     'formatters' => [
 *         \yii\web\Response::FORMAT_JSON => [
 *              'class' => 'yii\web\JsonResponseFormatter',
 *              'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
 *              'keepObjectType' => false, // keep object type for zero-indexed objects
 *              // ...
 *         ],
 *     ],
 * ],
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class JsonResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * JSON Content Type.
     *
     * @since 2.0.14
     */
    public const CONTENT_TYPE_JSONP = 'application/javascript; charset=UTF-8';

    /**
     * JSONP Content Type.
     *
     * @since 2.0.14
     */
    public const CONTENT_TYPE_JSON = 'application/json; charset=UTF-8';

    /**
     * HAL JSON Content Type.
     *
     * @since 2.0.14
     */
    public const CONTENT_TYPE_HAL_JSON = 'application/hal+json; charset=UTF-8';

    /**
     * @var string|null custom value of the `Content-Type` header of the response.
     * When equals `null` default content type will be used based on the `useJsonp` property.
     *
     * @since 2.0.14
     */
    public $contentType;

    /**
     * @var bool whether to use JSONP response format. When this is true, the [[Response::data|response data]]
     * must be an array consisting of `data` and `callback` members. The latter should be a JavaScript
     * function name while the former will be passed to this function as a parameter.
     */
    public $useJsonp = false;

    /**
     * @var int the encoding options passed to [[Json::encode()]]. For more details please refer to
     * <https://www.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     * This property has no effect, when [[useJsonp]] is `true`.
     *
     * @since 2.0.7
     */
    public $encodeOptions = 320;

    /**
     * @var bool whether to format the output in a readable "pretty" format. This can be useful for debugging purpose.
     * If this is true, `JSON_PRETTY_PRINT` will be added to [[encodeOptions]].
     * Defaults to `false`.
     * This property has no effect, when [[useJsonp]] is `true`.
     *
     * @since 2.0.7
     */
    public $prettyPrint = false;

    /**
     * @var bool Avoids objects with zero-indexed keys to be encoded as array
     * Json::encode((object)['test']) will be encoded as an object not array. This matches the behaviour of json_encode().
     * Defaults to Json::$keepObjectType value
     *
     * @since 2.0.44
     */
    public $keepObjectType;

    /**
     * Formats the specified response.
     *
     * @param Response $response the response to be formatted.
     */
    public function format($response): void
    {
        if ($this->contentType === null) {
            $this->contentType = $this->useJsonp
                ? self::CONTENT_TYPE_JSONP
                : self::CONTENT_TYPE_JSON;
        } elseif (!str_contains($this->contentType, 'charset')) {
            $this->contentType .= '; charset=UTF-8';
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);

        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }

    /**
     * Formats response data in JSON format.
     *
     * @param Response $response
     */
    protected function formatJson($response): void
    {
        if ($response->data !== null) {
            $options = $this->encodeOptions;

            if ($this->prettyPrint) {
                $options |= JSON_PRETTY_PRINT;
            }

            $default = Json::$keepObjectType;

            if ($this->keepObjectType !== null) {
                Json::$keepObjectType = $this->keepObjectType;
            }

            $response->content = Json::encode($response->data, $options);

            // Restore default value to avoid any unexpected behaviour
            Json::$keepObjectType = $default;
        } elseif ($response->content === null) {
            $response->content = 'null';
        }
    }

    /**
     * Formats response data in JSONP format.
     *
     * @param Response $response
     */
    protected function formatJsonp($response): void
    {
        if (is_array($response->data)
            && isset($response->data['data'], $response->data['callback'])
        ) {
            $response->content = sprintf('%s(%s);', $response->data['callback'], Json::htmlEncode($response->data['data']));
        } elseif ($response->data !== null) {
            $response->content = '';
            Yii::warning("The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.", __METHOD__);
        }
    }
}
