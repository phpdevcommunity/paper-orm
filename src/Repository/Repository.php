<?php

namespace PhpDevCommunity\PaperORM\Repository;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\Listener\EventDispatcher;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\EntityManagerInterface;
use PhpDevCommunity\PaperORM\Event\PreCreateEvent;
use PhpDevCommunity\PaperORM\Event\PreUpdateEvent;
use PhpDevCommunity\PaperORM\Expression\Expr;
use PhpDevCommunity\PaperORM\Hydrator\EntityHydrator;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;
use PhpDevCommunity\PaperORM\Query\Fetcher;
use PhpDevCommunity\PaperORM\Query\QueryBuilder;
use PhpDevCommunity\PaperORM\Serializer\SerializerToDb;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class Repository
{
    private EntityManagerInterface $em;
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $dispatcher = null)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
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

    public function find(int $pk): Fetcher
    {
        $entityName = $this->getEntityName();
        $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumnName($entityName);
        return $this->findBy()->where(Expr::equal($primaryKeyColumn, $pk))->first();
    }

    public function findBy(array $arguments = []): Fetcher
    {
        $expressions = [];
        foreach ($arguments as $key => $value) {
            if ($value instanceof EntityInterface) {
                $value = $value->getPrimaryKeyValue();
            } elseif (is_array($value)) {
                $expressions[] = Expr::in($key, $value);
                continue;
            } elseif (is_null($value)) {
                $expressions[] = Expr::isNull($key);
                continue;
            }
            elseif (is_string($value) && strtoupper($value) === "!NULL") {
                $expressions[] = Expr::isNotNull($key);
                continue;
            }
            elseif (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf('Argument "%s" must be scalar, array, null or EntityInterface, %s given', $key, gettype($value))
                );
            }
            $expressions[] = Expr::equal($key, $value);
        }
        return (new Fetcher($this->qb(), true))->where(...$expressions);
    }

    public function findOneBy(array $arguments = []): Fetcher
    {
        return $this->findBy($arguments)->first();
    }

    public function insert(object $entityToInsert): int
    {
        $this->checkEntity($entityToInsert);
        if ($entityToInsert->getPrimaryKeyValue() !== null) {
            throw new LogicException(static::class . sprintf(' Cannot insert an entity %s with a primary key ', get_class($entityToInsert)));
        }

        if ($this->dispatcher && $entityToInsert instanceof EntityInterface) {
            $this->dispatcher->dispatch(new PreCreateEvent($entityToInsert));
        }
        $qb = \PhpDevCommunity\Sql\QueryBuilder::insert($this->getTableName());

        $values = [];
        foreach ((new SerializerToDb($entityToInsert))->serialize() as $key => $value) {
            $keyWithoutBackticks = str_replace("`", "", $key);
            $qb->setValue($key, ":$keyWithoutBackticks");
            $values[$keyWithoutBackticks] = $value;
        }
        $rows = $this->em->getConnection()->executeStatement($qb, $values);
        $lastInsertId = $this->em->getConnection()->getPdo()->lastInsertId();
        if ($rows > 0) {
            $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumnName($entityToInsert);
            (new EntityHydrator($this->em->getCache()))->hydrate($entityToInsert, [$primaryKeyColumn => $lastInsertId]);
        }
        return $rows;
    }

    public function update(object $entityToUpdate): int
    {
        $this->checkEntity($entityToUpdate, true);
        if ($entityToUpdate->getPrimaryKeyValue() === null) {
            throw new LogicException(static::class . sprintf(' Cannot update an entity %s without a primary key ', get_class($entityToUpdate)));
        }

        /**
         * @var ProxyInterface|EntityInterface $entityToUpdate
         */
        if (!$entityToUpdate->__wasModified()) {
            return 0;
        }

        if ($this->dispatcher && $entityToUpdate instanceof EntityInterface) {
            $this->dispatcher->dispatch(new PreUpdateEvent($entityToUpdate));
        }
        $qb = \PhpDevCommunity\Sql\QueryBuilder::update($this->getTableName())
            ->where(
                sprintf('`%s` = %s',
                    ColumnMapper::getPrimaryKeyColumnName($this->getEntityName()),
                    $entityToUpdate->getPrimaryKeyValue()
                )
            );
        $values = [];
        foreach ((new SerializerToDb($entityToUpdate))->serialize($entityToUpdate->__getPropertiesModified()) as $key => $value) {
            $keyWithoutBackticks = str_replace("`", "", $key);
            $qb->set($key, ":$keyWithoutBackticks");
            $values[$keyWithoutBackticks] = $value;
        }
        $rows = $this->em->getConnection()->executeStatement($qb, $values);
        if ($rows > 0) {
            $entityToUpdate->__reset();
        }
        return $rows;

    }

    public function delete(object $entityToDelete): int
    {
        /**
         * @var ProxyInterface|EntityInterface $entityToUpdate
         */
        $this->checkEntity($entityToDelete, true);
        if ($entityToDelete->getPrimaryKeyValue() === null) {
            throw new LogicException(static::class . sprintf(' Cannot delete an entity %s without a primary key ', get_class($entityToDelete)));
        }

        $qb = \PhpDevCommunity\Sql\QueryBuilder::delete($this->getTableName())
            ->where(
                sprintf('`%s` = %s',
                    ColumnMapper::getPrimaryKeyColumnName($this->getEntityName()),
                    $entityToDelete->getPrimaryKeyValue()
                )
            );

        $rows = $this->em->getConnection()->executeStatement($qb);
        if ($rows > 0) {
            $entityToDelete->__destroy();
        }
        return $rows;
    }

    public function qb(): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($this->em);
        return $queryBuilder->select($this->getEntityName(), []);
    }

    private function checkEntity(object $entity, bool $proxy = false): void
    {
        $entityName = $this->getEntityName();
        if (!$entity instanceof $entityName) {
            throw new LogicException($entityName . ' Cannot insert an entity of type ' . get_class($entity));
        }

        if ($proxy && (!$entity instanceof ProxyInterface || !$entity->__isInitialized())) {
            throw new LogicException($entityName . ' Cannot use an entity is not a proxy');
        }
    }
}
