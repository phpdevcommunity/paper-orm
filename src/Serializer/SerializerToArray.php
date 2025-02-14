<?php

namespace PhpDevCommunity\PaperORM\Serializer;

use LogicException;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use ReflectionClass;
use ReflectionException;

final class SerializerToArray
{
    private object $entity;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
    }

    public function serialize(): array
    {
        $entity = $this->entity;
        $columns = ColumnMapper::getColumns(get_class($entity));
        if (count($columns) === 0) {
            return [];
        }

        $reflection = new ReflectionClass($entity);
        $data = [];
        foreach ($columns as $column) {
            try {
                if (false !== ($reflectionParent = $reflection->getParentClass()) && $reflectionParent->hasProperty($column->getProperty())) {
                    $property = $reflectionParent->getProperty($column->getProperty());
                }else {
                    $property = $reflection->getProperty($column->getProperty());
                }
            } catch (ReflectionException $e) {
                throw new LogicException("Property {$column->getProperty()} not found in class " . get_class($entity));
            }

            $property->setAccessible(true);
            $value = $property->getValue($entity);
            $propertyName = $column->getProperty();
            if (is_iterable($value)) {
                $data[$propertyName] = iterator_to_array($value);
                continue;
            }

            if ($column instanceof DateTimeColumn) {
                $data[$propertyName] = $column->convertToDatabase($value);
                continue;
            }

            if ($column instanceof JoinColumn) {
                $data[$propertyName] = (new self($value))->serialize();
                continue;
            }

            $data[$propertyName] = $property->getValue($entity);

        }
        return $data;
    }

}
