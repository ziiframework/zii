<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use Yii;
use Exception;
use yiiunit\TestCase;
use yii\db\Connection;
use yii\caching\DummyCache;

use function extension_loaded;

abstract class DatabaseTestCase extends TestCase
{
    protected $database;

    /**
     * @var string the driver name of this test class. Must be set by a subclass.
     */
    protected $driverName;

    /**
     * @var Connection
     */
    private $_db;

    protected function setUp(): void
    {
        if ($this->driverName === null) {
            throw new Exception('driverName is not set for a DatabaseTestCase.');
        }

        parent::setUp();
        $databases = self::getParam('databases');
        $this->database = $databases[$this->driverName];
        $pdo_database = 'pdo_' . $this->driverName;

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and ' . $pdo_database . ' extension are required.');
        }
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        if ($this->_db) {
            $this->_db->close();
        }
        $this->destroyApplication();
    }

    /**
     * @param  bool $reset whether to clean up the test database
     * @param  bool $open  whether to open and populate test database
     *
     * @return \yii\db\Connection
     */
    public function getConnection($reset = true, $open = true)
    {
        if (!$reset && $this->_db) {
            return $this->_db;
        }
        $config = $this->database;

        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }

        try {
            $this->_db = $this->prepareDatabase($config, $fixture, $open);
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->_db;
    }

    public function prepareDatabase($config, $fixture, $open = true)
    {
        if (!isset($config['class'])) {
            $config['class'] = 'yii\db\Connection';
        }
        /* @var $db \yii\db\Connection */
        $db = Yii::createObject($config);

        if (!$open) {
            return $db;
        }
        $db->open();

        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($fixture));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $db->pdo->exec($line);
                }
            }
        }

        return $db;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @return mixed
     */
    protected function replaceQuotes($sql)
    {
        switch ($this->driverName) {
            case 'mysql':
            case 'sqlite':
                return str_replace(['[[', ']]'], '`', $sql);

            case 'pgsql':
                // more complex replacement needed to not conflict with postgres array syntax
                return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))\]\])/', '"', $sql));

            case 'sqlsrv':
                return str_replace(['[[', ']]'], ['[', ']'], $sql);

            default:
                return $sql;
        }
    }

    /**
     * @return \yii\db\Connection
     */
    protected function getConnectionWithInvalidSlave()
    {
        $config = array_merge($this->database, [
            'serverStatusCache' => new DummyCache(),
            'slaves' => [
                [], // invalid config
            ],
        ]);

        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }

        return $this->prepareDatabase($config, $fixture, true);
    }
}
