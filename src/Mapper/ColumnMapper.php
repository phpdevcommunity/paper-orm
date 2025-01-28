<?php

namespace PhpDevCommunity\PaperORM\Mapper;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\PaperORM\Cache\ColumnCache;
use PhpDevCommunity\PaperORM\Cache\OneToManyCache;
use PhpDevCommunity\PaperORM\Cache\PrimaryKeyColumnCache;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use ReflectionClass;

final class ColumnMapper
{

    static public function getPrimaryKeyColumn($class): string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $cache = PrimaryKeyColumnCache::getInstance();
        if (empty($cache->get($class))) {

            $columnsFiltered = array_filter(self::getColumns($class), function (Column $column) {
                return $column instanceof PrimaryKeyColumn;
            });

            if (count($columnsFiltered) === 0) {
                throw new LogicException(self::class . ' At least one primary key is required. : ' . $class);
            }

            if (count($columnsFiltered) > 1) {
                throw new LogicException(self::class . ' Only one primary key is allowed. : ' . $class);
            }

            $primaryKey = $columnsFiltered[0];

            $cache->set($class, $primaryKey);
        }
        return $cache->get($class)->getName();
    }

    /**
     * @param string $class
     * @return array<Column>
     */
    static public function getColumns($class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $cache = ColumnCache::getInstance();
        if (empty($cache->get($class))) {
            self::loadCache($class);
        }
        return $cache->get($class);
    }


    /**
     * @param $class
     * @return array<OneToMany>
     */
    final static public function getOneToManyRelations($class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $cache = OneToManyCache::getInstance();
        if (empty($cache->get($class))) {
            $columnsMapping = [];
            if (is_subclass_of($class, EntityInterface::class)) {
                $columnsMapping = $class::columnsMapping();
                $columnsMapping = array_filter($columnsMapping, function ($column) {
                    return $column instanceof OneToMany;
                });
            }

            $cache->set($class, $columnsMapping);
            self::loadCache($class);
        }

        return $cache->get($class);
    }

    static public function getColumnByProperty(string $class, string $property): ?Column
    {
        $columns = self::getColumns($class);
        foreach ($columns as $column) {
            if ($column->getProperty() === $property || $column->getName() === $property) {
                return $column;
            }
        }
        return null;
    }


    static private function loadCache(string $class): void
    {
        if (is_subclass_of($class, EntityInterface::class)) {
            $columnsMapping = $class::columnsMapping();
            $columnsMapping = array_filter($columnsMapping, function ($column) {
                return $column instanceof Column;
            });
        }
        if (empty($columnsMapping)) {
            throw new InvalidArgumentException('No columns found. : ' . $class);
        }

        ColumnCache::getInstance()->set($class, $columnsMapping);
    }
}
