<?php

namespace Test\PhpDevCommunity\PaperORM\Repository;

use PhpDevCommunity\PaperORM\Repository\Repository;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\TagTest;

class TagTestRepository extends Repository
{
    public function getEntityName(): string
    {
        return TagTest::class;
    }
}
