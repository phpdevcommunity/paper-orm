<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\UniTester\TestCase;

class PlatformDiffTest extends TestCase
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
        $platform = $this->em->createDatabasePlatform();
        $columns = [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            (new BoolColumn('is_active'))->bindProperty('active'),
        ];
        $platform->createTable('user', $columns);

        $diff = $platform->diff('user', $columns, [] );

        $this->assertEmpty($diff->getColumnsToAdd());
        $this->assertEmpty($diff->getColumnsToUpdate());
        $this->assertEmpty($diff->getColumnsToDelete());

        $columns[3] = new StringColumn('username');

        $diff = $platform->diff('user', $columns, [] );

        $this->assertTrue(count($diff->getColumnsToAdd()) == 1);
        $this->assertTrue(count($diff->getColumnsToDelete()) == 1);
        $this->assertEmpty($diff->getColumnsToUpdate());

        $platform->dropTable('user');
        $platform->createTable('user', $columns, [] );

        $columns[3] = new StringColumn( 'username', 100);
        $diff = $platform->diff('user', $columns, [] );

        $this->assertTrue(count($diff->getColumnsToUpdate()) == 1);
        $this->assertEmpty($diff->getColumnsToAdd());
        $this->assertEmpty($diff->getColumnsToDelete());


        $diff = $platform->diff('user2', $columns, [] );
        $this->assertTrue(count($diff->getColumnsToAdd()) == 6);
    }
}
