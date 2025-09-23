<?php

namespace Test\PhpDevCommunity\PaperORM\Common;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class OrmTestMemory extends TestCase
{

    protected function setUp(): void
    {
    }


    protected function tearDown(): void
    {
    }


    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as  $params) {
            $em = new EntityManager($params);
            DataBaseHelperTest::init($em, 1000, false);
            $memory = memory_get_usage();
            $users = $em->getRepository(UserTest::class)
                ->findBy()
                ->toObject();
            $this->assertStrictEquals(1000, count($users));
            foreach ($users as $user) {
                $this->assertInstanceOf(UserTest::class, $user);
                $this->assertNotEmpty($user);
            }
            $memory = memory_get_usage(true) - $memory;
            $memory = ceil($memory / 1024 / 1024);
            $this->assertTrue( $memory <= 30 );
            $em->getConnection()->close();
        }
    }
}
