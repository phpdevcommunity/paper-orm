<?php

namespace PhpDevCommunity\PaperORM\Driver;

use PDO;
use PhpDevCommunity\PaperORM\PaperConnection;
use PhpDevCommunity\PaperORM\Pdo\PaperPDO;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Platform\SqlitePlatform;
use PhpDevCommunity\PaperORM\Schema\SqliteSchema;

final class SqliteDriver implements DriverInterface
{
    public function connect(
        #[SensitiveParameter]
        array $params
    ): PaperPDO
    {
        $driverOptions = $params['driverOptions'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        return new PaperPDO(
            $this->constructPdoDsn(array_intersect_key($params, ['path' => true, 'memory' => true])),
            $params['user'] ?? '',
            $params['password'] ?? '',
            $driverOptions,
        );
    }

    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @param array<string, mixed> $params
     */
    private function constructPdoDsn(array $params): string
    {
        $dsn = 'sqlite:';
        if (isset($params['path'])) {
            $dsn .= $params['path'];
        } elseif (isset($params['memory'])) {
            $dsn .= ':memory:';
        }

        return $dsn;
    }

    public function createDatabasePlatform(PaperConnection $connection): PlatformInterface
    {
        return new SqlitePlatform($connection, $this->createDatabaseSchema());
    }

    public function createDatabaseSchema(): SqliteSchema
    {
        return new SqliteSchema();
    }
}
