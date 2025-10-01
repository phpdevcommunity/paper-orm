<?php

namespace PhpDevCommunity\PaperORM\Serializer;


use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;

final class SerializerToDb
{
    /**
     * @var object
     */
    private object $entity;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
    }

    public function serialize(array $columnsToSerialize = [] ): array
    {
        $entity = $this->entity;
        $columns = ColumnMapper::getColumns(get_class($entity));
        $reflection = new \ReflectionClass($entity);
        $dbData = [];
        foreach ($columns as $column) {
            if (!empty($columnsToSerialize) && !in_array($column->getProperty(), $columnsToSerialize)) {
                continue;
            }

            try {
                if (false !== ($reflectionParent = $reflection->getParentClass()) && $reflectionParent->hasProperty($column->getProperty())) {
                    $property = $reflectionParent->getProperty($column->getProperty());
                }else {
                    $property = $reflection->getProperty($column->getProperty());
                }
            }    catch (\ReflectionException $e) {
                throw new \InvalidArgumentException("Property {$column->getProperty()} not found in class " . get_class($entity));
            }

            $property->setAccessible(true);
            $key = $column->getName();
            $value = $property->getValue($entity);
            if ($column instanceof JoinColumn) {
                if (is_object($value) && ($value instanceof EntityInterface || method_exists($value, 'getPrimaryKeyValue'))) {
                    $value = $value->getPrimaryKeyValue();
                }
            }

            $dbData[$key] = $column->convertToDatabase($value) ;

        }
        return $dbData;
    }

}
