<?php

namespace PhpDevCommunity\PaperORM\Persistence;

use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Event\PreCreateEvent;
use PhpDevCommunity\PaperORM\Event\PreUpdateEvent;
use PhpDevCommunity\PaperORM\Hydrator\EntityHydrator;
use PhpDevCommunity\PaperORM\Hydrator\ReadOnlyEntityHydrator;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;
use PhpDevCommunity\PaperORM\Serializer\SerializerToDb;
use PhpDevCommunity\Sql\QueryBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;

class EntityPersistence
{
    private PlatformInterface $platform;

    private ?EventDispatcherInterface $dispatcher;
    public function __construct(PlatformInterface $platform, EventDispatcherInterface $dispatcher = null)
    {
        $this->platform = $platform;
        $this->dispatcher = $dispatcher;
    }

    public function insert(object $entity): int
    {
        /**
         * @var EntityInterface $entity
         */
        $this->checkEntity($entity);
        if ($entity->getPrimaryKeyValue() !== null) {
            throw new \LogicException(static::class . sprintf(' Cannot insert an entity %s with a primary key ', get_class($entity)));
        }

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(new PreCreateEvent($entity));
        }
        $schema = $this->platform->getSchema();
        $tableName = EntityMapper::getTable($entity);
        $qb = QueryBuilder::insert($schema->quote($tableName));

        $values = [];
        foreach ((new SerializerToDb($entity))->serialize() as $key => $value) {
            $qb->setValue($schema->quote($key), ":$key");
            $values[$key] = $value;
        }
        $conn = $this->platform->getConnection();
        $rows = $conn->executeStatement($qb, $values);
        $lastInsertId = $conn->getPdo()->lastInsertId();
        if ($rows > 0) {
            $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumnName($entity);
            (new ReadOnlyEntityHydrator())->hydrate($entity, [$primaryKeyColumn => $lastInsertId]);
        }
        return $rows;
    }

    public function update(object $entity): int
    {
        /**
         * @var ProxyInterface|EntityInterface $entity
         */
        $this->checkEntity($entity, true);
        if ($entity->getPrimaryKeyValue() === null) {
            throw new \LogicException(static::class . sprintf(' Cannot update an entity %s without a primary key ', get_class($entity)));
        }

        if (!$entity->__wasModified()) {
            return 0;
        }

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(new PreUpdateEvent($entity));
        }
        $tableName = EntityMapper::getTable($entity);
        $schema = $this->platform->getSchema();
        $qb = QueryBuilder::update($schema->quote($tableName))
            ->where(
                sprintf('%s = %s',
                    $schema->quote(ColumnMapper::getPrimaryKeyColumnName($entity)),
                    $entity->getPrimaryKeyValue()
                )
            );
        $values = [];
        foreach ((new SerializerToDb($entity))->serialize($entity->__getPropertiesModified()) as $key => $value) {
            $qb->set($schema->quote($key), ":$key");
            $values[$key] = $value;
        }
        $conn = $this->platform->getConnection();
        $rows = $conn->executeStatement($qb, $values);
        if ($rows > 0) {
            $entity->__reset();
        }
        return $rows;
    }

    public function delete(object $entity): int
    {
        /**
         * @var ProxyInterface|EntityInterface $entity
         */
        $this->checkEntity($entity, true);
        if ($entity->getPrimaryKeyValue() === null) {
            throw new \LogicException(static::class . sprintf(' Cannot delete an entity %s without a primary key ', get_class($entity)));
        }

        $tableName = EntityMapper::getTable($entity);
        $schema = $this->platform->getSchema();
        $qb = QueryBuilder::delete($schema->quote($tableName))
            ->where(
                sprintf('%s = %s',
                    $schema->quote(ColumnMapper::getPrimaryKeyColumnName($entity)),
                    $entity->getPrimaryKeyValue()
                )
            );

        $conn = $this->platform->getConnection();
        $rows = $conn->executeStatement($qb);
        if ($rows > 0) {
            $entity->__destroy();
        }
        return $rows;
    }

    private function checkEntity(object $entity, bool $proxy = false): void
    {
        if (!$entity instanceof EntityInterface) {
            throw new \LogicException(sprintf(
                'Invalid entity of type "%s". Expected an instance of "%s".',
                get_class($entity),
                EntityInterface::class
            ));
        }

        if ($proxy && (!$entity instanceof ProxyInterface || !$entity->__isInitialized())) {
            throw new \LogicException(sprintf(
                'Entity of type "%s" is not a valid initialized proxy (expected instance of "%s").',
                get_class($entity),
                ProxyInterface::class
            ));
        }
    }


}
