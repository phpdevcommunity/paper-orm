<?php

namespace PhpDevCommunity\PaperORM\Hydrator;

use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;

abstract class AbstractEntityHydrator
{
    abstract protected function instantiate(string $class, array $data): object;

    /**
     *
     * @param object|string $objectOrClass
     * @param array $data
     * @return object
     */
    public function hydrate($objectOrClass, array $data): object
    {
        if (!is_subclass_of($objectOrClass, EntityInterface::class)) {
            throw new \LogicException(
                sprintf('Class %s must implement %s', $objectOrClass, EntityInterface::class)
            );
        }

        $object = is_string($objectOrClass) ? $this->instantiate($objectOrClass, $data) : $objectOrClass;

        $this->mapProperties($object, $data);

        return $object;
    }

    private function mapProperties(object $object, array $data): void
    {
        $reflection = new \ReflectionClass($object);
        if ($reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }

        $columns = array_merge(
            ColumnMapper::getColumns($object),
            ColumnMapper::getOneToManyRelations($object)
        );

        $properties = [];

        foreach ($columns as $column) {
            $name = ($column instanceof OneToMany || $column instanceof JoinColumn)
                ? $column->getProperty()
                : $column->getName();

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
                $entityName = $column->getTargetEntity();
                $value = is_array($value) ? $this->hydrate($entityName, $value) : null;
                $property->setValue($object, $value);
                continue;
            }

            if ($column instanceof OneToMany) {
                $entityName = $column->getTargetEntity();
                $storage = $property->getValue($object) ?: new ObjectStorage();
                foreach ((array) $value as $item) {
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
    }
}

