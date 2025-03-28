<?php

namespace PhpDevCommunity\PaperORM\Driver;

use PhpDevCommunity\PaperORM\PaperConnection;
use PhpDevCommunity\PaperORM\Pdo\PaperPDO;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Schema\SchemaInterface;

interface DriverInterface
{
    public function connect(array $params): PaperPDO;
    public function createDatabasePlatform(PaperConnection $connection): PlatformInterface;
    public function createDatabaseSchema(): SchemaInterface;
}
