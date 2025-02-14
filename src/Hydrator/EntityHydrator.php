<?php

namespace PhpDevCommunity\PaperORM\Hydrator;

use LogicException;
use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use ReflectionClass;

final class EntityHydrator
{
    private ?EntityMemcachedCache $cache;

    public function __construct(EntityMemcachedCache $cache = null)
    {
        $this->cache = $cache;
    }

    public function hydrate($objectOrClass, array $data): object
    {
        if (!class_exists($objectOrClass)) {
            throw new LogicException('Class ' . $objectOrClass . ' does not exist');
        }
        if (!is_subclass_of($objectOrClass, EntityInterface::class)) {
            throw new LogicException('Class ' . $objectOrClass . ' is not an PhpDevCommunity\PaperORM\Entity\EntityInterface');
        }
        $object = $objectOrClass;
        if (!is_object($object)) {
            $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumn($object);
            $object = $this->cache->get($objectOrClass, $data[$primaryKeyColumn]) ?: new $object();
            $this->cache->set($objectOrClass, $data[$primaryKeyColumn], $object);
        }
        $reflection = new ReflectionClass($object);
        $columns = array_merge(ColumnMapper::getColumns($object), ColumnMapper::getOneToManyRelations($object));

        foreach ($columns as $column) {

            if ($column instanceof OneToMany || $column instanceof JoinColumn) {
                $name = $column->getProperty();
            } else {
                $name = $column->getName();
            }
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $property = $reflection->getProperty($column->getProperty());
            $property->setAccessible(true);
            $value = $data[$name];
            if ($column instanceof JoinColumn) {
                if (!is_array($value) && $value !== null) {
                    $value = null;
                }
                $entityName = $column->getTargetEntity();
                if (is_array($value)) {
                    $value = $this->hydrate($entityName, $value);
                }
                $property->setValue($object, $value);
                continue;
            } elseif ($column instanceof OneToMany) {
                if (!is_array($value)) {
                    $value = [];
                }
                $entityName = $column->getTargetEntity();
                $storage = $property->getValue($object) ?: new ObjectStorage();
                foreach ($value as $item) {
                    $storage->add($this->hydrate($entityName, $item));
                }
                $property->setValue($object, $storage);
                continue;
            }

            $property->setValue($object, $column->convertToPHP($value));
        }
        return $object;
    }

}
