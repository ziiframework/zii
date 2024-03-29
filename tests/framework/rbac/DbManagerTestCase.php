<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\rbac;

use Yii;
use yii\rbac\Role;
use yii\log\Logger;
use app\models\User;
use yii\db\Connection;
use yii\rbac\DbManager;
use yii\rbac\Assignment;
use yii\rbac\Permission;
use yii\console\ExitCode;
use yii\caching\ArrayCache;
use yii\console\Application;
use yiiunit\data\rbac\UserID;
use yiiunit\framework\log\ArrayTarget;
use yiiunit\framework\console\controllers\EchoMigrateController;

/**
 * DbManagerTestCase.
 *
 * @group db
 * @group rbac
 */
abstract class DbManagerTestCase extends ManagerTestCase
{
    protected static $database;
    protected static $driverName;

    /**
     * @var Connection
     */
    protected $db;

    protected static function runConsoleAction($route, $params = []): void
    {
        if (Yii::$app === null) {
            new Application([
                'id' => 'Migrator',
                'basePath' => '@yiiunit',
                'controllerMap' => [
                    'migrate' => EchoMigrateController::className(),
                ],
                'components' => [
                    'db' => static::createConnection(),
                    'authManager' => '\yii\rbac\DbManager',
                ],
            ]);
        }

        Yii::$app->setComponents([
            'db' => static::createConnection(),
            'authManager' => '\yii\rbac\DbManager',
        ]);
        self::assertSame(static::$driverName, Yii::$app->db->getDriverName(), 'Connection represents the same DB driver, as is tested');
        ob_start();
        $result = Yii::$app->runAction($route, $params);
        echo 'Result is ' . $result;

        if ($result !== ExitCode::OK) {
            ob_end_flush();
        } else {
            ob_end_clean();
        }
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $databases = static::getParam('databases');
        static::$database = $databases[static::$driverName];
        $pdo_database = 'pdo_' . static::$driverName;

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            static::markTestSkipped('pdo and ' . $pdo_database . ' extension are required.');
        }

        static::runConsoleAction('migrate/up', ['migrationPath' => '@yii/rbac/migrations/', 'interactive' => false]);
    }

    public static function tearDownAfterClass(): void
    {
        static::runConsoleAction('migrate/down', ['all', 'migrationPath' => '@yii/rbac/migrations/', 'interactive' => false]);
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        if (defined('HHVM_VERSION') && static::$driverName === 'pgsql') {
            $this->markTestSkipped('HHVM PDO for pgsql does not work with binary columns, which are essential for rbac schema. See https://github.com/yiisoft/yii2/issues/14244');
        }
        parent::setUp();
        $this->auth = $this->createManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->auth->removeAll();

        if ($this->db && static::$driverName !== 'sqlite') {
            $this->db->close();
        }
        $this->db = null;
    }

    /**
     * @return \yii\db\Connection
     *
     * @throws \yii\base\InvalidParamException
     * @throws \yii\db\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getConnection()
    {
        if ($this->db === null) {
            $this->db = static::createConnection();
        }

        return $this->db;
    }

    public static function createConnection()
    {
        $db = new Connection();
        $db->dsn = static::$database['dsn'];

        if (isset(static::$database['username'])) {
            $db->username = static::$database['username'];
            $db->password = static::$database['password'];
        }

        if (isset(static::$database['attributes'])) {
            $db->attributes = static::$database['attributes'];
        }

        if (!$db->isActive) {
            $db->open();
        }

        return $db;
    }

    /**
     * @return \yii\rbac\ManagerInterface
     */
    protected function createManager()
    {
        return new DbManager(['db' => $this->getConnection(), 'defaultRoles' => ['myDefaultRole']]);
    }

    private function prepareRoles($userId): void
    {
        $this->auth->removeAll();

        $author = $this->auth->createRole('Author');
        $this->auth->add($author);
        $this->auth->assign($author, $userId);

        $createPost = $this->auth->createPermission('createPost');
        $this->auth->add($createPost);
        $this->auth->assign($createPost, $userId);

        $updatePost = $this->auth->createPermission('updatePost');
        $this->auth->add($updatePost);
        $this->auth->assign($updatePost, $userId);
    }

    public function emptyValuesProvider()
    {
        return [
            [0, 0, true],
            [0, new UserID(0), true],
            ['', '', false],
        ];
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $userId
     * @param mixed $searchUserId
     * @param mixed $isValid
     */
    public function testGetPermissionsByUserWithEmptyValue($userId, $searchUserId, $isValid): void
    {
        $this->prepareRoles($userId);

        $permissions = $this->auth->getPermissionsByUser($searchUserId);

        if ($isValid) {
            $this->assertTrue(isset($permissions['createPost']));
            $this->assertInstanceOf(Permission::className(), $permissions['createPost']);
        } else {
            $this->assertEmpty($permissions);
        }
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $userId
     * @param mixed $searchUserId
     * @param mixed $isValid
     */
    public function testGetRolesByUserWithEmptyValue($userId, $searchUserId, $isValid): void
    {
        $this->prepareRoles($userId);

        $roles = $this->auth->getRolesByUser($searchUserId);

        if ($isValid) {
            $this->assertTrue(isset($roles['Author']));
            $this->assertInstanceOf(Role::className(), $roles['Author']);
        } else {
            $this->assertEmpty($roles);
        }
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $userId
     * @param mixed $searchUserId
     * @param mixed $isValid
     */
    public function testGetAssignmentWithEmptyValue($userId, $searchUserId, $isValid): void
    {
        $this->prepareRoles($userId);

        $assignment = $this->auth->getAssignment('createPost', $searchUserId);

        if ($isValid) {
            $this->assertInstanceOf(Assignment::className(), $assignment);
            $this->assertEquals($userId, $assignment->userId);
        } else {
            $this->assertEmpty($assignment);
        }
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $userId
     * @param mixed $searchUserId
     * @param mixed $isValid
     */
    public function testGetAssignmentsWithEmptyValue($userId, $searchUserId, $isValid): void
    {
        $this->prepareRoles($userId);

        $assignments = $this->auth->getAssignments($searchUserId);

        if ($isValid) {
            $this->assertNotEmpty($assignments);
            $this->assertInstanceOf(Assignment::className(), $assignments['createPost']);
            $this->assertInstanceOf(Assignment::className(), $assignments['updatePost']);
        } else {
            $this->assertEmpty($assignments);
        }
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $userId
     * @param mixed $searchUserId
     * @param mixed $isValid
     */
    public function testRevokeWithEmptyValue($userId, $searchUserId, $isValid): void
    {
        $this->prepareRoles($userId);
        $role = $this->auth->getRole('Author');

        $result = $this->auth->revoke($role, $searchUserId);

        if ($isValid) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $userId
     * @param mixed $searchUserId
     * @param mixed $isValid
     */
    public function testRevokeAllWithEmptyValue($userId, $searchUserId, $isValid): void
    {
        $this->prepareRoles($userId);

        $result = $this->auth->revokeAll($searchUserId);

        if ($isValid) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /**
     * Ensure assignments are read from DB only once on subsequent tests.
     */
    public function testCheckAccessCache(): void
    {
        $this->mockApplication();
        $this->prepareData();

        // warm up item cache, so only assignment queries are sent to DB
        $this->auth->cache = new ArrayCache();
        $this->auth->checkAccess('author B', 'readPost');
        $this->auth->checkAccess(new UserID('author B'), 'createPost');

        // track db queries
        Yii::$app->log->flushInterval = 1;
        Yii::$app->log->getLogger()->messages = [];
        Yii::$app->log->targets['rbacqueries'] = $logTarget = new ArrayTarget([
            'categories' => ['yii\\db\\Command::query'],
            'levels' => Logger::LEVEL_INFO,
        ]);
        $this->assertCount(0, $logTarget->messages);

        // testing access on two different permissons for the same user should only result in one DB query for user assignments
        foreach (['readPost' => true, 'createPost' => false] as $permission => $result) {
            $this->assertEquals($result, $this->auth->checkAccess('reader A', $permission), "Checking $permission");
        }
        $this->assertSingleQueryToAssignmentsTable($logTarget);

        // verify cache is flushed on assign (createPost is now true)
        $this->auth->assign($this->auth->getRole('admin'), 'reader A');

        foreach (['readPost' => true, 'createPost' => true] as $permission => $result) {
            $this->assertEquals($result, $this->auth->checkAccess('reader A', $permission), "Checking $permission");
        }
        $this->assertSingleQueryToAssignmentsTable($logTarget);

        // verify cache is flushed on unassign (createPost is now false again)
        $this->auth->revoke($this->auth->getRole('admin'), 'reader A');

        foreach (['readPost' => true, 'createPost' => false] as $permission => $result) {
            $this->assertEquals($result, $this->auth->checkAccess('reader A', $permission), "Checking $permission");
        }
        $this->assertSingleQueryToAssignmentsTable($logTarget);

        // verify cache is flushed on revokeall
        $this->auth->revokeAll('reader A');

        foreach (['readPost' => false, 'createPost' => false] as $permission => $result) {
            $this->assertEquals($result, $this->auth->checkAccess('reader A', $permission), "Checking $permission");
        }
        $this->assertSingleQueryToAssignmentsTable($logTarget);

        // verify cache is flushed on removeAllAssignments
        $this->auth->assign($this->auth->getRole('admin'), 'reader A');

        foreach (['readPost' => true, 'createPost' => true] as $permission => $result) {
            $this->assertEquals($result, $this->auth->checkAccess('reader A', $permission), "Checking $permission");
        }
        $this->assertSingleQueryToAssignmentsTable($logTarget);
        $this->auth->removeAllAssignments();

        foreach (['readPost' => false, 'createPost' => false] as $permission => $result) {
            $this->assertEquals($result, $this->auth->checkAccess('reader A', $permission), "Checking $permission");
        }
        $this->assertSingleQueryToAssignmentsTable($logTarget);
    }

    private function assertSingleQueryToAssignmentsTable($logTarget): void
    {
        $this->assertCount(1, $logTarget->messages, 'Only one query should have been performed, but there are the following logs: ' . print_r($logTarget->messages, true));
        $this->assertStringContainsString('auth_assignment', $logTarget->messages[0][0], 'Log message should be a query to auth_assignment table');
        $logTarget->messages = [];
    }
}
