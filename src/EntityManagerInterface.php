<?php

namespace PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Manager\PaperKeyValueManager;
use PhpDevCommunity\PaperORM\Manager\PaperSequenceManager;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Repository\Repository;

interface EntityManagerInterface
{
    public function persist(object $entity): void;
    public function remove(object $entity): void;
    public function flush(object $entity = null ): void;
    public function registry(): PaperKeyValueManager;
    public function sequence(): PaperSequenceManager;
    public function getRepository(string $entity): Repository;
    public function getPlatform(): PlatformInterface;
    public function getConnection(): PaperConnection;
    public function getCache(): EntityMemcachedCache;
    public function clear(): void;
}
