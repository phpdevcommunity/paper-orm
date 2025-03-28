<?php

namespace PhpDevCommunity\PaperORM\Hydrator;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;
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
            $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumnName($object);
            $object = $this->cache->get($objectOrClass, $data[$primaryKeyColumn]) ?: $this->createProxyObject($object);
            $this->cache->set($objectOrClass, $data[$primaryKeyColumn], $object);
        }
        $reflection = new ReflectionClass($object);
        if ($reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }
        $columns = array_merge(ColumnMapper::getColumns($object), ColumnMapper::getOneToManyRelations($object));

        $properties = [];
        foreach ($columns as $column) {

            if ($column instanceof OneToMany || $column instanceof JoinColumn) {
                $name = $column->getProperty();
            } else {
                $name = $column->getName();
            }

            if (!array_key_exists($name, $data)) {
                continue;
            }
            $value = $data[$name];

            if (!$column instanceof OneToMany) {
                $properties[$column->getProperty()] = $column;
            }

            $property = $reflection->getProperty($column->getProperty());
            $property->setAccessible(true);
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

        if ($object instanceof ProxyInterface) {
            $object->__setInitialized($properties);
        }

        return $object;
    }

    private function createProxyObject(string $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class $class does not exist.");
        }


        $sanitizedClass = str_replace('\\', '_', $class);
        $proxyClass = 'Proxy_' . $sanitizedClass;

        if (!class_exists($proxyClass)) {
            eval("
            class $proxyClass extends \\$class implements \\PhpDevCommunity\\PaperORM\\Proxy\\ProxyInterface {
                use \\PhpDevCommunity\\PaperORM\\Proxy\\ProxyInitializedTrait;
            }
        ");
        }

        return new $proxyClass();
    }

}
