<?php

namespace Test\PhpDevCommunity\PaperORM\Common;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class OrmTestMemory extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
            'debug' => false
        ]);
        $this->setUpDatabaseSchema();
    }

    protected function setUpDatabaseSchema(): void
    {
        DataBaseHelperTest::init($this->em, 10000);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }


    protected function execute(): void
    {
        $memory = memory_get_usage();
        $users = $this->em->getRepository(UserTest::class)
            ->findBy()
            ->toObject()
        ;
        $this->assertStrictEquals(10000, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertNotEmpty($user);
        }
        $memory = memory_get_usage(true) - $memory;
        $memory = ceil($memory / 1024 / 1024);
        $this->assertTrue( $memory <= 30 );
    }
}
