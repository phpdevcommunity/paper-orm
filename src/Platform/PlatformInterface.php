<?php

namespace PhpDevCommunity\PaperORM\Platform;

use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Metadata\DatabaseSchemaDiffMetadata;
use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
use PhpDevCommunity\PaperORM\Metadata\IndexMetadata;
use PhpDevCommunity\PaperORM\Schema\SchemaInterface;

/**
 * Interface PlatformInterface
 *
 * This interface defines methods for managing platform-specific database operations.
 */
interface PlatformInterface
{
    /**
     * Retrieves a list of all tables in the current database.
     *
     * @return array Returns an array containing the names of all tables in the database.
     */
    public function listTables(): array;

    /**
     * @param string $tableName
     * @return array<ColumnMetadata>
     */
    public function listTableColumns(string $tableName): array;

    /**
     * @param string $tableName
     * @return array<IndexMetadata>
     */
    public function listTableIndexes(string $tableName): array;

    /**
     * Retrieves a list of all databases available on the platform.
     *
     * @return array Returns an array containing the names of all databases.
     */
    public function listDatabases(): array;

    /**
     * Creates a new database on the platform.
     *
     * @return void
     */
    public function createDatabase(): void;

    /**
     * Creates a new database on the platform if it does not already exist.
     *
     * @return void
     */
    public function createDatabaseIfNotExists(): void;

    /**
     * Retrieves the name of the current database.
     *
     * @return string Returns the name of the current database.
     */
    public function getDatabaseName(): string;

    /**
     * Drops the current database from the platform.
     *
     * @return void
     */
    public function dropDatabase(): void;

    /**
     * @param string $tableName
     * @param array<Column> $columns
     * @param array $options
     * @return int
     */
    public function createTable(string $tableName, array $columns): int;
    public function createTableIfNotExists(string $tableName, array $columns): int;
    public function dropTable(string $tableName): int;
    public function addColumn(string $tableName, Column $column): int;
    public function dropColumn(string $tableName, Column $column): int;
    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): int;
    public function createIndex(IndexMetadata $indexMetadata): int;
    public function dropIndex(IndexMetadata $indexMetadata): int;
    public function getColumnTypeMappings(): array;
    public function getMaxLength(): int;
    public function getPrefixIndexName(): string;
    public function diff(string $tableName, array $columns, array $indexes): DatabaseSchemaDiffMetadata;
    public function getSchema(): SchemaInterface;
}

