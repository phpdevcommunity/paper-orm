<?php

namespace PhpDevCommunity\PaperORM\Platform;

use LogicException;
use PhpDevCommunity\PaperORM\PaperConnection;
use PhpDevCommunity\PaperORM\Schema\SqliteSchema;

class SqlitePlatform implements PlatformInterface
{
    private PaperConnection $connection;
    private SqliteSchema $schema;

    public function __construct(PaperConnection $connection, SqliteSchema $schema)
    {
        $this->connection = $connection;
        $this->schema = $schema;
    }

    public function getDatabaseName(): string
    {
        return "'main'";
    }

    public function listTables(): array
    {
        $rows = $this->connection->fetchAll($this->schema->showTables());
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = $row['name'];
        }
        return $tables;
    }

    public function listTableColumns(string $tableName): array
    {
        $rows = $this->connection->fetchAll($this->schema->showTableColumns($tableName));
        $columns = [];
        foreach ($rows as $row) {
            $columns[] = [
                'name' => $row['name'],
                'type' => $row['type'],
                'null' => boolval($row['notnull']) == false,
                'default' => $row['dflt_value'] ?? null,
                'comment' => $row['comment'] ?? null,
                'extra' => $row['extra'] ?? null,
                'attributes' => $row['attributes'] ?? null,
            ];
        }
        return $columns;
    }

    public function listDatabases(): array
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the platform interface.", __METHOD__));
    }

    public function createDatabase(): void
    {
        $database = $this->getDatabaseName();
        if (file_exists($database)) {
            return;
        }

        touch($database);
    }

    public function createDatabaseIfNotExists(): void
    {
        $this->createDatabase();
    }

    public function dropDatabase(): void
    {
        $database = $this->getDatabaseName();
        if (!file_exists($database)) {
            return;
        }

        unlink($database);
    }

    public function createTable(string $tableName, array $columns, array $options = []): int
    {
        return $this->connection->executeStatement($this->schema->createTable($tableName, $columns, $options));
    }

    public function dropTable(string $tableName): int
    {
        return $this->connection->executeStatement($this->schema->dropTable($tableName));
    }

    public function addColumn(string $tableName, string $columnName, string $columnType): int
    {
        return $this->connection->executeStatement($this->schema->addColumn($tableName, $columnName, $columnType));
    }

    public function dropColumn(string $tableName, string $columnName): int
    {
        return $this->connection->executeStatement($this->schema->dropColumn($tableName, $columnName));
    }

    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): int
    {
        return $this->connection->executeStatement($this->schema->renameColumn($tableName, $oldColumnName, $newColumnName));
    }
}
