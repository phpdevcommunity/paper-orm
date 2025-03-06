<?php

namespace PhpDevCommunity\PaperORM\Query;

use PhpDevCommunity\PaperORM\Expression\Expr;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;

final class Fetcher
{
    private QueryBuilder $queryBuilder;
    private array $arguments = [];
    private bool $collection;


    public function __construct(QueryBuilder $queryBuilder, bool $collection = true)
    {
        $this->queryBuilder = $queryBuilder;
        $this->collection = $collection;
    }

    public function where(Expr ...$expressions): Fetcher
    {
        $alias = $this->queryBuilder->getPrimaryAlias();
        foreach ($expressions as $expression) {
            $this->queryBuilder->where($expression->toPrepared($alias));
            foreach ($expression->getBoundValue() as $k => $v) {
                $this->arguments[ltrim($k, ':')] = $v;
            }
        }
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): Fetcher
    {
        $alias = $this->queryBuilder->getPrimaryAlias();
        $this->queryBuilder->orderBy(sprintf('%s.%s', $alias, $column), $direction);
        return $this;
    }

    public function limit(?int $limit): Fetcher
    {
        $this->queryBuilder->setMaxResults($limit);
        return $this;
    }

    public function first(): Fetcher
    {
        $this->collection = false;
        return $this;
    }

    public function with(string...$relationsClasses): Fetcher
    {
        foreach ($relationsClasses as $relationClass) {
            $this->joinRelation('left', $relationClass);
        }

        return $this;
    }

    public function has(string...$relationsClasses): Fetcher
    {
        foreach ($relationsClasses as $relationClass) {
            $this->joinRelation('inner', $relationClass);
        }
        return $this;
    }

    public function toArray(): ?array
    {
        if ($this->collection) {
            return $this->queryBuilder->getResult($this->arguments, false);
        }

        return $this->queryBuilder->getOneOrNullResult($this->arguments, false);
    }

    public function toObject()
    {
        if ($this->collection) {
            return $this->queryBuilder->getResult($this->arguments);
        }

        return $this->queryBuilder->getOneOrNullResult($this->arguments);
    }

    private function joinRelation(string $type, string $expression): void
    {
        $alias = $this->queryBuilder->getPrimaryAlias();
        if (class_exists($expression)) {
            if ($type === 'left') {
                $this->queryBuilder->leftJoin($alias, $expression);
                return;
            }
            $this->queryBuilder->innerJoin($alias, $expression);
            return;
        }

        $currentEntityName = $this->queryBuilder->getPrimaryEntityName();
        $currentAlias = $alias;
        foreach (explode('.', $expression) as $propertyName) {
            $column = ColumnMapper::getRelationColumnByProperty($currentEntityName, $propertyName);
            $targetEntityName = $column->getTargetEntity();
            $property = $column->getProperty();
            if ($type === 'left') {
                $this->queryBuilder->leftJoin($currentAlias, $targetEntityName, $property);
            } else {
                $this->queryBuilder->innerJoin($currentAlias, $targetEntityName, $property);
            }
            $currentAliases = $this->queryBuilder->getAliasesFromEntityName($targetEntityName, $property);
            $currentAlias = end($currentAliases);
            $currentEntityName = $targetEntityName;
        }
    }
}
