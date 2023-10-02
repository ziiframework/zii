<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\console\controllers;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Closure;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Type;
use Yii;
use yii\behaviors\AttributeTypecastBehavior;
use yii\console\Controller;
use yii\db\ActiveQuery;
use yii\db\ColumnSchema;
use yii\db\Exception;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\helpers\Console;
use yii\helpers\Inflector;
use yii\web\IdentityInterface;

/**
 * Generate model by analyzing db schema.
 *
 * see the description of this command:
 *
 *     yii model
 *
 * generate a model by its schema:
 *
 *     yii model/generate first second
 *
 * @author charescape <charescape@outlook.com>
 *
 * @since 2.0
 */
class ModelController extends Controller
{
    private const EDGE_MAX = 'max';
    private const EDGE_MIN = 'min';

    public string $modelNamespace = 'Zpp\\Models';
    public string $modelExtends = '\\Zpp\\Models\\BaseModel';
    public string $modelDir = '@app/src/Models';

    public array $identityTables = ['usr', 'user', 'yh'];

    public static array $specialKeys = [
        'id' => 'ID',
        'client_ip' => '%zii_t("客户端IP")%',
        'created_at_microtime' => '%zii_t("创建时间戳")%',
        'created_at_unixtime' => '%zii_t("创建时间戳")%',
        'created_at' => '%zii_t("创建时间")%',
        'updated_at' => '%zii_t("更新时间")%',
    ];

    private PhpNamespace $_targetNamespace;

    private ClassType $_targetClass;

    private array $_primaryKeys = [];

    private array $_indexes = [];

    /**
     * Required eg:
     * [
     *   ['name' => NAME],
     *   ['name' => NAME],
     *   ['name' => NAME],
     * ].
     */
    private array $_ruleRequired = [];

    /**
     * Range eg:
     * [
     *   ['name' => NAME, 'range' => RANGE],
     *   ['name' => NAME, 'range' => RANGE],
     *   ['name' => NAME, 'range' => RANGE],
     * ].
     */
    private array $_ruleRange = [];

    /**
     * Boolean eg:
     * [
     *   ['name' => NAME],
     *   ['name' => NAME],
     *   ['name' => NAME],
     * ].
     */
    private array $_ruleBoolean = [];

    /**
     * Integer eg:
     * [
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     * ].
     */
    private array $_ruleInteger = [];

    /**
     * [
     *   ['name' => NAME, 'min' => MIN, 'max' => MAX],
     *   ['name' => NAME, 'min' => MIN, 'max' => MAX],
     *   ['name' => NAME, 'min' => MIN, 'max' => MAX],
     * ].
     */
    private array $_ruleDecimal = [];

    /**
     * String eg:
     * [
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     *   ['name' => NAME, 'size' => SIZE],
     * ].
     */
    private array $_ruleString = [];

    /**
     * String eg:
     * [
     *   ['name' => NAME],
     *   ['name' => NAME],
     *   ['name' => NAME],
     * ].
     */
    private array $_ruleText = [];

    /**
     * YmdHis eg:
     * [
     *   ['name' => NAME, 'format' => 'Y-m-d'],
     *   ['name' => NAME, 'format' => 'Y-m-d H:i:s'],
     *   ['name' => NAME, 'format' => 'Y'],
     * ].
     */
    private array $_ruleYmdHis = [];

    /**
     * Exist eg:
     * [
     *   ['name' => NAME, 'targetClassName' => 'Member'],
     *   ['name' => NAME, 'targetClassName' => 'Member'],
     *   ['name' => NAME, 'targetClassName' => 'Member'],
     * ].
     */
    private array $_ruleExist = [];

    /**
     * typeCast eg:
     * [
     *   'is_success' => 'TYPE_BOOLEAN',
     *   'is_success' => 'TYPE_BOOLEAN',
     *   'is_success' => 'TYPE_BOOLEAN',
     * ].
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
     * special chars replacement.
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
        $this->_primaryKeys = [];
        $this->_indexes = [];
        $this->_ruleRequired = [];
        $this->_ruleRange = [];
        $this->_ruleBoolean = [];
        $this->_ruleInteger = [];
        $this->_ruleDecimal = [];
        $this->_ruleString = [];
        $this->_ruleText = [];
        $this->_ruleYmdHis = [];
        $this->_ruleExist = [];
        $this->_typeCastAttributes = [];
    }

    public bool $override = false;

    public array $exclude = [];

    public string $modelNamePrefix = 'Gii';

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'override',
            'exclude',
            'modelNamePrefix',
        ]);
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'p' => 'modelNamePrefix',
        ]);
    }

    public function actionIndex(): void
    {
        $this->stdout("Use php yii model/gii to generate a model.\n", Console::FG_RED);
    }

    public function actionGiiAll(): void
    {
        $tableNames = Yii::$app->db->getSchema()->getTableNames('', true);

        $exclude = array_merge([
            'dbcache',
            'dbsession',
            'dbqueue',
            'dbauth_item',
            'dbauth_item_child',
            'dbauth_assignment',
            'dbauth_rule',
            'dbmigration',
        ], $this->exclude);

        foreach ($tableNames as $tableName) {
            if (!in_array($tableName, $exclude, true)) {
                $this->actionGii($tableName);
            }
        }
    }

    public function actionGii(string $tableName): void
    {
        $this->resetAttributes();

        $this->_targetNamespace = new PhpNamespace($this->modelNamespace . '\\Gii');
        $this->_targetNamespace->addUse($this->modelExtends);

        $this->_targetClass = $this->_targetNamespace->addClass($this->modelNamePrefix . Inflector::camelize($tableName));
        $this->_targetClass->setExtends($this->modelExtends);
        $this->_targetClass->setAbstract();

        if (in_array($tableName, $this->identityTables, true)) {
            $this->_targetNamespace->addUse(IdentityInterface::class);
            $this->_targetClass->addImplement(IdentityInterface::class);
        }

        // Table Struct
        $_schema = Yii::$app->db->getTableSchema($tableName, true);

        if (!($_schema instanceof TableSchema)) {
            echo "Table $tableName does not exist.\n";

            exit;
        }

        // Table Comment
        $this->_targetClass->addComment($this->getTableComment($tableName) . ".\n");

        // Table Indexes
        foreach ($this->getTableIndexes($tableName) as $index) {
            $this->_indexes[$index['Column_name']] = $index['Non_unique'] * 1 ? 'indexed' : 'unique';
        }

        foreach ($_schema->columns as $column) {
            if ($column->isPrimaryKey) {
                $this->_primaryKeys[] = $column->name;
            }

            // Field Comment
            $varType = $column->phpType;

            if ($varType === 'integer') {
                $varType = 'int';
            }

            if (str_contains($column->dbType, 'decimal')) {
                $varType = 'float';
            }

            if (str_contains($column->dbType, 'tinyint') && preg_match('/^(is|has|can|enable|use|require)_/', $column->name)) {
                $varType = 'bool';
            }

            if ($column->isPrimaryKey) {
                $this->_targetClass->addComment(implode(' ', [
                    '@property',
                    $varType,
                    '$' . $column->name,
                    $column->comment . "[$column->dbType]",
                    "This property is PrimaryKey.",
                ]));
            } else {
                $this->_targetClass->addComment(implode(' ', [
                    '@property',
                    $varType . ($column->allowNull ? '|null' : ''),
                    '$' . $column->name,
                    $column->comment . "[$column->dbType]" . ($column->allowNull ? '.' : '[NOT NULL].'),
                    isset($this->_indexes[$column->name]) && $this->_indexes[$column->name] ? "This property is {$this->_indexes[$column->name]}." : '',
                ]));
            }

            if (in_array($column->name, array_keys(self::$specialKeys), true)) {
                continue;
            }

            // Column Cast
            $this->castColumn($column);
        }

        foreach ($this->getTableForeignKeys($tableName) as $tableForeignKey) {
            if ($tableForeignKey['REFERENCED_TABLE_NAME'] === null) {
                echo "Warning: REFERENCED_TABLE_NAME is NULL\n";
                continue;
            }
            $this->_ruleExist[] = [
                'name' => $tableForeignKey['COLUMN_NAME'],
                'targetClassName' => Inflector::camelize($tableForeignKey['REFERENCED_TABLE_NAME']),
                'targetAttribute' => $tableForeignKey['REFERENCED_COLUMN_NAME'],
                'targetClassComment' => $this->getTableComment($tableForeignKey['REFERENCED_TABLE_NAME']),
            ];
        }

        // public function behaviors(): array
        if (!empty($this->_typeCastAttributes)) {
            $this->_targetNamespace->addUse(AttributeTypecastBehavior::class);
            $this->_targetClass->addMethod('behaviors')
                ->setReturnType('array')
                // ->addComment('@inheritdoc')
                ->setBody(implode("\n", array_merge(
                    [
                        '$behaviors = parent::behaviors();' . "\n",
                    ],
                    array_map(function (string $k, string $v): string {
                        return "\$behaviors['typecast']['attributeTypes']['$k'] = AttributeTypecastBehavior::$v;";
                    }, array_keys($this->_typeCastAttributes), array_values($this->_typeCastAttributes)),
                    [
                        "\n" . 'return $behaviors;',
                    ]
                )));
        }

        // public static function primaryKey()
        $this->_targetClass->addMethod('primaryKey')
            ->setStatic()
            ->setReturnType('array')
            ->setBody('return ?;', [$this->_primaryKeys]);

        // public function attributeLabels()
        $_fields = array_column($_schema->columns, 'name');
        $_comments = array_column($_schema->columns, 'comment');

        // for field without comment
        foreach ($_comments as $_ci => $_c) {
            if (!is_string($_c) || trim($_c) === '') {
                $_comments[$_ci] = strtoupper($_fields[$_ci]);
            }
        }

        $this->_targetClass->addMethod('attributeLabels')
            ->setReturnType('array')
            ->setBody('return array_merge(parent::attributeLabels(), ?);', [
                    array_combine(
                        $_fields,
                        array_map(static fn(string $value): string => "%zii_t(\"$value\")%", $_comments)
                    ),
                ]
            );
//            ->setBody(
//                "\$attributeLabels = parent::attributeLabels();\n\n"
//                . (!in_array('id', $_allColumnNames, true) ? "unset(\$attributeLabels['id']);\n" : "")
//                . (!in_array('created_at', $_allColumnNames, true) ? "unset(\$attributeLabels['created_at']);\n" : "")
//                . "\n return array_merge(\$attributeLabels, ?);", [
//                    array_diff_key(array_combine($_allColumnNames, array_map(static fn(string $value): string => "%zii_t(\"$value\")%", array_column($_schema->columns, 'comment'))), [
//                        'id' => 'ID',
//                        'created_at' => '%zii_t("创建时间")%',
//                    ]),
//                ]
//            );

        // public function extraFields
        $this->_targetClass->addMethod('extraFields')
            ->setReturnType('array')
            // ->addComment('@inheritdoc')
            ->setBody('return array_merge(parent::extraFields(), ?);', [
                array_map(fn (string $field): string => 'db' . $this->getRelationMethodName($field), array_column($this->_ruleExist, 'name')),
            ]);

        // rules
        $this->_targetClass->addMethod('rules')
            ->setReturnType('array')
            // ->addComment('@inheritdoc')
            ->setBody('return array_merge(parent::rules(), ?);', [$this->generateRules()]);

        // identity interface implement
        if (in_array($tableName, $this->identityTables, true)) {
            $this->_targetClass->addMethod('findIdentity')
                ->setReturnType(IdentityInterface::class)
                ->setReturnNullable()
                ->setStatic()
                // ->addComment('@inheritdoc')
                ->setBody("return static::findOne(['id' => \$id]);")
                ->setParameters([
                    (new Parameter('id'))->setType('int'),
                ]);
            $this->_targetClass->addMethod('findIdentityByAccessToken')
                ->setReturnType(IdentityInterface::class)
                ->setReturnNullable()
                ->setStatic()
                // ->addComment('@inheritdoc')
                ->setBody("return static::findOne(['access_token' => \$token]);")
                ->setParameters([
                    (new Parameter('token'))->setType('string'),
                    (new Parameter('type'))->setType('string')->setDefaultValue(null),
                ]);
            $this->_targetClass->addMethod('getId')
                ->setReturnType('int')
                // ->addComment('@inheritdoc')
                ->setBody('return $this->getPrimaryKey();');
            $this->_targetClass->addMethod('getAuthKey')
                ->setReturnType('string')
                // ->addComment('@inheritdoc')
                ->setBody('return $this->session_secret;');
            $this->_targetClass->addMethod('validateAuthKey')
                ->setReturnType('bool')
                // ->addComment('@inheritdoc')
                ->setBody('return $this->getAuthKey() === $session_secret;')
                ->addParameter('session_secret');
        }

        $file = Yii::getAlias($this->modelDir) . '/Gii/' . $this->modelNamePrefix . Inflector::camelize($tableName) . '.php';

        if ($this->override === true || !file_exists($file)) {
            $object_contents = str_replace(array_keys(self::$_codeReplacements), array_values(self::$_codeReplacements), (string) $this->_targetNamespace);
            $object_contents = preg_replace('/["]([^$"]+)["]/u', "'$1'", $object_contents);
            $object_contents = preg_replace('/["](\s+)(\d+)(\s+)["]/u', "'$1$2$3'", $object_contents);
            $object_contents = preg_replace('/["](\s+)(\d+)["]/u', "'$1$2'", $object_contents);

            if (file_put_contents($file, "<?php\n\ndeclare(strict_types=1);\n\n" . $object_contents) !== false) {
                $fileContent = file_get_contents($file);
                $fileContent = str_replace(': \\?', ': ?', $fileContent);
                file_put_contents($file, $fileContent);
                echo '✔ Successfully created abstract model ' . $this->modelNamePrefix . Inflector::camelize($tableName) . "\n";
                $this->createFinalModel($tableName);
                $this->fixCodeStyle($file);
            } else {
                echo '✘ Failed to create abstract model ' . $this->modelNamePrefix . Inflector::camelize($tableName);
            }
            echo "\n";
        } else {
            echo '✘ Create model ' . $this->modelNamePrefix . Inflector::camelize($tableName) . " aborted, file $file already exists\n";
        }
    }

    private function createFinalModel(string $tableName): void
    {
        $file = Yii::getAlias($this->modelDir) . "/" . Inflector::camelize($tableName) . '.php';

        if (file_exists($file)) {
            echo "Skipped createFinalModel()\n";
            return;
        }

        $extendsClassName = '\\' . $this->modelNamespace . '\\Gii\\' . $this->modelNamePrefix . Inflector::camelize($tableName);

        $namespace = new PhpNamespace($this->modelNamespace);
        $namespace->addUse($extendsClassName);

        $class = $namespace->addClass(Inflector::camelize($tableName));
        $class->setExtends($extendsClassName);
        $class->setFinal();

        if (file_put_contents($file, "<?php\n\ndeclare(strict_types=1);\n\n" . $namespace->__toString()) !== false) {
            echo '✔ Successfully created model ' . Inflector::camelize($tableName);
        } else {
            echo '✘ Failed to create model ' . Inflector::camelize($tableName);
        }
    }

    private function fixCodeStyle(string $file): void
    {
        $rootDir = dirname(Yii::getAlias('@vendor'));

        $cmd = "php D:/phpcsfixer.phar --version  2>&1";
        exec($cmd, $output, $outCode);
        echo "exec: $cmd" . "\n",
            implode("\n", $output)
            . "\nresult code: $outCode\n";
        unset($cmd, $output, $outCode);

        $cmd = "php D:/phpcsfixer.phar --config=$rootDir/.php-cs-fixer.dist.php fix $file  2>&1";
        exec($cmd, $output, $outCode);
        echo implode("\n", $output) . "\nresult code: $outCode\n";
        echo "exec: $cmd" . "\n",
            implode("\n", $output)
            . "\nresult code: $outCode\n";
        unset($cmd, $output, $outCode);
    }

    private function castColumn(ColumnSchema $column): void
    {
        // required
        if (!$column->allowNull && $column->defaultValue === null) {
            $this->_ruleRequired[] = ['name' => $column->name];
        }

        // tinyint
        if ($this->fieldTypeCast($column->dbType) === 'tinyint') {
            // $column->size === 1
            // Warning: #1681 Integer display width is deprecated and will be removed in a future release.
            if (preg_match('/^(is|has|can|enable|use|require)_/', $column->name)) {
                $this->_typeCastAttributes[$column->name] = 'TYPE_BOOLEAN';
                $this->_ruleBoolean[] = ['name' => $column->name];
            } else {
                $this->_typeCastAttributes[$column->name] = 'TYPE_INTEGER';
                $this->_ruleInteger[] = [
                    'name' => $column->name,
                    'max' => $this->getColumnEdge($column),
                ];
            }
        }

        // int
        if (in_array($this->fieldTypeCast($column->dbType), ['smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
            $this->_ruleInteger[] = [
                'name' => $column->name,
                'max' => $this->getColumnEdge($column),
            ];
        }

        if (in_array($this->fieldTypeCast($column->dbType), ['double', 'float', 'decimal'])) {
            $this->_ruleDecimal[] = [
                'name' => $column->name,
                'max' => $this->getColumnEdge($column),
                'min' => $this->getColumnEdge($column, self::EDGE_MIN),
            ];
        }

        // varchar
        if ($this->fieldTypeCast($column->dbType) === 'varchar') {
            $this->_ruleString[] = [
                'name' => $column->name,
                'size' => $column->size,
            ];
        }

        // text
        if ($this->fieldTypeCast($column->dbType) === 'text') {
            $this->_ruleText[] = ['name' => $column->name];

            $this->_ruleString[] = [
                'name' => $column->name,
                'size' => 65535,
            ];
        }

        // enum
        if ($this->fieldTypeCast($column->dbType) === 'enum') {
            $this->_ruleRange[] = [
                'name' => $column->name,
                'range' => $column->enumValues,
            ];
        }

        // set
        if ($this->fieldTypeCast($column->dbType) === 'set') {
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
        if (isset(self::$_dateFormat[$this->fieldTypeCast($column->dbType)])) {
            $this->_ruleYmdHis[] = [
                'name' => $column->name,
                'format' => self::$_dateFormat[$this->fieldTypeCast($column->dbType)],
            ];
        }
    }

    private function generateRules(): array
    {
        $rules = [];

        // Rule String
        if (!empty($this->_ruleString) || !empty($this->_ruleRange)) {
            $closure = new Closure();
            $closure->setBody('return pf_str_or_null($value);')
                ->setReturnType('string')
                ->setReturnNullable()
                ->addParameter('value');

            $rules[] = [
                $this->arrayOrString(array_unique(array_merge(array_column($this->_ruleString, 'name'), array_column($this->_ruleRange, 'name')))),
                'filter',
                'filter' => "%$closure%",
            ];
        }
        // Rule Text
        if (!empty($this->_ruleText)) {
            $closure = new Closure();
            $closure->setBody('return pf_str_or_null($value) ?? \'\';')
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
                $closure->setBody("return zff_date_or_null(\$value, '$format');")
                    ->setReturnType('string')
                    ->setReturnNullable()
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
                $max_size = (int) $size;
                $min_size = $max_size >= 60000 ? 0 : 1;

                $rules[] = [
                    $this->arrayOrString($names),
                    'string',
                    'min' => $min_size,
                    'max' => $max_size,
                    'message' => '%"{attribute}" . " " . zii_t("不是有效的字符")%',
                    'tooShort' => '%"{attribute}" . " " . zii_t("不能少于") . ' . "\" $min_size \"" . '. zii_t("个字符")%',
                    'tooLong' => '%"{attribute}" . " " . zii_t("不能超过") . ' . "\" $max_size \"" . '. zii_t("个字符")%',
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
                    'max' => (int) $size,
                    'message' => '%"{attribute}" . " " . zii_t("必须是整数")%',
                    'tooSmall' => '%"{attribute}" . " " . zii_t("不能小于") . " 0"%',
                    'tooBig' => '%"{attribute}" . " " . zii_t("不能大于") . ' . "\" $size\"%",
                ];
            }
        }
        // Decimal Type & Length
        if (!empty($this->_ruleDecimal)) {
            $groupBySize = [];

            foreach ($this->_ruleDecimal as $column) {
                $groupBySize["{$column['min']}--{$column['max']}"][] = $column['name'];
            }

            foreach ($groupBySize as $scale => $names) {
                $scale0 = explode('--', $scale)[0];
                $scale1 = explode('--', $scale)[1];
                $scaleLength = mb_strlen(explode('.', $scale1)[1]);
                $scaleLengthSC = ['一', '两', '三', '四', '五', '六', '七'][$scaleLength - 1];

                $rules[] = [
                    $this->arrayOrString($names),
                    'double',
                    'min' => '%' . $scale0 . '%',
                    'max' => '%' . $scale1 . '%',
                    'numberPattern' => '#^\d+\.\d{' . $scaleLength . '}$#',
                    'message' =>  '%"{attribute}" . " " . zii_t("必须是小数点后保留' . $scaleLengthSC . '位的数字")%',
                    'tooSmall' => '%"{attribute}" . " " . zii_t("不能小于") . ' . "\" $scale0\"%",
                    'tooBig' =>   '%"{attribute}" . " " . zii_t("不能大于") . ' . "\" $scale1\"%",
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
            $this->_targetNamespace->addUse(ActiveQuery::class);

            foreach ($this->_ruleExist as $item) {
                $this->_targetNamespace->addUse("\\{$this->modelNamespace}\\{$item['targetClassName']}");

                $rules[] = [
                    $this->arrayOrString($item['name']),
                    'exist',
                    'targetClass' => '%' . $item['targetClassName'] . '::class%',
                    'targetAttribute' => $item['targetAttribute'],
                    'message' => '%"{attribute}" . " " . zii_t("不存在")%',
                ];

                // Table Relations
                $refMethodName = 'getDb' . $this->getRelationMethodName($item['name']);
                if (!$this->_targetClass->hasMethod($refMethodName)) {
                    $this->_targetClass->addMethod($refMethodName)
                        ->setReturnType(Type::union($this->modelNamespace . '\\' . $item['targetClassName'], ActiveQuery::class))
                        ->setReturnNullable()
                        ->setBody("return \$this->hasOne({$item['targetClassName']}::class, ['{$item['targetAttribute']}' => '" . $item['name'] . "']);");
                }

                // Class Comment
                $this->_targetClass->addComment(implode(' ', [
                    '@property',
                    $item['targetClassName'],
                    '$db' . $this->getRelationMethodName($item['name']),
                ]));
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

    private function getRelationMethodName(string $field): string
    {
        return Inflector::camelize(str_replace(['_id', '_hashtag', 'ref_'], '', $field));
    }

    private function getTableComment(string $tableName): ?string
    {
        $sql = "SHOW TABLE STATUS WHERE Name = '$tableName'";
        $command = Yii::$app->db->createCommand($sql);

        try {
            $query = $command->queryOne();
        } catch (Exception $e) {
        }

        return $query['Comment'] ?? null;
    }

    private function getTableIndexes(string $tableName): array
    {
        $sql = 'SHOW INDEX FROM ' . Yii::$app->db->schema->getRawTableName($tableName);
        $command = Yii::$app->db->createCommand($sql);

        try {
            return $command->queryAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private function getTableForeignKeys(string $tableName): array
    {
        preg_match('/dbname=([^;]+)/', Yii::$app->db->dsn, $matches);

        $databaseName = $matches[1];
        $tableName = Yii::$app->db->schema->getRawTableName($tableName);

        $sql = <<<EOT
SELECT
    ii.TABLE_SCHEMA,
    ii.TABLE_NAME,
    ii.CONSTRAINT_TYPE,
    ii.CONSTRAINT_NAME,
    kk.COLUMN_NAME,
    kk.REFERENCED_TABLE_NAME,
    kk.REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.TABLE_CONSTRAINTS ii
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kk
ON
    ii.CONSTRAINT_NAME = kk.CONSTRAINT_NAME
WHERE
    ii.TABLE_SCHEMA = '$databaseName'
    AND ii.TABLE_NAME = '$tableName'
    AND ii.CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND kk.TABLE_SCHEMA = '$databaseName'
ORDER BY
    ii.TABLE_NAME;
EOT;

        $command = Yii::$app->db->createCommand($sql);

        try {
            $all = $command->queryAll();
        } catch (Exception) {
            $all = [];
        }

        $fks = [];

        // 去重
        foreach ($all as $v) {
            $fks[$v['CONSTRAINT_NAME']] = $v;
        }

        return $fks;
    }

    private function fieldTypeCast(string $dbType): string
    {
        if (!str_contains($dbType, '(')) {
            return $dbType;
        }

        return explode('(', $dbType)[0];
    }

    /**
     * @param ColumnSchema $column
     * @param string $edge
     * @return int|string|null
     */
    private function getColumnEdge(ColumnSchema $column, string $edge = self::EDGE_MAX)
    {
        $dbType = $this->fieldTypeCast($column->dbType);

        switch ($dbType) {
            case Schema::TYPE_TINYINT:
                $max = $column->size === null || $column->size >= 3 ? 127 : str_repeat('9', $column->size);

                break;
            case Schema::TYPE_SMALLINT:
                $max = $column->size === null || $column->size >= 5 ? 32767 : str_repeat('9', $column->size);

                break;
            case 'mediumint':
                $max = $column->size === null || $column->size >= 7 ? 8388607 : str_repeat('9', $column->size);

                break;
            case 'int':
            case Schema::TYPE_INTEGER:
                $max = $column->size === null || $column->size >= 10 ? 2147483647 : str_repeat('9', $column->size);

                break;
            case Schema::TYPE_BIGINT:
                $max = $column->size === null || $column->size >= 19 ? 9223372036854775807 : str_repeat('9', $column->size);

                break;
            case Schema::TYPE_DOUBLE:
            case Schema::TYPE_FLOAT:
            case Schema::TYPE_DECIMAL:
                $precision = $column->precision === null || $column->precision >= 9 ? 9 : $column->precision;
                $scale = $column->scale === null ? 0 : $column->scale;

                if ($edge === self::EDGE_MAX) {
                    return str_repeat('9', $precision - $scale) . '.' . str_repeat('9', $scale);
                }
                if ($edge === self::EDGE_MIN) {
                    return '0.' . str_repeat('0', $scale);
                }

                return null;
            default:
                $max = null;

                break;
        }

        return (int) $max;
    }
}
