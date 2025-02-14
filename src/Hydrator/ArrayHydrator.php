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

final class ArrayHydrator
{

    public function __construct()
    {
    }

    public function hydrate(string $object, array $data): array
    {
        if (!class_exists($object)) {
            throw new LogicException('Class ' . $object . ' does not exist');
        }
        if (!is_subclass_of($object, EntityInterface::class)) {
            throw new LogicException('Class ' . $object . ' is not an PhpDevCommunity\PaperORM\Entity\EntityInterface');
        }
        $columns = array_merge(ColumnMapper::getColumns($object), ColumnMapper::getOneToManyRelations($object));

        $result = [];
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
            $propertyName = $column->getProperty();
            if ($column instanceof JoinColumn) {
                if (!is_array($value) && $value !== null) {
                    $value = null;
                }
                $entityName = $column->getTargetEntity();
                if (is_array($value)) {
                    $value = $this->hydrate($entityName, $value);
                }
                $result[$propertyName] = $value;
                continue;
            } elseif ($column instanceof OneToMany) {
                if (!is_array($value)) {
                    $value = [];
                }
                $entityName = $column->getTargetEntity();
                $storage = [];
                foreach ($value as $item) {
                    $storage[] = $this->hydrate($entityName, $item);
                }
                $result[$propertyName] = $storage;
                unset($storage);
                continue;
            }
            $value =  $column->convertToPHP($value);
            if ($value instanceof \DateTimeInterface) {
                $value = $column->convertToDatabase($value);
            }
            $result[$propertyName] = $value;
        }
        return $result;
    }

}
