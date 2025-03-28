<?php

namespace PhpDevCommunity\PaperORM\Schema;

use LogicException;
use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
use PhpDevCommunity\PaperORM\Metadata\IndexMetadata;

class SqliteSchema implements SchemaInterface
{

    public function showDatabases(): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function showTables(): string
    {
        return "SELECT name FROM  sqlite_schema WHERE  type ='table' AND name NOT LIKE 'sqlite_%'";
    }

    public function showTableColumns(string $tableName): string
    {
        return sprintf("PRAGMA table_info('%s')", $tableName);
    }

    public function showForeignKeys(string $tableName): string
    {
        return sprintf("PRAGMA foreign_key_list('%s')", $tableName);
    }

    public function showTableIndexes(string $tableName): string
    {
        return sprintf("PRAGMA index_list('%s')", $tableName);
    }

    public function createDatabase(string $databaseName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function createDatabaseIfNotExists(string $databaseName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function dropDatabase(string $databaseName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    /**
     * @param string $tableName
     * @param array<ColumnMetadata> $columns
     * @param array $options
     * @return string
     */
    public function createTable(string $tableName, array $columns, array $options = []): string
    {
        $lines = [];
        $foreignKeys = [];
        foreach ($columns as $columnMetadata) {
            $line = sprintf('%s %s', $columnMetadata->getName(), $columnMetadata->getTypeWithAttributes());
            if ($columnMetadata->isPrimary()) {
                $line .= ' PRIMARY KEY';
            }
            if (!$columnMetadata->isNullable()) {
                $line .= ' NOT NULL';
            }
            if ($columnMetadata->getDefaultValue() !== null) {
                $line .= sprintf(' DEFAULT %s', $columnMetadata->getDefaultValue());
            }
            $lines[] = $line;

            if (!empty($columnMetadata->getForeignKeyMetadata())) {
                $foreignKeys[] = $columnMetadata;
            }
        }

        foreach ($foreignKeys as $foreignKey) {
            $lines[] = $this->foreignKeyConstraints($foreignKey);
        }
        $options['indexes'] = $options['indexes'] ?? [];

        $linesString = implode(',', $lines);

        $createTable = sprintf("CREATE TABLE $tableName (%s)", $linesString);

        $indexesSql = [];
        foreach ($options['indexes'] as $index) {
            $createTable .= $this->createIndex($index);
        }

        return $createTable.';'.implode(';', $indexesSql);
    }

    public function createTableIfNotExists(string $tableName, array $columns, array $options = []): string
    {
        $createTable = $this->createTable($tableName, $columns, $options);
        return str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $createTable);
    }

    public function createForeignKeyConstraints(string $tableName, ColumnMetadata $columnMetadata): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function dropTable(string $tableName): string
    {
        return sprintf('DROP TABLE %s', $tableName);
    }

    public function renameTable(string $oldTableName, string $newTableName): string
    {
        return sprintf('ALTER TABLE %s RENAME TO %s', $oldTableName, $newTableName);
    }

    public function addColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        $sql =  sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnMetadata->getName(), $columnMetadata->getTypeWithAttributes());

        if (!$columnMetadata->isNullable()) {
            $sql .= ' NOT NULL';
        }

        if ($columnMetadata->getDefaultValue() !== null) {
            $sql .= sprintf(' DEFAULT %s', $columnMetadata->getDefaultValue());
        }

        return $sql;
    }

    public function dropColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        if (!$this->supportsDropColumn()) {
            throw new \LogicException(sprintf("The method '%s' is not supported with SQLite versions older than 3.35.0.", __METHOD__));
        }
        return sprintf('ALTER TABLE %s DROP COLUMN %s', $tableName, $columnMetadata->getName());
    }

    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): string
    {
        return sprintf('ALTER TABLE %s RENAME COLUMN %s to %s', $tableName, $oldColumnName, $newColumnName);
    }

    public function modifyColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    /**
     * @param IndexMetadata $indexMetadata
     * @return string
     */
    public function createIndex(IndexMetadata $indexMetadata): string
    {
        $sql = sprintf('CREATE INDEX %s ON %s (%s)', $indexMetadata->getName(), $indexMetadata->getTableName(), implode(', ', $indexMetadata->getColumns()));
        if ($indexMetadata->isUnique()) {
            $sql = str_replace('CREATE INDEX', 'CREATE UNIQUE INDEX', $sql);
        }

        return $sql;
    }

    public function dropIndex(IndexMetadata $indexMetadata): string
    {
        return sprintf('DROP INDEX %s;', $indexMetadata->getName());
    }

    public function getDateTimeFormatString(): string
    {
        return 'Y-m-d H:i:s';
    }

    public function getDateFormatString(): string
    {
        return 'Y-m-d';
    }

    private function foreignKeyConstraints(ColumnMetadata $columnMetadata): string
    {
        $foreignKeys = $columnMetadata->getForeignKeyMetadata();
        if (empty($foreignKeys)) {
            return '';
        }
        $referencedTable = $foreignKeys['referencedTable'];
        $referencedColumn = $foreignKeys['referencedColumn'];

        return sprintf('FOREIGN KEY (%s) REFERENCES %s (%s)', $columnMetadata->getName(), $referencedTable, $referencedColumn);
    }

    public function supportsForeignKeyConstraints(): bool
    {
       return true;
    }

    public function supportsIndexes(): bool
    {
        return true;
    }

    public function supportsTransactions(): bool
    {
        return true;
    }

    public function supportsDropColumn(): bool
    {
        return \SQLite3::version()['versionString'] >= '3.35.0';
    }

    public function supportsModifyColumn(): bool
    {
        return false;
    }

    public function supportsAddForeignKey(): bool
    {
        return false;
    }
}
