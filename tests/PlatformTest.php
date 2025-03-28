<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\IntColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
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
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);


        $platform->createTable('post', [
            new PrimaryKeyColumn('id'),
            new IntColumn('user_id'),
            new StringColumn('title'),
            new StringColumn('content'),
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
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
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
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
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
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $this->assertStrictEquals(6, count($platform->listTableColumns('user')));
        $platform->addColumn('user', new StringColumn('username'));
        $this->assertStrictEquals(7, count($platform->listTableColumns('user')));
    }

    public function testRenameColumn()
    {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $platform = $this->em->createDatabasePlatform();
        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $columnsAsArray = array_map(function (ColumnMetadata $column) {
            return $column->toArray();
        }, $platform->listTableColumns('user'));
        $columns = array_column($columnsAsArray, 'name');
        $this->assertTrue(in_array('firstname', $columns));

        $platform->renameColumn('user', 'firstname', 'prenom');

        $columnsAsArray = array_map(function (ColumnMetadata $column) {
            return $column->toArray();
        }, $platform->listTableColumns('user'));
        $columns = array_column($columnsAsArray, 'name');
        $this->assertTrue(!in_array('firstname', $columns));
        $this->assertTrue(in_array('prenom', $columns));
    }

}
