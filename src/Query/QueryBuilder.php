<?php

namespace PhpDevCommunity\PaperORM\Query;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\EntityManagerInterface;
use PhpDevCommunity\PaperORM\Hydrator\ArrayHydrator;
use PhpDevCommunity\PaperORM\Hydrator\EntityHydrator;
use PhpDevCommunity\PaperORM\Hydrator\ReadOnlyEntityHydrator;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Schema\SchemaInterface;
use PhpDevCommunity\Sql\QL\JoinQL;

final class QueryBuilder
{

    public const HYDRATE_OBJECT = 'object';
    public const HYDRATE_OBJECT_READONLY = 'readonly';
    public const HYDRATE_ARRAY = 'array';
    private PlatformInterface $platform;
    private SchemaInterface $schema;
    private EntityMemcachedCache $cache;

    private string $primaryKey;

    private AliasGenerator $aliasGenerator;
    private array $select = [];
    private array $where = [];
    private array $orderBy = [];

    private array $joins = [];
    private array $joinsAlreadyAdded = [];

    private ?int $maxResults = null;

    public function __construct(EntityManagerInterface $em, string $primaryKey = 'id')
    {
        $this->platform = $em->getPlatform();
        $this->schema = $this->platform->getSchema();;
        $this->cache = $em->getCache();
        $this->aliasGenerator = new AliasGenerator();
        $this->primaryKey = $primaryKey;
    }

    public function getResultIterator(array $parameters = [], string $hydrationMode = self::HYDRATE_OBJECT): iterable
    {
        foreach ($this->buildSqlQuery()->getResultIterator($parameters) as $item) {
            yield $this->hydrate([$item], $hydrationMode)[0];
        }
    }

    public function getResult(array $parameters = [],  string $hydrationMode = self::HYDRATE_OBJECT): array
    {
        return $this->hydrate($this->buildSqlQuery()->getResult($parameters), $hydrationMode);
    }

    public function getOneOrNullResult(array $parameters = [], string $hydrationMode = self::HYDRATE_OBJECT)
    {
        $item = $this->buildSqlQuery()->getOneOrNullResult($parameters);
        if ($item === null) {
            return null;
        }
        return $this->hydrate([$item], $hydrationMode)[0];
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

            $columns[] = $this->schema->quote($column->getName());
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
        $joinQl = new JoinQL($this->platform->getConnection()->getPdo(), $this->primaryKey);
        $joinQl->select($this->schema->quote($table), $alias, $columns);
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
            $targetTable = $this->schema->quote($targetTable);
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

    private function hydrate(array $data, string $hydrationMode): array
    {
        if ($hydrationMode === self::HYDRATE_ARRAY) {
            $hydrator = new ArrayHydrator();
        } elseif ($hydrationMode === self::HYDRATE_OBJECT_READONLY) {
            $hydrator = new ReadOnlyEntityHydrator();
        } else {
            $hydrator = new EntityHydrator($this->cache);
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
                $expression = str_replace($alias . '.' . $property, $this->schema->quote($alias) . '.'.$this->schema->quote($column->getName()), $expression);
            }

        }
        return $expression;
    }
}
