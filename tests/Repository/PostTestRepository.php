<?php

namespace Test\PhpDevCommunity\PaperORM\Repository;

use PhpDevCommunity\PaperORM\Repository\Repository;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;

class PostTestRepository extends Repository
{
    public function getEntityName(): string
    {
        return PostTest::class;
    }
}
