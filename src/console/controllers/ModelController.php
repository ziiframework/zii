<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\console\controllers;

use Yii;
use yii\caching\ApcCache;
use yii\caching\CacheInterface;
use yii\console\Controller;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Console;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Closure;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use yii\behaviors\AttributeTypecastBehavior;
use yii\db\ActiveQuery;
use yii\db\ColumnSchema;
use yii\db\TableSchema;
use yii\helpers\Inflector;
use Zii\Util\Supports\DbSupport;
use Zii\Util\Supports\RuleSupport;

/**
 * Allows you to flush cache.
 *
 * see list of available components to flush:
 *
 *     yii cache
 *
 * flush particular components specified by their names:
 *
 *     yii cache/flush first second third
 *
 * flush all cache components that can be found in the system
 *
 *     yii cache/flush-all
 *
 * Note that the command uses cache components defined in your console application configuration file. If components
 * configured are different from web application, web application cache won't be cleared. In order to fix it please
 * duplicate web application cache components in console config. You can use any component names.
 *
 * APC is not shared between PHP processes so flushing cache from command line has no effect on web.
 * Flushing web cache could be either done by:
 *
 * - Putting a php file under web root and calling it via HTTP
 * - Using [Cachetool](http://gordalina.github.io/cachetool/)
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @author Mark Jebri <mark.github@yandex.ru>
 *
 * @since 2.0
 */
class ModelController extends Controller
{
    public string $modelNamespace = 'Zpp\\Models';
    public string $modelExtends = '\\Zpp\\Models\\BaseModel';

    public string $identityTable = 'user';

    private PhpNamespace $_namespace;

    private ClassType $_class;

    private array $_indexes = [];

    /**
     * Required eg:
     * [
     *   ['name' => NAME],
     *   ['name' => NAME],
     *   ['name' => NAME],
     * ]
     */
    private array $_ruleRequired = [];

    /**
     * Range eg:
     * [
     *   ['name' => NAME, 'range' => RANGE],
     *   ['name' => NAME, 'range' => RANGE],
     *   ['name' => NAME, 'range' => RANGE],
     * ]
     */
    private array $_ruleRange = [];

    /**
     * Boolean eg:
     * [
     *   ['name' => NAME],
     *   ['name' => NAME],
     *   ['name' => NAME],
     * ]
     */
    private array $_ruleBoolean = [];


    /**
     * Integer eg:
     * [
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     * ]
     */
    private array $_ruleInteger = [];

    /**
     * String eg:
     * [
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     * ]
     */
    private array $_ruleString = [];

    /**
     * String eg:
     * [
     *   ['name' => NAME],
     *   ['name' => NAME],
     *   ['name' => NAME],
     * ]
     */
    private array $_ruleText = [];

    /**
     * YmdHis eg:
     * [
     *   ['name' => NAME, 'format' => 'Y-m-d'],
     *   ['name' => NAME, 'format' => 'Y-m-d H:i:s'],
     *   ['name' => NAME, 'format' => 'Y'],
     * ]
     */
    private array $_ruleYmdHis = [];

    /**
     * Exist eg:
     * [
     *   ['name' => NAME, 'targetClassName' => 'Member'],
     *   ['name' => NAME, 'targetClassName' => 'Member'],
     *   ['name' => NAME, 'targetClassName' => 'Member'],
     * ]
     */
    private array $_ruleExist = [];

    /**
     * typeCast eg:
     * [
     *   attr => targetClass,
     *   attr => targetClass,
     *   attr => targetClass,
     * ]
     */
    private array $_typeCastAttributes = [];

    private static array $_dateFormat = [
        'year' => 'Y',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'datetime' => 'Y-m-d H:i:s',
        'timestamp' => 'Y-m-d H:i:s',
    ];

    /**
     * special chars replacement
     */
    private static array $_codeReplacements = [
        "'%" => '',
        "%'" => '',
        '"%' => '',
        '%"' => '',
        '""' => "''",
        '" "' => "' '",
        '\\$' => '$',
        '\\n\\t' => "\n",
        '\\n' => "\n",
        "\t" => '    ',
        '0 => ' => '',
        '1 => ' => '',

        '"{attribute}"' => "'{attribute}'",
        '"不是有效的字符"' => "'不是有效的字符'",
        '"不能少于"' => "'不能少于'",
        '"个字符"' => "'个字符'",
        '"不能超过"' => "'不能超过'",
        '"必须是整数"' => "'必须是整数'",
        '"不能小于"' => "'不能小于'",
        '"不能大于"' => "'不能大于'",
        '"不是有效的值"' => "'不是有效的值'",
        '"不能为空"' => "'不能为空'",
        '"不存在"' => "'不存在'",
        '"不能重复"' => "'不能重复'",
    ];

    private function resetAttributes(): void
    {
        $this->_indexes = [];
        $this->_ruleRequired = [];
        $this->_ruleRange = [];
        $this->_ruleBoolean = [];
        $this->_ruleInteger = [];
        $this->_ruleString = [];
        $this->_ruleYmdHis = [];
        $this->_ruleExist = [];
        $this->_typeCastAttributes = [];
    }

    public function actionIndex(string $tableName, bool $overwrite = false): void
    {
        clearstatcache();

        $this->resetAttributes();

        $this->_namespace = new PhpNamespace($this->modelNamespace);
        $this->_class = $this->_namespace->addClass(Inflector::camelize($tableName));
        $this->_class->setExtends($this->modelExtends);
        $this->_class->setFinal();
        if ($tableName === $this->identityTable) {
            // $this->_namespace->addUse('Yii');
            $this->_namespace->addUse(yii\web\IdentityInterface::class);
            $this->_class->addImplement(yii\web\IdentityInterface::class);
        }

        // Table Struct
        $_schema = Yii::$app->db->getTableSchema($tableName, true);
        if (!($_schema instanceof TableSchema)) {
            echo "Table $tableName does not exist.\n";
            exit;
        }

        // Table Comment
        $this->_class->addComment(DbSupport::getTableComment($tableName). "\n");

        // Table Indexes
        foreach (DbSupport::getTableIndexes($tableName) as $index) {
            $this->_indexes[$index['Column_name']] = (bool)($index['Non_unique'] * 1) ? 'indexed' : 'unique';
        }

        foreach ($_schema->columns as $column) {
            if (in_array($column->name, ['id', 'client_ip', 'created_at', 'updated_at'], true)) {
                continue;
            }

            // Field Comment
            $varType = $column->phpType;
            if (mb_stripos($column->dbType, 'decimal') !== false) {
                $varType = 'float';
            }
            if (mb_stripos($column->dbType, 'tinyint') !== false && preg_match('/^(is|has|can|enable)_/', $column->name)) {
                $varType = 'bool';
            }

            $this->_class->addComment(implode(' ', [
                '@property',
                $varType,
                '$' . $column->name,
                $column->comment . "[$column->dbType]" . ($column->allowNull ? '.' : '[NOT NULL].'),
                isset($this->_indexes[$column->name]) && $this->_indexes[$column->name] ? "This property is {$this->_indexes[$column->name]}." : '',
            ]));

            // Column Cast
            $this->castColumn($column);
            ++$this->_columnIdx;
        }

        // public function attributeLabels
        $this->_class->addMethod('attributeLabels')
            ->setReturnType('array')
            // ->addComment('@inheritdoc')
            ->setBody('return array_merge(parent::attributeLabels(), ?);', [
                array_diff_key(
                    array_combine(
                        array_column($_schema->columns, 'name'),
                        array_map(fn (string $value): string => "%zii_t(\"$value\")%", array_column($_schema->columns, 'comment'))
                    ), [
                    'id' => 'ID',
                    'created_at' => "%zii_t(\"创建时间\")%",
                ])
            ]);

        // public function extraFields
        $this->_class->addMethod('extraFields')
            ->setReturnType('array')
            // ->addComment('@inheritdoc')
            ->setBody('return array_merge(parent::extraFields(), ?);', [
                array_map(function (string $f): string {
                    return 'db' . ucfirst($f);
                }, array_column($this->_ruleExist, 'targetClassName')),
            ]);

        // rules
        $this->_class->addMethod('rules')
            ->setReturnType('array')
            // ->addComment('@inheritdoc')
            ->setBody('return array_merge(parent::rules(), ?);', [$this->generateRules()]);

        // identity interface implement
        if ($tableName === $this->identityTable) {
            $this->_class->addMethod('findIdentity')
                ->setReturnType('?IdentityInterface')
                ->setStatic()
                // ->addComment('@inheritdoc')
                ->setBody("return static::findOne(['id' => \$id]);")
                ->setParameters([
                    (new Parameter('id'))->setType('int'),
                ]);
            $this->_class->addMethod('findIdentityByAccessToken')
                ->setReturnType('?IdentityInterface')
                ->setStatic()
                // ->addComment('@inheritdoc')
                ->setBody("return static::findOne(['access_token' => \$token]);")
                ->setParameters([
                    (new Parameter('token'))->setType('string'),
                    (new Parameter('type'))->setType('string')->setDefaultValue(null),
                ]);
            $this->_class->addMethod('getId')
                ->setReturnType('int')
                // ->addComment('@inheritdoc')
                ->setBody('return $this->getPrimaryKey();');
            $this->_class->addMethod('getAuthKey')
                ->setReturnType('string')
                // ->addComment('@inheritdoc')
                ->setBody('return $this->identity_secret;');
            $this->_class->addMethod('validateAuthKey')
                ->setReturnType('bool')
                // ->addComment('@inheritdoc')
                ->setBody('return $this->getAuthKey() === $identity_secret;')
                ->addParameter('identity_secret');
        }

        $file = ZDIR_ROOT . '/src/Models/' . Inflector::camelize($tableName) . '.php';
        if ($overwrite === true || !file_exists($file)) {
            $objectBody = str_replace(
                array_keys(self::$_codeReplacements),
                array_values(self::$_codeReplacements),
                $this->_namespace
            );
            $objectBody = preg_replace('/["]([^$"]+)["]/u', "'$1'", $objectBody);
            $objectBody = preg_replace('/["](\s+)(\d+)(\s+)["]/u', "'$1$2$3'", $objectBody);
            $objectBody = preg_replace('/["](\s+)(\d+)["]/u', "'$1$2'", $objectBody);
            if (file_put_contents($file, "<?php\n\ndeclare(strict_types=1);\n\n" . $objectBody) !== false) {
                $fileContent = file_get_contents($file);
                $fileContent = str_replace(': \\?', ': ?', $fileContent);
                file_put_contents($file, $fileContent);
                echo '✔ Successfully created model ' . Inflector::camelize($tableName);
            } else {
                echo '✘ Failed to create model ' . Inflector::camelize($tableName);
            }
            echo "\n";
        } else {
            echo '✘ Create model ' . Inflector::camelize($tableName) . " aborted, file $file already exists\n";
        }
    }

    private int $_columnIdx = 0;

    private function castColumn(ColumnSchema $column): void
    {
        // required
        if (!$column->allowNull && $column->defaultValue === null) {
            $this->_ruleRequired[] = ['name' => $column->name];
        }

        // tinyint
        if (DbSupport::castDataType($column->dbType) === 'tinyint') {
            // $column->size === 1
            // Warning: #1681 Integer display width is deprecated and will be removed in a future release.
            if (preg_match('/^(is|has|can|enable)_/', $column->name)) {
                $this->_typeCastAttributes[$column->name] = AttributeTypecastBehavior::TYPE_BOOLEAN;
                $this->_ruleBoolean[] = ['name' => $column->name];
            } else {
                $this->_typeCastAttributes[$column->name] = AttributeTypecastBehavior::TYPE_INTEGER;
                $this->_ruleInteger[] = [
                    'name' => $column->name,
                    'max' => DbSupport::getColumnMaxValue($column),
                ];
            }
        }

        // int
        if (in_array(DbSupport::castDataType($column->dbType), ['smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
            $this->_ruleInteger[] = [
                'name' => $column->name,
                'max' => DbSupport::getColumnMaxValue($column),
            ];
        }

        // double、float、decimal TODO
        if (DbSupport::castDataType($column->dbType) === 'double') {
            $this->_ruleInteger[] = [
                'name' => $column->name,
                'max' => DbSupport::getColumnMaxValue($column),
            ];
            $this->_typeCastAttributes[$column->name] = AttributeTypecastBehavior::TYPE_INTEGER;
        }
        if (DbSupport::castDataType($column->dbType) === 'float') {
            $this->_ruleInteger[] = [
                'name' => $column->name,
                'max' => DbSupport::getColumnMaxValue($column),
            ];
            $this->_typeCastAttributes[$column->name] = AttributeTypecastBehavior::TYPE_INTEGER;
        }
        if (DbSupport::castDataType($column->dbType) === 'decimal') {
            $this->_ruleInteger[] = [
                'name' => $column->name,
                'max' => DbSupport::getColumnMaxValue($column),
            ];
            $this->_typeCastAttributes[$column->name] = AttributeTypecastBehavior::TYPE_INTEGER;
        }

        // varchar
        if (DbSupport::castDataType($column->dbType) === 'varchar') {
            $this->_ruleString[] = [
                'name' => $column->name,
                'size' => $column->size,
            ];
        }

        // text
        if (DbSupport::castDataType($column->dbType) === 'text') {
            $this->_ruleText[] = ['name' => $column->name];

            $this->_ruleString[] = [
                'name' => $column->name,
                'size' => 65535,
            ];
        }

        // enum
        if (DbSupport::castDataType($column->dbType) === 'enum') {
            $this->_ruleRange[] = [
                'name' => $column->name,
                'range' => $column->enumValues,
            ];
        }

        // set
        if (DbSupport::castDataType($column->dbType) === 'set') {
            $isMatch = preg_match('/^([\w ]+)(?:\(([^)]+)\))?$/', $column->dbType, $matches);
            if ($isMatch !== false && !empty($matches[2])) {
                $values = preg_split('/\s*,\s*/', $matches[2]);
                foreach ($values as $i => $value) {
                    $values[$i] = trim($value, "'");
                }
                $this->_ruleRange[] = [
                    'name' => $column->name,
                    'range' => $values,
                    'allowArray' => true,
                ];
            }
        }

        // year、date、time、timestamp、datetime
        if (isset(self::$_dateFormat[DbSupport::castDataType($column->dbType)])) {
            $this->_ruleYmdHis[] = [
                'name' => $column->name,
                'format' => self::$_dateFormat[DbSupport::castDataType($column->dbType)],
            ];
        }

        // xxx_id
        if (preg_match('/^([a-z0-9]+)_id$/', $column->name, $matches)) {
            $getTableComment = DbSupport::getTableComment($matches[1]);
            if ($getTableComment !== null) {
                $this->_ruleExist[] = [
                    'name' => $column->name,
                    'targetClassName' => ucfirst($matches[1]),
                    'targetClassComment' => $getTableComment,
                ];
            }
        }
    }

    private function generateRules(): array
    {
        $rules = [];

        $this->_namespace->addUse(RuleSupport::class);
        // Rule String
        if (!empty($this->_ruleString) || !empty($this->_ruleRange)) {
            $closure = new Closure();
            $closure->setBody('return RuleSupport::strOrNull($value);')
                ->setReturnType('?string')
                ->addParameter('value');

            $rules[] = [
                $this->arrayOrString(array_unique(array_merge(
                    array_column($this->_ruleString, 'name'),
                    array_column($this->_ruleRange, 'name')
                ))),
                'filter',
                'filter' => "%$closure%",
            ];
        }
        // Rule Text
        if (!empty($this->_ruleText)) {
            $closure = new Closure();
            $closure->setBody('return RuleSupport::strOrEmpty($value);')
                ->setReturnType('string')
                ->addParameter('value');

            $rules[] = [
                $this->arrayOrString(array_column($this->_ruleText, 'name')),
                'filter',
                'filter' => "%$closure%",
            ];
        }
        // Rule YmdHis
        if (!empty($this->_ruleYmdHis)) {
            $groupByFormat = [];
            foreach ($this->_ruleYmdHis as $column) {
                $groupByFormat[$column['format']][] = $column['name'];
            }
            foreach ($groupByFormat as $format => $names) {
                $closure = new Closure();
                $closure->setBody("return RuleSupport::dateOrNull(\$value, '{$format}');")
                    ->setReturnType('?string')
                    ->addParameter('value');
                $rules[] = [
                    $this->arrayOrString($names),
                    'filter',
                    'filter' => "%$closure%",
                ];
            }
        }
        // String Type & Length
        if (!empty($this->_ruleString)) {
            $groupBySize = [];
            foreach ($this->_ruleString as $column) {
                $groupBySize[$column['size']][] = $column['name'];
            }
            foreach ($groupBySize as $size => $names) {
                $max_size = (int)$size;
                $min_size = $max_size === 65535 || $max_size >= 60000 ? 0 : 1;

                $rules[] = [
                    $this->arrayOrString($names),
                    'string',
                    'min' => $min_size,
                    'max' => $max_size,
                    'message'  => '%"{attribute}" . " " . zii_t("不是有效的字符")%',
                    'tooShort' => '%"{attribute}" . " " . zii_t("不能少于") . ' . "\" $min_size \"" . '. zii_t("个字符")%',
                    'tooLong'  => '%"{attribute}" . " " . zii_t("不能超过") . ' . "\" $max_size \"" . '. zii_t("个字符")%',
                ];
            }
        }
        // Int Type & Length
        if (!empty($this->_ruleInteger)) {
            $groupBySize = [];
            foreach ($this->_ruleInteger as $column) {
                $groupBySize[$column['max']][] = $column['name'];
            }
            foreach ($groupBySize as $size => $names) {
                $rules[] = [
                    $this->arrayOrString($names),
                    'integer',
                    'integerOnly' => true,
                    'min' => 0,
                    'max' => (int)$size,
                    'message' => '%"{attribute}" . " " . zii_t("必须是整数")%',
                    'tooSmall' => '%"{attribute}" . " " . zii_t("不能小于") . " 0"%',
                    'tooBig' => '%"{attribute}" . " " . zii_t("不能大于") . ' . "\" $size\"%",
                ];
            }
        }
        // Boolean Type
        if (!empty($this->_ruleBoolean)) {
            $rules[] = [
                $this->arrayOrString(array_column($this->_ruleBoolean, 'name')),
                'boolean',
                'trueValue' => '1',
                'falseValue' => '0',
                'message' => '%"{attribute}" . " " . zii_t("不是有效的值")%',
            ];
        }
        // Range Type
        if (!empty($this->_ruleRange)) {
            foreach ($this->_ruleRange as $item) {
                $rules[] = [
                    $this->arrayOrString($item['name']),
                    'in',
                    'range' => $item['range'],
                    'strict' => true,
                    'allowArray' => $item['allowArray'] ?? false,
                    'message' => '%"{attribute}" . " " . zii_t("不是有效的值")%',
                ];
            }
        }
        // Required Type
        if (!empty($this->_ruleRequired)) {
            $rules[] = [
                $this->arrayOrString(array_column($this->_ruleRequired, 'name')),
                'required',
                'strict' => true,
                'message' => '%"{attribute}" . " " . zii_t("不能为空")%',
            ];
        }
        // Exist Type
        if (!empty($this->_ruleExist)) {
            $this->_namespace->addUse(ActiveQuery::class);
            foreach ($this->_ruleExist as $item) {
                $rules[] = [
                    $this->arrayOrString($item['name']),
                    'exist',
                    'targetClass' => '%' . $item['targetClassName'] . '::class%',
                    'targetAttribute' => 'id',
                    'message' => '%"{attribute}" . " " . zii_t("不存在")%',
                ];
                // Class Comment
                $this->_class->addComment(implode(' ', [
                    '@property',
                    $item['targetClassName'],
                    '$db' . ucfirst($item['targetClassName']),
                    // '关联' . str_replace('表', '', $item['targetClassComment']) . '[ActiveRecord].',
                ]));
                // Table Relations
                $this->_class->addMethod("getDb{$item['targetClassName']}")
                    ->setReturnType('?ActiveQuery')
                    // ->addComment("关联{$item['targetClassComment']}")
                    ->addComment("@return null|ActiveQuery|{$item['targetClassName']}")
                    ->setBody("return \$this->hasOne({$item['targetClassName']}::class, ['id' => '" . lcfirst($item['targetClassName']) . "_id']);");
            }
        }
        // Unique Index
        if (!empty($this->_indexes)) {
            $uniqueFields = [];
            foreach ($this->_indexes as $indexName => $indexType) {
                if ($indexName !== 'id' && $indexType === 'unique') {
                    $uniqueFields[] = $indexName;
                }
            }
            if ($uniqueFields !== []) {
                $rules[] = [
                    $this->arrayOrString($uniqueFields),
                    'unique',
                    'message' => '%"{attribute}" . " " . zii_t("不能重复")%',
                ];
            }
        }

        return $rules;
    }

    /**
     * 优先返回一个字符串，用于规则中的目标字段
     * 以下情况将返回字符串：
     * 1. 参数是索引数组，且数组中只有一个元素.
     *
     * @param mixed $value
     * @return mixed
     */
    private function arrayOrString($value)
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $value = array_merge($value);
            if (count($value, COUNT_RECURSIVE) === 1 && isset($value[0])) {
                return $value[0];
            }
        }

        return $value;
    }
}
