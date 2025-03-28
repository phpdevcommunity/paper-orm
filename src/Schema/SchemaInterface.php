<?php

namespace PhpDevCommunity\PaperORM\Schema;

use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
use PhpDevCommunity\PaperORM\Metadata\IndexMetadata;

/**
 * Interface SchemaInterface
 *
 * Defines methods for managing database schema operations.
 */
interface SchemaInterface
{
    /**
     * Shows all databases.
     *
     * @return string Returns the SQL query for showing all databases.
     */
    public function showDatabases(): string;

    /**
     * Shows all tables in the database.
     *
     * @return string Returns the SQL query for showing all tables.
     */
    public function showTables(): string;

    public function showTableColumns(string $tableName): string;

    public function showForeignKeys(string $tableName): string;

    public function showTableIndexes(string $tableName): string;

    /**
     * Creates a new database.
     *
     * @param string $databaseName The name of the database to create.
     * @return string Returns the SQL query for creating the database.
     */
    public function createDatabase(string $databaseName): string;

    /**
     * Creates a new database if it does not exist.
     *
     * @param string $databaseName The name of the database to create.
     * @return string Returns the SQL query for creating the database if not exists.
     */
    public function createDatabaseIfNotExists(string $databaseName): string;

    /**
     * Drops an existing database.
     *
     * @param string $databaseName The name of the database to drop.
     * @return string Returns the SQL query for dropping the database.
     */
    public function dropDatabase(string $databaseName): string;

    /**
     * Creates a new table.
     *
     * @param string $tableName The name of the table to create.
     * @param array<ColumnMetadata> $columns An array of ColumnMetadata objects.
     * @param array $options Additional options for table creation.
     * @return string Returns the SQL query for creating the table.
     */
    public function createTable(string $tableName, array $columns, array $options = []): string;

    /**
     * Creates a new table if it does not exist.
     *
     * @param string $tableName The name of the table to create.
     * @param array<ColumnMetadata> $columns An array of ColumnMetadata objects.
     * @param array $options Additional options for table creation.
     * @return string Returns the SQL query for creating the table if not exists.
     */
    public function createTableIfNotExists(string $tableName, array $columns, array $options = []): string;

    /**
     * Adds a new foreign key constraint.
     *
     * @param string $tableName The name of the table to modify.
     * @param ColumnMetadata $columnMetadata
     * @return string Returns the SQL query for adding the foreign key constraint.
     */
    public function createForeignKeyConstraints(string $tableName, ColumnMetadata $columnMetadata) :string;

    /**
     * Drops an existing table.
     *
     * @param string $tableName The name of the table to drop.
     * @return string Returns the SQL query for dropping the table.
     */
    public function dropTable(string $tableName): string;

    /**
     * Renames an existing table.
     *
     * @param string $oldTableName The current name of the table.
     * @param string $newTableName The new name for the table.
     * @return string Returns the SQL query for renaming the table.
     */
    public function renameTable(string $oldTableName, string $newTableName): string;

    /**
     * Adds a new column to an existing table.
     *
     * @param string $tableName The name of the table to modify.
     * @param ColumnMetadata $columnMetadata The name of the new column.
     * @return string Returns the SQL query for adding the column.
     */
    public function addColumn(string $tableName, ColumnMetadata $columnMetadata): string;

    /**
     * Drops an existing column from a table.
     *
     * @param string $tableName The name of the table to modify.
     * @param ColumnMetadata $columnMetadata The column to drop.
     * @return string Returns the SQL query for dropping the column.
     */
    public function dropColumn(string $tableName, ColumnMetadata $columnMetadata): string;

    /**
     * Modifies the definition of an existing column in a table.
     *
     * @param string $tableName The name of the table to modify.
     * @param ColumnMetadata $columnMetadata The column to modify.
     * @return string Returns the SQL query for modifying the column.
     */
    public function modifyColumn(string $tableName, ColumnMetadata $columnMetadata): string;

    /**
     * Creates a new index on a table.
     *
     * @param IndexMetadata $indexMetadata
     * @return string Returns the SQL query for creating the index.
     */
    public function createIndex(IndexMetadata $indexMetadata): string;

    /**
     * Drops an existing index from a table.
     *
     * @param IndexMetadata $indexMetadata
     * @return string Returns the SQL query for dropping the index.
     */
    public function dropIndex(IndexMetadata $indexMetadata): string;

    /**
     * Returns the format string for DateTime objects.
     *
     * @return string The format string.
     */
    public function getDateTimeFormatString(): string;

    /**
     * Returns the format string for Date objects.
     *
     * @return string The format string.
     */
    public function getDateFormatString(): string;

    /**
     * Checks if the database supports foreign key constraints.
     *
     * @return bool True if supported, false otherwise.
     */
    public function supportsForeignKeyConstraints(): bool;

    /**
     * Checks if the database supports indexes.
     *
     * @return bool True if supported, false otherwise.
     */
    public function supportsIndexes(): bool;

    /**
     * Checks if the database supports transactions.
     *
     * @return bool True if supported, false otherwise.
     */
    public function supportsTransactions(): bool;

    /**
     * Checks if the database supports dropping columns from a table.
     *
     * @return bool True if supported, false otherwise.
     */
    public function supportsDropColumn(): bool;

    /**
     * Checks if the database supports modifying the type of a column.
     *
     * @return bool True if supported, false otherwise.
     */
    public function supportsModifyColumn(): bool;

    /**
     * Checks if the database supports adding foreign keys.
     *
     * @return bool True if supported, false otherwise.
     */
    public function supportsAddForeignKey(): bool;

}
