<?php

namespace PhpDevCommunity\PaperORM\Repository;

use LogicException;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
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

    public function findPk(int $pk): ?object
    {
        $entityName = $this->getEntityName();
        $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumn($entityName);
        return $this->findOneBy([$primaryKeyColumn => $pk]);
    }

    public function findOneBy(array $arguments = [], array $orderBy = []): ?object
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, null);
        $item = $query->fetchAssociative();
        if ($item === false) {
            return null;
        }
        return $this->hydrate($this->getEntityName(), $item);
    }

    public function findBy(array $arguments = [], array $orderBy = [], ?int $limit = null): ObjectStorage
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, $limit);
        $data = $query->fetchAllAssociative();

        return $this->hydrateCollection($data);
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

    public function createQueryBuilder(string $alias = null): JoinQL
    {
        $entityName = $this->getEntityName();
        $columns = ColumnMapper::getColumns($entityName);
        $alias = $alias ?: $this->getTableName();
        $columnsToSelect = [];
        foreach ($columns as $column) {
            $columnsToSelect[] = sprintf('%s.`%s`', $alias, $column->getName());
        }
        return (new JoinQL($this->em->getConnection()->getPdo(), ColumnMapper::getPrimaryKeyColumn($entityName)))
            ->select($this->getTableName(), $alias, $columnsToSelect);
    }


    private function checkEntity(object $entity): void
    {
        $entityName = $this->getEntityName();
        if (!$entity instanceof $entityName) {
            throw new LogicException($entityName . ' Cannot insert an entity of type ' . get_class($entity));
        }
    }
}
