<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\UniTester\TestCase;

class PlatformTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
        ]);

    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    protected function execute(): void
    {
      $this->testCreateTables();
      $this->testDropTable();
      $this->testDropColumn();
      $this->testAddColumn();
      $this->testRenameColumn();
    }

    public function testCreateTables()
    {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();
        $platform = $this->em->createDatabasePlatform();
        $platform->createTable('user', [
            'id' => 'INTEGER PRIMARY KEY',
            'firstname' => 'TEXT',
            'lastname' => 'TEXT',
            'email' => 'TEXT',
            'password' => 'TEXT',
            'is_active' => 'INTEGER',
        ]);

        $platform->createTable('post', [
            'id' => 'INTEGER PRIMARY KEY',
            'user_id' => 'INTEGER',
            'title' => 'TEXT',
            'content' => 'TEXT',
        ], [
            'FOREIGN KEY (user_id) REFERENCES user (id)'
        ]);

        $this->assertStrictEquals(2, count($platform->listTables()));
        $this->assertEquals(['user', 'post'], $platform->listTables());
    }


    public function testDropTable()
    {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $platform = $this->em->createDatabasePlatform();
        $platform->createTable('user', [
            'id' => 'INTEGER PRIMARY KEY',
            'firstname' => 'TEXT',
            'lastname' => 'TEXT',
            'email' => 'TEXT',
            'password' => 'TEXT',
            'is_active' => 'INTEGER',
        ]);

        $this->assertStrictEquals(1, count($platform->listTables()));
        $platform->dropTable('user');
        $this->assertStrictEquals(0, count($platform->listTables()));
    }

    public function testDropColumn()
    {
        if (\SQLite3::version()['versionString'] < '3.35.0') {
            return;
        }

        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $platform = $this->em->createDatabasePlatform();
        $platform->createTable('user', [
            'id' => 'INTEGER PRIMARY KEY',
            'firstname' => 'TEXT',
            'lastname' => 'TEXT',
            'email' => 'TEXT',
            'password' => 'TEXT',
            'is_active' => 'INTEGER',
        ]);

        $this->assertStrictEquals(6, count($platform->listTableColumns('user')));
        $platform->dropColumn('user', 'lastname');
        $this->assertStrictEquals(5, count($platform->listTableColumns('user')));
    }

    public function testAddColumn()
    {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $platform = $this->em->createDatabasePlatform();
        $platform->createTable('user', [
            'id' => 'INTEGER PRIMARY KEY',
            'firstname' => 'TEXT',
            'lastname' => 'TEXT',
            'email' => 'TEXT',
            'password' => 'TEXT',
            'is_active' => 'INTEGER',
        ]);

        $this->assertStrictEquals(6, count($platform->listTableColumns('user')));
        $platform->addColumn('user', 'username', 'TEXT');
        $this->assertStrictEquals(7, count($platform->listTableColumns('user')));
    }

    public function testRenameColumn()
    {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $platform = $this->em->createDatabasePlatform();
        $platform->createTable('user', [
            'id' => 'INTEGER PRIMARY KEY',
            'firstname' => 'TEXT',
            'lastname' => 'TEXT',
            'email' => 'TEXT',
            'password' => 'TEXT',
            'is_active' => 'INTEGER',
        ]);

        $columns = array_column($platform->listTableColumns('user'), 'name');
        $this->assertTrue(in_array('firstname', $columns));

        $platform->renameColumn('user', 'firstname', 'prenom');
        $columns = array_column($platform->listTableColumns('user'), 'name');
        $this->assertTrue(!in_array('firstname', $columns));
        $this->assertTrue(in_array('prenom', $columns));
    }

}
