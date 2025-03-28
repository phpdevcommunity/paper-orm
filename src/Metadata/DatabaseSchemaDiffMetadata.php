<?php

namespace PhpDevCommunity\PaperORM\Metadata;

use LogicException;

final class DatabaseSchemaDiffMetadata
{
    private array $columnsToAdd = [];
    private array $columnsToUpdate = [];
    private array $columnsToDelete = [];
    private array $originalColumns = [];


    private array $indexesToAdd = [];
    private array $indexesToUpdate = [];
    private array $indexesToDelete = [];
    private array $originalIndexes = [];

    /**
     * @param ColumnMetadata[] $columnsToAdd
     * @param ColumnMetadata[] $columnsToUpdate
     * @param ColumnMetadata[] $columnsToDelete
     * @param ColumnMetadata[] $originalColumns
     */
    public function __construct(
        array $columnsToAdd,
        array $columnsToUpdate,
        array $columnsToDelete,
        array $originalColumns,
        array $indexesToAdd,
        array $indexesToUpdate,
        array $indexesToDelete,
        array $originalIndexes
    )
    {
        foreach ($columnsToAdd as $column) {
            if (!$column instanceof ColumnMetadata) {
                throw new LogicException(sprintf("The column '%s' is not supported.", get_class($column)));
            }
            $this->columnsToAdd[$column->getName()] = $column;
        }

        foreach ($columnsToUpdate as $column) {
            if (!$column instanceof ColumnMetadata) {
                throw new LogicException(sprintf("The column '%s' is not supported.", get_class($column)));
            }
            $this->columnsToUpdate[$column->getName()] = $column;
        }

        foreach ($columnsToDelete as $column) {
            if (!$column instanceof ColumnMetadata) {
                throw new LogicException(sprintf("The column '%s' is not supported.", get_class($column)));
            }
            $this->columnsToDelete[$column->getName()] = $column;
        }

        foreach ($originalColumns as $column) {
            if (!$column instanceof ColumnMetadata) {
                throw new LogicException(sprintf("The column '%s' is not supported.", get_class($column)));
            }
            $this->originalColumns[$column->getName()] = $column;
        }


        foreach ($indexesToAdd as $index) {
            if (!$index instanceof IndexMetadata) {
                throw new LogicException(sprintf("The index '%s' is not supported.", get_class($index)));
            }
            $this->indexesToAdd[$index->getName()] = $index;
        }

        foreach ($indexesToUpdate as $index) {
            if (!$index instanceof IndexMetadata) {
                throw new LogicException(sprintf("The index '%s' is not supported.", get_class($index)));
            }
            $this->indexesToUpdate[$index->getName()] = $index;
        }

        foreach ($indexesToDelete as $index) {
            if (!$index instanceof IndexMetadata) {
                throw new LogicException(sprintf("The index '%s' is not supported.", get_class($index)));
            }
            $this->indexesToDelete[$index->getName()] = $index;
        }

        foreach ($originalIndexes as $index) {
            if (!$index instanceof IndexMetadata) {
                throw new LogicException(sprintf("The index '%s' is not supported.", get_class($index)));
            }
            $this->originalIndexes[$index->getName()] = $index;
        }
    }

    /**
     * @return ColumnMetadata[]
     */
    public function getColumnsToAdd(): array
    {
        return $this->columnsToAdd;
    }

    /**
     * @return ColumnMetadata[]
     */
    public function getColumnsToUpdate(): array
    {
        return $this->columnsToUpdate;
    }

    /**
     * @return ColumnMetadata[]
     */
    public function getColumnsToDelete(): array
    {
        return $this->columnsToDelete;
    }

    public function getOriginalColumn(string $name): ColumnMetadata
    {
        if (!isset($this->originalColumns[$name])) {
            throw new LogicException(sprintf("The column '%s' is not supported.", $name));
        }
        return $this->originalColumns[$name];
    }


    /**
     * @return IndexMetadata[]
     */
    public function getIndexesToAdd(): array
    {
        return $this->indexesToAdd;
    }


    /**
     * @return IndexMetadata[]
     */
    public function getIndexesToUpdate(): array
    {
        return $this->indexesToUpdate;
    }


    /**
     * @return IndexMetadata[]
     */
    public function getIndexesToDelete(): array
    {
        return $this->indexesToDelete;
    }


    public function getOriginalIndex(string $name): IndexMetadata
    {
        if (!isset($this->originalIndexes[$name])) {
            throw new LogicException(sprintf("The index '%s' is not supported.", $name));
        }
        return $this->originalIndexes[$name];
    }

}
