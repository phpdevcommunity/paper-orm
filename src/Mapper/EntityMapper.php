<?php

namespace PhpDevCommunity\PaperORM\Mapper;


use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Entity\TableMetadataInterface;
use PhpDevCommunity\PaperORM\Mapping\Entity;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;

final class EntityMapper
{
    static public function getTable($class): string
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }

        if (is_subclass_of($class, TableMetadataInterface::class)) {
            return $class::getTableName();
        }

        if (PHP_VERSION_ID >= 80000) {
            $entity = self::getEntityPHP8($class);
            if ($entity instanceof Entity) {
                return $entity->getTable();
            }
        }

        if (method_exists($class, 'getTableName')) {
            return $class::getTableName();
        }

        throw new \LogicException(sprintf(
            'Entity %s must define a entityName via interface, attribute or static method',
            is_object($class) ? get_class($class) : $class
        ));
    }

    static public function getRepositoryName($class): ?string
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }

        if (is_subclass_of($class, TableMetadataInterface::class)) {
            return $class::getRepositoryName();
        }

        if (PHP_VERSION_ID >= 80000) {
            $entity = self::getEntityPHP8($class);
            if ($entity instanceof Entity) {
                return $entity->getRepositoryClass();
            }
        }

        if (method_exists($class, 'getRepositoryName')) {
            return $class::getRepositoryName();
        }

        throw new \LogicException(sprintf(
            'Entity %s must define a repository via interface, attribute or static method',
            is_object($class) ? get_class($class) : $class
        ));
    }

    static private function getEntityPHP8($class): ?Entity
    {
        if ($class instanceof ProxyInterface) {
            $class = $class->__getParentClass();
        }elseif (is_subclass_of($class, ProxyInterface::class)) {
            $reflector = new \ReflectionClass($class);
            $parentClass = $reflector->getParentClass();
            if ($parentClass) {
                $class = $parentClass->getName();
            }
        }

        $reflector = new \ReflectionClass($class);
        $attributes = $reflector->getAttributes(Entity::class);
        if (!empty($attributes)) {
            /** @var \PhpDevCommunity\PaperORM\Mapping\Entity $instance */
            return $attributes[0]->newInstance();
        }

        return null;
    }
}
