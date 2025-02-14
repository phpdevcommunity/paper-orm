<?php

namespace PhpDevCommunity\PaperORM\Repository;

use LogicException;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Expression\Expr;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Query\Fetcher;
use PhpDevCommunity\PaperORM\Query\QueryBuilder;
use PhpDevCommunity\Sql\QL\JoinQL;

abstract class Repository
{

    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get the name of the table associated with this repository.
     *
     * @return string The name of the table.
     */
    final public function getTableName(): string
    {
        $entityName = $this->getEntityName();
        return EntityMapper::getTable($entityName);
    }

    /**
     * Get the name of the model associated with this repository.
     *
     * @return class-string<EntityInterface> The name of the model.
     */
    abstract public function getEntityName(): string;

    public function findPk(int $pk): Fetcher
    {
        $entityName = $this->getEntityName();
        $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumn($entityName);
        return $this->findOneBy([$primaryKeyColumn => $pk]);
    }

    public function findOneBy(array $arguments = [], array $orderBy = []): Fetcher
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, 1);
        return new Fetcher($query, $arguments, false);
    }

    public function findBy(array $arguments = [], array $orderBy = [], ?int $limit = null): Fetcher
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, $limit);
        return new Fetcher($query, $arguments, true);
    }

    public function insert(object $entity): int
    {
        $this->checkEntity($entity);
        if ($entity->getPrimaryKeyValue() !== null) {
            throw new LogicException(static::class . sprintf(' Cannot insert an entity %s with a primary key ', get_class($entity)));
        }
    }

    public function update(object $entityToUpdate): int
    {
        $this->checkEntity($entityToUpdate);
        if ($entityToUpdate->getPrimaryKeyValue() === null) {
            throw new LogicException(static::class . sprintf(' Cannot update an entity %s without a primary key ', get_class($entityToUpdate)));
        }
    }

    public function delete(object $entityToDelete): int
    {
        $this->checkEntity($entityToDelete);
        if ($entityToDelete->getPrimaryKeyValue() === null) {
            throw new LogicException(static::class . sprintf(' Cannot delete an entity %s without a primary key ', get_class($entityToDelete)));
        }
    }

    public function qq(): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($this->em);
        return $queryBuilder->select($this->getEntityName(), []);
    }

    private function generateSelectQuery(array $arguments = [], array $orderBy = [], ?int $limit = null): QueryBuilder
    {
        $queryBuilder = $this->qq();
        $alias = $queryBuilder->getPrimaryAlias();
        foreach ($arguments as $key => $value) {
            $queryBuilder->where(Expr::equal(sprintf('%s.%s', $alias,$key), ':'.$key));
        }
        foreach ($orderBy as $key => $value) {
            $queryBuilder->orderBy(sprintf('%s.%s', $alias, $key), $value);
        }
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }
        return $queryBuilder;
    }


    private function checkEntity(object $entity): void
    {
        $entityName = $this->getEntityName();
        if (!$entity instanceof $entityName) {
            throw new LogicException($entityName . ' Cannot insert an entity of type ' . get_class($entity));
        }
    }
}
