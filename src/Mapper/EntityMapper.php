<?php

namespace PhpDevCommunity\PaperORM\Mapper;


use PhpDevCommunity\PaperORM\Entity\EntityInterface;

final class EntityMapper
{
    static public function getTable($class): string
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }
        return $class::getTableName();
    }

    static public function getRepositoryName($class): ?string
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }
        return $class::getRepositoryName();
    }
}
