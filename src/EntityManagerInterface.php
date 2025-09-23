<?php

namespace PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Repository\Repository;

interface EntityManagerInterface
{
    public function persist(object $entity): void;

    public function remove(object $entity): void;

    public function flush(): void;

    public function getRepository(string $entity): Repository;

    public function createDatabasePlatform(): PlatformInterface;

    public function getConnection(): PaperConnection;
    public function getCache(): EntityMemcachedCache;

    public function clear(): void;
}
