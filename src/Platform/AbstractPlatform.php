<?php

namespace PhpDevCommunity\PaperORM\Platform;

use LogicException;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Index;
use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
use PhpDevCommunity\PaperORM\Metadata\DatabaseSchemaDiffMetadata;
use PhpDevCommunity\PaperORM\Metadata\ForeignKeyMetadata;
use PhpDevCommunity\PaperORM\Metadata\IndexMetadata;

abstract class AbstractPlatform implements PlatformInterface
{
    final public function mapColumnsToMetadata(string $tableName, $columns): array
    {
        $columnsMetadata = [];
        foreach ($columns as $column) {
            if (!$column instanceof Column) {
                throw new LogicException(sprintf("The column '%s' is not supported.", is_object($column) ? get_class($column) : gettype($column)));
            }
            $columnsMetadata[] = $this->mapColumnToMetadata($tableName, $column);
        }

        return $columnsMetadata;
    }

    final public function mapColumnToMetadata(string $tableName, Column $column): ColumnMetadata
    {
        $mappings = $this->getColumnTypeMappings();
        $className = get_class($column);
        if (!array_key_exists($className, $mappings)) {
            throw new LogicException(sprintf("The column type '%s' is not supported.", $column->getType()));
        }

        $mapping = $mappings[$className];
        $sqlType = $mapping['type'];
        $args = $mapping['args'];
        $columnMetadata = ColumnMetadata::fromColumn($column, $sqlType,$args[0] ?? null, $args[1] ??  null);
        if ($columnMetadata->getForeignKeyMetadata() && $columnMetadata->getForeignKeyMetadata()->getName() === null) {
            $columnForeignKey = $columnMetadata->getForeignKeyMetadata();
            return $columnMetadata->replaceForeignKey(
                ForeignKeyMetadata::fromForeignKeyMetadataOverrideName(
                    $columnForeignKey,
                     $this->generateForeignKeyName($tableName, $columnForeignKey->getColumns())
                )
            );
        }
        return $columnMetadata;
    }

    /**
     * @param string $tableName
     * @param array<Column> $columns
     * @param array<Index> $indexes
     * @return void
     */
    final public function diff(string $tableName, array $columns, array $indexes): DatabaseSchemaDiffMetadata
    {
        list(
            $columnsToAdd,
            $columnsToUpdate,
            $columnsToDrop,
            $originalColumns,
            $foreignKeyToAdd,
            $foreignKeyToDrop,
            $originalForeignKeys,
            ) = $this->diffColumns($tableName, $columns);
        list($indexesToAdd, $indexesToUpdate, $indexesToDrop, $originalIndexes) = $this->diffIndexes($tableName, $indexes);

        return new DatabaseSchemaDiffMetadata(
            $columnsToAdd,
            $columnsToUpdate,
            $columnsToDrop,
            $originalColumns,
            $foreignKeyToAdd,
            $foreignKeyToDrop,
            $originalForeignKeys,
            $indexesToAdd,
            $indexesToUpdate,
            $indexesToDrop,
            $originalIndexes
        );
    }

    /**
     * @param string $tableName
     * @param array<Column> $columns
     * @return array
     *
     */
    private function diffColumns(string $tableName, array $columns): array
    {
        $columnsFromTable = $this->listTableColumns($tableName);
        $columnsExisting = [];
        $foreignKeysExisting = [];
        foreach ($columnsFromTable as $columnMetadata) {
            $columnsExisting[$columnMetadata->getName()] = $columnMetadata;
            if ($columnMetadata->getForeignKeyMetadata()) {
                $foreignKeysExisting[$columnMetadata->getForeignKeyMetadata()->getName()] = $columnMetadata->getForeignKeyMetadata();
            }
        }

        $columnsToAdd = [];
        $columnsToUpdate = [];
        $columnsToDrop = [];

        $foreignKeyToAdd = [];
        $foreignKeyToDrop = [];

        $columnsProcessed = [];
        $foreignKeysProcessed = [];
        foreach ($columns as $column) {
            $columnMetadata = $this->mapColumnToMetadata($tableName, $column);
            $willBeUpdated = false;
            if (isset($columnsExisting[$columnMetadata->getName()])) {
                $columnFromTable = $columnsExisting[$columnMetadata->getName()];
                if ($columnFromTable->toArray() != $columnMetadata->toArray()) {
                    $columnsToUpdate[] = $columnMetadata;
                    $willBeUpdated = true;
                }
            } else {
                $columnsToAdd[] = $columnMetadata;
            }
            $columnsProcessed[] = $columnMetadata->getName();
            if ($columnMetadata->getForeignKeyMetadata()) {
                $columnForeignKey = $columnMetadata->getForeignKeyMetadata();
                $foreignKeyName = $columnForeignKey->getName();
                if (isset($foreignKeysExisting[$foreignKeyName])) {
                    if ($willBeUpdated || $foreignKeysExisting[$foreignKeyName]->toArray() != $columnForeignKey->toArray()) {
                        $foreignKeyToDrop[] = $foreignKeysExisting[$foreignKeyName];
                        $foreignKeyToAdd[] = $columnForeignKey;
                    }
                }else {
                    $foreignKeyToAdd[] = $columnForeignKey;
                }

                $foreignKeysProcessed[$foreignKeyName] = true;
            }
        }

        foreach ($columnsExisting as $columnMetadata) {
            $willDrop = !in_array($columnMetadata->getName(), $columnsProcessed);
            if ($willDrop) {
                $columnsToDrop[] = $columnMetadata;
            }
            if ($columnMetadata->getForeignKeyMetadata()) {
                $columnForeignKey = $columnMetadata->getForeignKeyMetadata();
                $foreignKeyName = $columnForeignKey->getName();
                if (($willDrop && isset($foreignKeysExisting[$foreignKeyName])) || !isset($foreignKeysProcessed[$foreignKeyName])) {
                    $foreignKeyToDrop[] = $columnForeignKey;
                }
            }
        }

        $foreignKeyToAdd = array_values(array_unique($foreignKeyToAdd, SORT_REGULAR));
        $foreignKeyToDrop = array_values(array_unique($foreignKeyToDrop, SORT_REGULAR));

        return [
            $columnsToAdd,
            $columnsToUpdate,
            $columnsToDrop,
            $columnsFromTable,

            $foreignKeyToAdd,
            $foreignKeyToDrop,
            array_values($foreignKeysExisting),
        ];
    }

    /**
     * @param string $tableName
     * @param array<Index> $indexes
     * @return array
     */
    private function diffIndexes(string $tableName, array $indexes): array
    {
        $indexesFromTable = new ObjectStorage($this->listTableIndexes($tableName));
        $indexesToAdd = [];
        $indexesToUpdate = [];
        $indexesToDrop = [];

        $indexesExisting = [];
        foreach ($indexes as $index) {
            $indexMetadata = new IndexMetadata($tableName, $index->getName() ?: $this->generateIndexName($tableName, $index->getColumns()), $index->getColumns(), $index->isUnique());
            $indexFound = $indexesFromTable->findOneByMethod('getName', $indexMetadata->getName());
            if ($indexFound) {
                if ($indexMetadata->toArray() != $indexFound->toArray()) {
                    $indexesToUpdate[] = $indexMetadata;
                }
            } else {
                $indexesToAdd[] = $indexMetadata;
            }
            $indexesExisting[] = $indexMetadata->getName();
        }

        foreach ($indexesFromTable as $index) {
            if (!in_array($index->getName(), $indexesExisting)) {
                $indexesToDrop[] = $index;
            }
        }

        return [$indexesToAdd, $indexesToUpdate, $indexesToDrop, $indexesFromTable->toArray()];
    }


    final protected function generateIndexName(string $tableName, array $columnNames): string
    {
        $hash = implode('', array_map(static function ($column) {
            return dechex(crc32($column));
        }, array_merge([$tableName], $columnNames)));

        return strtoupper(substr($this->getPrefixIndexName() . $hash, 0, $this->getMaxLength()));
    }

    final protected function generateForeignKeyName(string $tableName, array $columnNames): string
    {
        $hash = implode('', array_map(static function ($column) {
            return dechex(crc32($column));
        }, array_merge([$tableName], $columnNames)));

        return strtoupper(substr($this->getPrefixForeignKeyName() . $hash, 0, $this->getMaxLength()));

    }
}
