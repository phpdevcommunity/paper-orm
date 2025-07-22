<?php

namespace PhpDevCommunity\PaperORM\Platform;

use LogicException;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Index;
use PhpDevCommunity\PaperORM\Metadata\DatabaseSchemaDiffMetadata;
use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
use PhpDevCommunity\PaperORM\Metadata\IndexMetadata;
use PhpDevCommunity\PaperORM\Schema\SchemaInterface;

abstract class AbstractPlatform implements PlatformInterface
{
    final public function mapColumnsToMetadata(array $columns): array
    {
        $columnsMetadata = [];
        foreach ($columns as $column) {
            if (!$column instanceof Column) {
                throw new LogicException(sprintf("The column '%s' is not supported.", is_object($column) ? get_class($column) : gettype($column)));
            }
            $columnsMetadata[] = $this->mapColumnToMetadata($column);
        }

        return $columnsMetadata;
    }
    final public function mapColumnToMetadata(Column $column): ColumnMetadata
    {
        $mappings = $this->getColumnTypeMappings();
        $className = get_class($column);
        if (!array_key_exists($className, $mappings)) {
            throw new LogicException(sprintf("The column type '%s' is not supported.", $column->getType()));
        }

        $sqlType = $mappings[$className];
        return ColumnMetadata::fromColumn($column, $sqlType);
    }

    /**
     * @param string $tableName
     * @param array<Column> $columns
     * @param array<Index> $indexes
     * @return void
     */
    final public function diff(string $tableName, array $columns, array $indexes): DatabaseSchemaDiffMetadata
    {
        list($columnsToAdd, $columnsToUpdate, $columnsToDrop, $originalColumns) = $this->diffColumns($tableName, $columns);
        list($indexesToAdd, $indexesToUpdate, $indexesToDrop, $originalIndexes) = $this->diffIndexes($tableName, $indexes);
        return new DatabaseSchemaDiffMetadata(
            $columnsToAdd,
            $columnsToUpdate,
            $columnsToDrop,
            $originalColumns,

            $indexesToAdd,
            $indexesToUpdate,
            $indexesToDrop,
            $originalIndexes
        );
    }

    private function diffColumns(string $tableName, array $columns): array
    {
        $columnsFromTable = $this->listTableColumns($tableName);
        $columnsFromTableByName = [];
        foreach ($columnsFromTable as $columnMetadata) {
            $columnsFromTableByName[$columnMetadata->getName()] = $columnMetadata;
        }

        $columnsToAdd = [];
        $columnsToUpdate = [];
        $columnsToDrop = [];

        $columnsExisting = [];
        foreach ($columns as $column) {
            $columnMetadata = $this->mapColumnToMetadata($column);
            if (isset($columnsFromTableByName[$columnMetadata->getName()])) {
                $columnFromTable = $columnsFromTableByName[$columnMetadata->getName()];
                if ($columnFromTable->toArray() != $columnMetadata->toArray()) {
                    $columnsToUpdate[] = $columnMetadata;
                }
            } else {
                $columnsToAdd[] = $columnMetadata;
            }
            $columnsExisting[] = $columnMetadata->getName();
        }


        foreach ($columnsFromTableByName as $columnMetadata) {
            if (!in_array($columnMetadata->getName(), $columnsExisting)) {
                $columnsToDrop[] = $columnMetadata;
            }
        }
        return [$columnsToAdd, $columnsToUpdate, $columnsToDrop, $columnsFromTable];
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
            $indexFound = $indexesFromTable->findOneBy('getName', $indexMetadata->getName());
            if ($indexFound) {
                if ($indexMetadata->toArray() != $indexFound->toArray()) {
                    $indexesToUpdate[] = $indexMetadata;
                }
            }else {
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
}
