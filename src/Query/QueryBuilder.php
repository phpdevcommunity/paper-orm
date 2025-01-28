<?php

namespace PhpDevCommunity\PaperORM\Query;

use InvalidArgumentException;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use PhpDevCommunity\PaperORM\PaperConnection;

final class QueryBuilder
{
    private PaperConnection $connection;

    private array $select = [];

    private array $joins = [];

    private int $lastAlias = 0;


    public function __construct(PaperConnection $connection)
    {
        $this->connection = $connection;
    }

    public function select(string $entityName, string $alias, array $properties = []): self
    {
        $this->select = [
            'table' => $this->getTableName($entityName),
            'entityName' => $entityName,
            'alias' => $alias,
            'properties' => $properties
        ];
//        if ($properties === []) {
//            $properties = ColumnMapper::getColumns($entityName);
//        }
//        $columns = $this->convertPropertiesToColumns($entityName, $alias, $properties);
//        $this->joinQL->select($this->getTableName($entityName), $alias, $columns);


        return $this;
    }


    public function leftJoin(string $fromEntityName, string $property): self
    {
        $column = $this->getRelationsColumns($fromEntityName, $property);
        $alias = 'r_' . $this->lastAlias++;
        $this->joins[$alias] = [
            'type' => 'LEFT',
            'fromEntityName' => $fromEntityName,
            'property' => $column->getProperty(),
            'column' => $column
        ];
        return $this;
    }

    public function innerJoin(string $fromEntityName, string $targetEntityName, ?string $property = null): self
    {
        $columns = $this->getRelationsColumns($fromEntityName, $targetEntityName, $property);

        foreach ($columns as $column) {
            $alias = 'r_' . $this->lastAlias++;
            $this->joins[$alias] = [
                'type' => 'INNER',
                'targetEntity' => $targetEntityName,
                'fromEntityName' => $fromEntityName,
                'property' => $column->getProperty(),
                'column' => $column
            ];
        }
        return $this;
    }

    private function convertPropertiesToColumns(string $entityName, string $alias, array $properties): array
    {
        $columns = [];
        foreach ($properties as $property) {
            if ($property instanceof Column) {
                $propertyName = $property->getProperty();
            } elseif (is_string($property)) {
                $propertyName = $property;
            } else {
                throw new InvalidArgumentException("Property {$property} not found in class " . $entityName);
            }

            $column = ColumnMapper::getColumnByProperty($entityName, $propertyName);
            if ($column === null) {
                throw new InvalidArgumentException("Property {$propertyName} not found in class " . $entityName);
            }

            $columns[] = sprintf('%s.`%s`', $alias, $column->getName());
        }
        return $columns;
    }

    /**
     * @param string $entityName
     * @param string $property
     * @return JoinColumn|OneToMany
     */
    private function getRelationsColumns(string $entityName, string $property)
    {
        $relationsColumns = [];
        foreach (ColumnMapper::getColumns($entityName) as $column) {
            if ($column instanceof JoinColumn) {
                $relationsColumns[$column->getProperty()] = $column;
            }
        }

        foreach (ColumnMapper::getOneToManyRelations($entityName) as $column) {
            $relationsColumns[$column->getProperty()] = $column;
        }

        if ($relationsColumns === []) {
            throw new InvalidArgumentException("Property {$property} not found in class " . $entityName);
        }

        $column = $relationsColumns[$property] ?? null;
        if ($column === null) {
            throw new InvalidArgumentException("Entity {$property} not found in class " . $entityName);
        }

        return $column;
    }

    private function getTableName(string $entityName): string
    {
        return EntityMapper::getTable($entityName);
    }

}
