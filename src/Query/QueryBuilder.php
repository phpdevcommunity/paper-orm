<?php

namespace PhpDevCommunity\PaperORM\Query;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Hydrator\ArrayHydrator;
use PhpDevCommunity\PaperORM\Hydrator\EntityHydrator;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use PhpDevCommunity\Sql\QL\JoinQL;

final class QueryBuilder
{
    private EntityManager $em;

    private string $primaryKey;

    private AliasGenerator $aliasGenerator;
    private array $select = [];
    private array $where = [];
    private array $orderBy = [];

    private array $joins = [];
    private array $joinsAlreadyAdded = [];

    private ?int $maxResults = null;

    public function __construct(EntityManager $em, string $primaryKey = 'id')
    {
        $this->em = $em;
        $this->aliasGenerator = new AliasGenerator();
        $this->primaryKey = $primaryKey;
    }

    public function getResultIterator(array $parameters = [], bool $objectHydrator = true): iterable
    {
        foreach ($this->buildSqlQuery()->getResultIterator($parameters) as $item) {
            yield $this->hydrate([$item], $objectHydrator)[0];
        }
    }

    public function getResult(array $parameters = [], bool $objectHydrator = true): array
    {
        return $this->hydrate($this->buildSqlQuery()->getResult($parameters), $objectHydrator);
    }

    public function getOneOrNullResult(array $parameters = [], bool $objectHydrator = true)
    {
        $item = $this->buildSqlQuery()->getOneOrNullResult($parameters);
        if ($item === null) {
            return null;
        }
        return $this->hydrate([$item], $objectHydrator)[0];
    }

    public function select(string $entityName, array $properties = []): self
    {
        $this->select = [
            'table' => $this->getTableName($entityName),
            'entityName' => $entityName,
            'alias' => $this->aliasGenerator->generateAlias($entityName),
            'properties' => $properties
        ];
        return $this;
    }

    public function getPrimaryAlias(): string
    {
        if (empty($this->select)) {
            throw new LogicException('Select must be called before getPrimaryAlias');
        }

        return $this->select['alias'];
    }

    public function getPrimaryEntityName(): string
    {
        if (empty($this->select)) {
            throw new LogicException('Select must be called before getPrimaryEntityName');
        }

        return $this->select['entityName'];
    }
    public function where(string ...$expressions): self
    {
        foreach ($expressions as $expression) {
            $this->where[] = $expression;
        }
        return $this;
    }

    public function orderBy(string $sort, string $order = 'ASC'): self
    {
        $this->orderBy[] = [
            'sort' => $sort,
            'order' => $order
        ];
        return $this;
    }

    public function resetWhere(): self
    {
        $this->where = [];
        return $this;
    }

    public function resetOrderBy() : self
    {
        $this->orderBy = [];
        return $this;
    }

    public function leftJoin(string $fromAliasOrEntityName, string $targetEntityName, ?string $property = null): self
    {
        return $this->join('LEFT', $fromAliasOrEntityName, $targetEntityName, $property);
    }

    public function innerJoin(string $fromAliasOrEntityName, string $targetEntityName, ?string $property = null): self
    {
        return $this->join('INNER', $fromAliasOrEntityName, $targetEntityName, $property);
    }

    public function setMaxResults(?int $maxResults): self
    {
        if ($this->select === []) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query '
            );
        }
        $this->maxResults = $maxResults;
        return $this;
    }

    private function join(string $type, string $fromAliasOrEntityName, string $targetEntityName, ?string $property = null): self
    {
        if (class_exists($fromAliasOrEntityName)) {
            $fromAliases = $this->getAliasesFromEntityName($fromAliasOrEntityName);
        } else {
            $fromAliases = [$fromAliasOrEntityName];
        }

        /**
         * @comment IS security , we need to check if the join is already added !!!
         */
        $key = md5(sprintf('%s.%s.%s.%s', $type,$fromAliasOrEntityName, $targetEntityName, $property));
        if (in_array($key, $this->joinsAlreadyAdded)) {
            return $this;
        }

        $this->joinsAlreadyAdded[] = $key;

        foreach ($fromAliases as $fromAlias) {
            $fromEntityName = $this->getEntityNameFromAlias($fromAlias);
            $columns = $this->getRelationsColumns($fromEntityName, $targetEntityName, $property);
            foreach ($columns as $column) {
                $alias = $this->aliasGenerator->generateAlias($targetEntityName);
                $this->joins[$alias] = [
                    'type' => $type,
                    'alias' => $alias,
                    'targetEntity' => $targetEntityName,
                    'targetTable' => $this->getTableName($targetEntityName),
                    'fromEntityName' => $fromEntityName,
                    'fromTable' => $this->getTableName($fromEntityName),
                    'fromAlias' => $fromAlias,
                    'column' => $column,
                    'property' => $property,
                    'isOneToMany' => $column instanceof OneToMany
                ];
            }

        }

        return $this;

    }

    private function convertPropertiesToColumns(string $entityName, array $properties): array
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

            $columns[] = $column->getName();
        }
        return $columns;
    }

    /**
     * @param string $entityName
     * @param string $targetEntityName
     * @param string|null $property
     * @return array<int,JoinColumn|OneToMany>
     */
    private function getRelationsColumns(string $entityName, string $targetEntityName, ?string $property = null): array
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
            throw new InvalidArgumentException("Entity {$targetEntityName} not found in class " . $entityName);
        }

        $columns = [];

        if ($property) {
            $column = $relationsColumns[$property] ?? null;
            if ($column) {
                $columns[] = $column;
            }
        } else {
            foreach ($relationsColumns as $column) {
                if ($column->getTargetEntity() === $targetEntityName) {
                    $columns[] = $column;
                }
            }
        }

        if ($columns === []) {
            throw new InvalidArgumentException("Entity {$targetEntityName} not found in class " . $entityName);
        }

        return $columns;
    }

    private function getTableName(string $entityName): string
    {
        return EntityMapper::getTable($entityName);
    }

    public function buildSqlQuery(): JoinQL
    {
        if ($this->select === []) {
            throw new LogicException('No query specified');
        }

        $properties = $this->select['properties'];
        $entityName = $this->select['entityName'];
        $alias = $this->select['alias'];
        $table = $this->select['table'];

        if ($properties === []) {
            $properties = ColumnMapper::getColumns($entityName);
        }
        $columns = $this->convertPropertiesToColumns($entityName, $properties);
        $joinQl = new JoinQL($this->em->getConnection()->getPdo(), $this->primaryKey);
        $joinQl->select($table, $alias, $columns);
        foreach ($this->joins as $join) {
            $fromAlias = $join['fromAlias'];
            $targetTable = $join['targetTable'];
            $targetEntity = $join['targetEntity'];
            $alias = $join['alias'];
            /**
             * @var JoinColumn|OneToMany $column
             */
            $column = $join['column'];
            $isOneToMany = $join['isOneToMany'];
            $type = $join['type'];
            $name = null;

            $columns = $this->convertPropertiesToColumns($targetEntity, ColumnMapper::getColumns($targetEntity));
            $joinQl->addSelect($alias, $columns);
            $criteria = [];
            if ($column instanceof JoinColumn) {
                $criteria = [$column->getName() => $column->getReferencedColumnName()];
                $name = $column->getName();
            } elseif ($column instanceof OneToMany) {
                $criteria = $column->getCriteria();
                $mappedBy = $column->getMappedBy(); //@todo VOIR SI RENDRE OBLIGATOIRE : A DISCUTER !!!
                if ($mappedBy) {
                    $columnMappedBy = ColumnMapper::getColumnByProperty($targetEntity, $mappedBy);
                    if (!$columnMappedBy instanceof JoinColumn) {
                        throw new InvalidArgumentException("Property mapped by {$mappedBy} not found in class " . $targetEntity);
                    }
                    $name = $columnMappedBy->getName();
                    $criteria = $criteria + [$columnMappedBy->getReferencedColumnName() => $columnMappedBy->getName()];
                }
            }
            $joinConditions = [];
            foreach ($criteria as $key => $value) {
                $value = "$alias.$value";
                $joinConditions[] = "$fromAlias.$key = $value";
            }
            if ($type === 'LEFT') {
                $joinQl->leftJoin($fromAlias, $targetTable, $alias, $joinConditions, $isOneToMany, $column->getProperty(), $name);
            } elseif ($type === 'INNER') {
                $joinQl->innerJoin($fromAlias, $targetTable, $alias, $joinConditions, $isOneToMany, $column->getProperty(), $name);
            }
        }

        foreach ($this->where as $where) {
            $joinQl->where($this->resolveExpression($where));
        }

        foreach ($this->orderBy as $orderBy) {
            $joinQl->orderBy($this->resolveExpression($orderBy['sort']), $orderBy['order']);
        }

        if ($this->maxResults) {
            $joinQl->setMaxResults($this->maxResults);
        }
        return $joinQl;
    }

    public function getAliasesFromEntityName(string $entityName, string $property = null): array
    {
        $aliases = [];
        if (isset($this->select['entityName']) && $this->select['entityName'] === $entityName) {
            $aliases[] = $this->select['alias'];
        }
        foreach ($this->joins as $keyAsAlias => $join) {
            if ($join['targetEntity'] === $entityName && $join['property'] === $property) {
                $aliases[] = $keyAsAlias;
            }
        }

        if ($aliases === []) {
            throw new LogicException('Alias not found for ' . $entityName);
        }

        return $aliases;
    }

    private function getEntityNameFromAlias(string $alias): string
    {
        if (isset($this->select['alias']) && $this->select['alias'] === $alias) {
            return $this->select['entityName'];
        }
        $entityName = null;
        foreach ($this->joins as $keyAsAlias => $join) {
            if ($keyAsAlias === $alias) {
                $entityName = $join['targetEntity'];
                break;
            }
        }
        if ($entityName === null) {
            throw new LogicException('Entity name not found for ' . $alias);
        }

        return $entityName;
    }

    private function hydrate(array $data, bool $objectHydrator = true): array
    {
        if (!$objectHydrator) {
            $hydrator = new ArrayHydrator();
        } else {
            $hydrator = new EntityHydrator($this->em->getCache());
        }
        $collection = [];
        foreach ($data as $item) {
            $collection[] = $hydrator->hydrate($this->select['entityName'], $item);
        }
        return $collection;
    }

    private function resolveExpression(string $expression): string
    {
        $aliases = AliasDetector::detect($expression);
        foreach ($aliases as $alias => $properties) {
            $fromEntityName = $this->getEntityNameFromAlias($alias);
            foreach ($properties as $property) {
                $column = ColumnMapper::getColumnByProperty($fromEntityName, $property);
                if ($column === null) {
                    throw new InvalidArgumentException(sprintf('Property %s not found in class %s or is a collection and cannot be used in an expression', $property, $fromEntityName));
                }
                $expression = str_replace($alias . '.' . $property, $alias . '.'.$column->getName(), $expression);
            }

        }
        return $expression;
    }
}
